<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveRecordRequest;
use App\Http\Requests\BulkResolveRequest;
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
        return response()->json(['success' => true]);
    }

    public function unlock(ReconciliationQueue $record, RecordLockService $lockService)
    {
        $lockService->release($record, auth()->user());
        return response()->json(['success' => true]);
    }

    public function resolve(ResolveRecordRequest $request, ReconciliationQueue $record, RecordLockService $lockService)
    {
        if ($record->locked_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You must lock the record before resolving.'], 403);
        }

        $agent = Agent::where('agent_code', $request->aligned_agent_code)->firstOrFail();

        DB::transaction(function() use ($request, $record, $agent, $lockService) {
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

            $lockService->release($record, auth()->user());
        });

        return response()->json(['success' => true, 'message' => 'Record resolved successfully.']);
    }

    public function flag(Request $request, ReconciliationQueue $record, RecordLockService $lockService)
    {
        if ($record->locked_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You must lock the record before flagging.'], 403);
        }

        $record->update([
            'status' => 'flagged',
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
        $agent = Agent::where('agent_code', $request->aligned_agent_code)->firstOrFail();
        $records = ReconciliationQueue::whereIn('id', $request->record_ids)->get();

        $processed = 0;
        DB::transaction(function() use ($records, $agent, $request, &$processed) {
            foreach ($records as $record) {
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

        return response()->json([
            'success' => true, 
            'message' => "Successfully resolved {$processed} out of " . count($request->record_ids) . " records."
        ]);
    }
}
