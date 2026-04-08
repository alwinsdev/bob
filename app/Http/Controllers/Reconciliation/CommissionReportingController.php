<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\ReconciliationQueue;
use App\Exports\FinalBobExport;
use App\Exports\LocklistImpactExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CommissionReportingController extends Controller
{
    /**
     * Display the Final BOB Data view.
     */
    public function finalBob()
    {
        return view('reconciliation.reporting.final-bob');
    }

    /**
     * Provide JSON data for Final BOB AG Grid.
     * Only returns "resolved" records (commission-ready).
     */
    public function finalBobData(Request $request)
    {
        $batchId = $this->resolveReportingBatchId($request);

        $query = ReconciliationQueue::resolved()
            ->notArchived()
            ->select([
                'id', 'contract_id', 'member_first_name', 'member_last_name',
                'aligned_agent_name', 'group_team_sales', 'payee_name',
                'match_method', 'override_flag', 'import_batch_id'
            ]);

        if ($batchId) {
            $query->where('import_batch_id', $batchId);
        } else {
            $query->whereRaw('1 = 0');
        }

        $records = $query->get();

        $latestPatchByContract = collect();
        if ($batchId && $records->isNotEmpty()) {
            $contractIds = $records->pluck('contract_id')
                ->filter(static fn ($contractId) => !empty($contractId))
                ->unique()
                ->values();

            if ($contractIds->isNotEmpty()) {
                $latestPatchByContract = DB::table('contract_patch_logs')
                    ->select(['contract_id', 'batch_id', 'created_at'])
                    ->where('parent_batch_id', $batchId)
                    ->whereIn('contract_id', $contractIds)
                    ->orderByDesc('created_at')
                    ->get()
                    ->unique('contract_id')
                    ->keyBy('contract_id');
            }
        }

        return response()->json($records->map(function ($record) use ($latestPatchByContract, $batchId) {
            $patchMeta = $latestPatchByContract->get($record->contract_id);

            return [
                'id' => $record->id,
                'contract_id' => $record->contract_id,
                'member_name' => trim($record->member_first_name . ' ' . $record->member_last_name),
                'agent_name' => $record->aligned_agent_name,
                'department' => $record->group_team_sales,
                'payee_name' => $record->payee_name,
                'match_method' => $record->match_method_label,
                'override_flag' => $record->override_flag,
                'patch_trace_available' => (bool) $patchMeta,
                'latest_patch_batch_id' => $patchMeta->batch_id ?? null,
                'patch_ledger_url' => $patchMeta
                    ? route('reconciliation.reporting.contract-patches', [
                        'parent_batch_id' => $batchId,
                        'batch_id' => $patchMeta->batch_id,
                        'contract_id' => $record->contract_id,
                    ])
                    : null,
            ];
        }));
    }

    /**
     * Display the Commission Dashboard view.
     */
    public function dashboard()
    {
        $batchId = $this->resolveReportingBatchId(request());

        if ($batchId) {
            $baseQuery = ReconciliationQueue::resolved()
                ->notArchived()
                ->where('import_batch_id', $batchId);

            $totalRecords = (clone $baseQuery)->count();
            $locklistOverrides = (clone $baseQuery)->where('override_flag', true)->count();

            // Count non-overridden matches specifically
            $imsMatches = (clone $baseQuery)->where('override_flag', false)
                ->where('match_method', 'like', 'ims:%')->count();
            $hsMatches = (clone $baseQuery)->where('override_flag', false)
                ->where('match_method', 'like', 'hs:%')->count();

            // Technically "unmatched" means it was resolved manually
            $manualMatches = $totalRecords - ($locklistOverrides + $imsMatches + $hsMatches);

            // Top Agents Distribution (Limited to top 10)
            $agentDistribution = DB::table('reconciliation_queue')
                ->where('status', 'resolved')
                ->whereNull('archived_at')
                ->where('import_batch_id', $batchId)
                ->whereNotNull('aligned_agent_name')
                ->where('aligned_agent_name', '!=', '')
                ->select('aligned_agent_name', DB::raw('count(*) as total'))
                ->groupBy('aligned_agent_name')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        } else {
            $totalRecords = 0;
            $locklistOverrides = 0;
            $imsMatches = 0;
            $hsMatches = 0;
            $manualMatches = 0;
            $agentDistribution = collect();
        }

        $metrics = [
            'total_records'      => $totalRecords,
            'locklist_overrides' => $locklistOverrides,
            'ims_matches'        => $imsMatches,
            'hs_matches'         => $hsMatches,
            'manual_matches'     => $manualMatches,
        ];

        $adjustmentSummary = $this->buildCommissionAdjustmentSummary($batchId);

        return view('reconciliation.reporting.commission-dashboard', compact('metrics', 'agentDistribution', 'adjustmentSummary'));
    }

    /**
     * Display the Locklist Impact view.
     */
    public function locklistImpact()
    {
        return view('reconciliation.reporting.locklist-impact');
    }

    /**
     * Provide JSON data for Locklist Impact AG Grid.
     */
    public function locklistImpactData(Request $request)
    {
        $batchId = $this->resolveReportingBatchId($request);

        $query = ReconciliationQueue::where('override_flag', true)
            ->notArchived()
            ->select([
                'id', 'contract_id', 'original_agent_name', 'aligned_agent_name',
                'original_match_method', 'match_method', 'member_first_name', 'member_last_name'
            ]);

        if ($batchId) {
            $query->where('import_batch_id', $batchId);
        } else {
            $query->whereRaw('1 = 0');
        }

        return response()->json($query->get()->map(function ($record) {
            $memberName = trim($record->member_first_name . ' ' . $record->member_last_name);
            return [
                'id' => $record->id,
                'contract_id' => $record->contract_id,
                'member_name' => $memberName ?: '—',
                'old_agent' => $record->original_agent_name ?: 'None',
                'new_agent' => $record->aligned_agent_name,
                'source_before' => $record->original_match_method ? 
                    $this->humanizeMethod($record->original_match_method) : 'Unmatched',
                'override_flag' => true,
            ];
        }));
    }

    private function humanizeMethod(?string $method): string
    {
        if (!$method) return 'Unmatched';
        return match (true) {
            str_starts_with($method, 'ims:') => 'IMS',
            str_starts_with($method, 'hs:') => 'Health Sherpa',
            default => 'Other'
        };
    }

    private function buildCommissionAdjustmentSummary(?string $parentBatchId): array
    {
        $summary = [
            'total_runs' => 0,
            'adjusted_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'top_skip_reasons' => [],
            'recent_runs' => [],
            'details_url' => $parentBatchId
                ? route('reconciliation.reporting.contract-patches', ['parent_batch_id' => $parentBatchId])
                : route('reconciliation.reporting.contract-patches'),
        ];

        if (!$parentBatchId) {
            return $summary;
        }

        $runs = ImportBatch::query()
            ->where('batch_type', 'contract_patch')
            ->where('parent_batch_id', $parentBatchId)
            ->latest('created_at')
            ->get([
                'id',
                'contract_original_name',
                'status',
                'contract_patched_records',
                'skipped_records',
                'failed_records',
                'skipped_summary',
                'created_at',
            ]);

        if ($runs->isEmpty()) {
            return $summary;
        }

        $summary['total_runs'] = $runs->count();
        $summary['adjusted_rows'] = (int) $runs->sum('contract_patched_records');
        $summary['skipped_rows'] = (int) $runs->sum('skipped_records');
        $summary['failed_rows'] = (int) $runs->sum('failed_records');

        $skipReasons = [];
        foreach ($runs as $run) {
            foreach (($run->skipped_summary ?? []) as $reason => $count) {
                $label = trim((string) $reason);
                $label = $label !== '' ? $label : 'Unspecified reason';
                $skipReasons[$label] = ($skipReasons[$label] ?? 0) + max(0, (int) $count);
            }
        }

        arsort($skipReasons);
        $summary['top_skip_reasons'] = array_slice($skipReasons, 0, 5, true);

        $summary['recent_runs'] = $runs->take(5)->map(function (ImportBatch $run) use ($parentBatchId) {
            return [
                'id' => $run->id,
                'name' => trim((string) $run->contract_original_name) !== ''
                    ? $run->contract_original_name
                    : 'Commission Adjustment',
                'status' => $run->status,
                'status_label' => ucwords(str_replace('_', ' ', (string) $run->status)),
                'adjusted_rows' => (int) $run->contract_patched_records,
                'skipped_rows' => (int) $run->skipped_records,
                'failed_rows' => (int) $run->failed_records,
                'created_at' => optional($run->created_at)->format('M d, Y h:i A'),
                'details_url' => route('reconciliation.reporting.contract-patches', [
                    'parent_batch_id' => $parentBatchId,
                    'batch_id' => $run->id,
                ]),
            ];
        })->values()->all();

        return $summary;
    }

    /**
     * Export Final BOB Data based on user preference.
     */
    public function exportFinalBob(Request $request)
    {
        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $format = $prefs['export_format'] ?? 'xlsx';
        $search = $request->input('search');
        $batchId = $this->resolveReportingBatchId($request);
        
        $fileName = 'Final_BOB_Output_' . now()->format('Ymd_His');

        if ($format === 'pdf') {
            $query = ReconciliationQueue::resolved()->notArchived()->latest();
            
            if ($batchId) {
                $query->where('import_batch_id', $batchId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('contract_id', 'like', "%{$search}%")
                      ->orWhere('member_first_name', 'like', "%{$search}%")
                      ->orWhere('member_last_name', 'like', "%{$search}%")
                      ->orWhere('aligned_agent_name', 'like', "%{$search}%")
                      ->orWhere('payee_name', 'like', "%{$search}%")
                      ->orWhere('group_team_sales', 'like', "%{$search}%");
                });
            }

            $records = $query->get();
            $pdf = Pdf::loadView('reports.final-bob-pdf', ['records' => $records])
                ->setPaper('a4', 'landscape');
            return $pdf->download($fileName . '.pdf');
        }

        $export = new FinalBobExport($search, $batchId);
        $ext = in_array($format, ['xlsx', 'csv']) ? $format : 'xlsx';

        return $export->download($fileName . '.' . $ext);
    }


    /**
     * Export Locklist Impact Data based on user preference.
     */
    public function exportLocklistImpact(Request $request)
    {
        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $format = $prefs['export_format'] ?? 'xlsx';
        $search = $request->input('search');
        $batchId = $this->resolveReportingBatchId($request);
        
        $fileName = 'Locklist_Impact_Output_' . now()->format('Ymd_His');

        if ($format === 'pdf') {
            $query = ReconciliationQueue::where('override_flag', true)->notArchived()->latest();
            
            if ($batchId) {
                $query->where('import_batch_id', $batchId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('contract_id', 'like', "%{$search}%")
                      ->orWhere('member_first_name', 'like', "%{$search}%")
                      ->orWhere('member_last_name', 'like', "%{$search}%")
                      ->orWhere('aligned_agent_name', 'like', "%{$search}%")
                      ->orWhere('payee_name', 'like', "%{$search}%")
                      ->orWhere('group_team_sales', 'like', "%{$search}%");
                });
            }

            $records = $query->get();
            $pdf = Pdf::loadView('reports.locklist-impact-pdf', ['records' => $records])
                ->setPaper('a4', 'landscape');
            return $pdf->download($fileName . '.pdf');
        }

        $export = new LocklistImpactExport($search, $batchId);
        $ext = in_array($format, ['xlsx', 'csv']) ? $format : 'xlsx';

        return $export->download($fileName . '.' . $ext);
    }


    private function resolveReportingBatchId(Request $request): ?string
    {
        $requestedBatchId = trim((string) $request->input('batch_id', ''));

        if ($requestedBatchId !== '' && $this->isEligibleReportingBatch($requestedBatchId)) {
            return $requestedBatchId;
        }

        return ImportBatch::query()
            ->topLevel()
            ->where('batch_type', 'standard')
            ->whereIn('status', ['completed', 'completed_with_errors'])
            // Default to the latest completed batch that contains reportable rows.
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('reconciliation_queue as rq')
                    ->whereColumn('rq.import_batch_id', 'import_batches.id')
                    ->whereNull('rq.archived_at')
                    ->where(function ($inner) {
                        $inner->where('rq.status', 'resolved')
                            ->orWhere('rq.override_flag', true);
                    });
            })
            ->latest('created_at')
            ->value('id');
    }

    private function isEligibleReportingBatch(string $batchId): bool
    {
        return ImportBatch::query()
            ->topLevel()
            ->where('batch_type', 'standard')
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->whereKey($batchId)
            ->exists();
    }

}
