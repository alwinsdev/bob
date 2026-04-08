<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveRecordRequest;
use App\Http\Requests\BulkResolveRequest;
use App\Models\LockList;
use App\Models\ReconciliationQueue;
use App\Models\ReconciliationAuditLog;
use App\Models\Agent;
use App\Services\RecordLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{
    public function lock(ReconciliationQueue $record, RecordLockService $lockService)
    {
        if ($record->isLockedByOther(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Record is currently locked by another user.'], 403);
        }

        $lockService->acquire($record, auth()->user());

        ReconciliationAuditLog::create([
            'transaction_id' => $record->transaction_id,
            'action' => 'lock_acquired',
            'modified_by_user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json(['success' => true]);
    }

    public function unlock(ReconciliationQueue $record, RecordLockService $lockService)
    {
        $user = auth()->user();
        if ($record->locked_by !== $user->id && !$user->can('reconciliation.bulk_approve')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to unlock this record.'], 403);
        }

        $shouldLogRelease = $record->locked_by === $user->id;

        $lockService->release($record, $user);

        if ($shouldLogRelease) {
            ReconciliationAuditLog::create([
                'transaction_id' => $record->transaction_id,
                'action' => 'lock_released',
                'modified_by_user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function resolve(ResolveRecordRequest $request, ReconciliationQueue $record, RecordLockService $lockService)
    {
        if ($record->locked_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You must lock the record before resolving.'], 403);
        }

        $agent = Agent::where('agent_code', $request->aligned_agent_code)->first();
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Aligned Agent Code was not found. Please enter a valid code.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $record, $agent, $lockService) {
                $prevAgentCode = $record->aligned_agent_code;

                $record->update([
                    'status' => 'resolved',
                    'aligned_agent_code' => $agent->agent_code,
                    'aligned_agent_name' => $agent->full_name,
                    'group_team_sales' => $agent->group_team_sales,
                    'payee_name' => $agent->full_name,
                    'compensation_type' => $request->compensation_type,
                    'resolved_by' => auth()->id(),
                    'resolved_at' => now(),
                ]);

                ReconciliationAuditLog::create([
                    'transaction_id' => $record->transaction_id,
                    'action' => 'resolved',
                    'previous_agent_code' => $prevAgentCode,
                    'new_agent_code' => $agent->agent_code,
                    'modified_by_user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Opt-in feedback loop: only update Lock List if the user chose to
                if ($request->boolean('save_to_locklist') && !empty($record->contract_id)) {
                    $user = auth()->user();
                    // Only users with bulk approval permission may write to lock list.
                    if ($user && $user->can('reconciliation.bulk_approve')) {
                        LockList::updateOrCreate(
                            ['policy_id' => $record->contract_id],
                            [
                                'agent_name' => $agent->full_name,
                                'department' => $agent->group_team_sales,
                                'payee_name' => $agent->full_name,
                                'promoted_by' => $user->id,
                            ]
                        );

                        ReconciliationAuditLog::create([
                            'transaction_id' => $record->transaction_id,
                            'action' => 'locklist_updated',
                            'new_agent_code' => $agent->agent_code,
                            'modified_by_user_id' => $user->id,
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'notes' => "Manual resolve saved to Lock List for contract {$record->contract_id}.",
                        ]);
                    }
                }

                $lockService->release($record, auth()->user());
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? ('Resolution failed: ' . $e->getMessage())
                    : 'Resolution failed due to a server issue. Please try again.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Record resolved successfully.']);
    }

    public function flag(Request $request, ReconciliationQueue $record, RecordLockService $lockService)
    {
        if ($record->locked_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You must lock the record before flagging.'], 403);
        }

        $request->validate([
            'flag_value' => ['nullable', 'string'],
            'flagValue' => ['nullable', 'string'],
        ]);

        // Backward compatibility: older bundles may omit flag_value entirely.
        $rawFlagValue = $request->input('flag_value', $request->input('flagValue'));
        if ($rawFlagValue === null || trim((string) $rawFlagValue) === '') {
            $rawFlagValue = $record->flag_value ?: 'Home Open';
        }

        $normalizedFlagValue = match (strtolower(trim((string) $rawFlagValue))) {
            'home open' => 'Home Open',
            'home close' => 'Home Close',
            default => null,
        };

        if ($normalizedFlagValue === null) {
            return response()->json([
                'success' => false,
                'message' => 'Flag value must be either Home Open or Home Close.',
            ], 422);
        }

        $record->update([
            'status' => 'flagged',
            'flag_value' => $normalizedFlagValue,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        ReconciliationAuditLog::create([
            'transaction_id' => $record->transaction_id,
            'action' => 'flagged',
            'modified_by_user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $lockService->release($record, auth()->user());

        return response()->json(['success' => true, 'message' => 'Record flagged for review.']);
    }

    public function bulkResolve(BulkResolveRequest $request)
    {
        $agent = Agent::where('agent_code', $request->aligned_agent_code)->first();
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Aligned Agent Code was not found. Please enter a valid code.',
            ], 422);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, ReconciliationQueue> $records */
        $records = ReconciliationQueue::whereIn('id', $request->record_ids)
            ->whereIn('status', ['pending', 'flagged'])
            ->get();

        $processed = 0;
        try {
            DB::transaction(function () use ($records, $agent, $request, &$processed) {
                foreach ($records as $record) {
                    /** @var ReconciliationQueue $record */
                    if ($record->isLockedByOther(auth()->user()) || $record->status === 'resolved') {
                        continue;
                    }

                    $prevAgentCode = $record->aligned_agent_code;

                    $record->update([
                        'status' => 'resolved',
                        'aligned_agent_code' => $agent->agent_code,
                        'aligned_agent_name' => $agent->full_name,
                        'group_team_sales' => $agent->group_team_sales,
                        'payee_name' => $agent->full_name,
                        'compensation_type' => $request->compensation_type,
                        'resolved_by' => auth()->id(),
                        'resolved_at' => now(),
                        'locked_by' => null,
                        'locked_at' => null,
                    ]);

                    ReconciliationAuditLog::create([
                        'transaction_id' => $record->transaction_id,
                        'action' => 'resolved',
                        'previous_agent_code' => $prevAgentCode,
                        'new_agent_code' => $agent->agent_code,
                        'modified_by_user_id' => auth()->id(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    $processed++;
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? ('Bulk resolution failed: ' . $e->getMessage())
                    : 'Bulk resolution failed due to a server issue. Please try again.',
            ], 500);
        }

        if ($processed === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No records were resolved. Selected records may already be resolved or locked by another user.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully resolved {$processed} out of " . count($request->record_ids) . " records."
        ]);
    }

    /**
     * Bulk promote selected records to the Lock List.
     *
     * This is a separate explicit action — bulk resolve does NOT auto-update
     * the Lock List. Only authorized roles may execute this.
     */
    public function bulkPromoteToLocklist(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'record_ids' => 'required|array|min:1',
            'record_ids.*' => 'required|string',
        ]);

        $records = ReconciliationQueue::whereIn('id', $request->record_ids)
            ->whereNotNull('contract_id')
            ->where('contract_id', '!=', '')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No eligible records found (records must have a Contract ID).',
            ], 422);
        }

        $promoted = 0;

        DB::transaction(function () use ($records, $user, &$promoted) {
            foreach ($records as $record) {
                LockList::updateOrCreate(
                    ['policy_id' => $record->contract_id],
                    [
                        'agent_name' => $record->aligned_agent_name,
                        'department' => $record->group_team_sales,
                        'payee_name' => $record->payee_name,
                        'promoted_from_batch_id' => $record->import_batch_id,
                        'promoted_by' => $user->id,
                    ]
                );

                ReconciliationAuditLog::create([
                    'transaction_id' => $record->transaction_id,
                    'action' => 'promoted_to_locklist',
                    'new_agent_code' => $record->aligned_agent_code,
                    'modified_by_user_id' => $user->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'notes' => "Bulk promoted contract {$record->contract_id} to Lock List.",
                ]);

                $promoted++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully promoted {$promoted} records to the Lock List.",
        ]);
    }

    /**
     * Promote a resolved record's assignment to the Lock List.
     *
     * Restricted to Operations Manager and Admin users only.
     * Upserts on policy_id so existing entries are updated, not duplicated.
     */
    public function promoteToLocklist(ReconciliationQueue $record)
    {
        $user = auth()->user();

        // A contract_id is mandatory for a Lock List entry (policy_id)
        if (empty($record->contract_id)) {
            return response()->json([
                'success' => false,
                'message' => 'This record has no Contract ID. Cannot create a Lock List entry.',
            ], 422);
        }

        DB::transaction(function () use ($record, $user) {
            LockList::updateOrCreate(
                ['policy_id' => $record->contract_id],
                [
                    'agent_name' => $record->aligned_agent_name,
                    'department' => $record->group_team_sales,
                    'payee_name' => $record->payee_name,
                    'promoted_from_batch_id' => $record->import_batch_id,
                    'promoted_by' => $user->id,
                ]
            );

            ReconciliationAuditLog::create([
                'transaction_id' => $record->transaction_id,
                'action' => 'promoted_to_locklist',
                'new_agent_code' => $record->aligned_agent_code,
                'modified_by_user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'notes' => "Promoted contract {$record->contract_id} to Lock List.",
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Contract {$record->contract_id} has been added to the Lock List.",
        ]);
    }
}
