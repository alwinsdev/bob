<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use Illuminate\Http\Request;

class BatchResultsController extends Controller
{
    public function show(ImportBatch $batch)
    {
        abort_unless(auth()->user()?->can('viewResults', $batch), 403);

        $batch->load('uploadedBy');

        $totalRecords = $batch->total_records ?? 0;
        $imsMatched = $batch->ims_matched_records ?? 0;
        $hsMatched = $batch->hs_matched_records ?? 0;
        $processed = $batch->processed_records ?? 0;
        $locklistCount = $batch->locklist_matched_records ?? 0;
        $unmatched = max(0, $processed - $imsMatched - $hsMatched - $locklistCount);

        return view('reconciliation.batch-results', compact(
            'batch',
            'totalRecords',
            'imsMatched',
            'hsMatched',
            'locklistCount',
            'unmatched',
            'processed'
        ));
    }

    public function batchData(Request $request, ImportBatch $batch)
    {
        abort_unless($request->user()?->can('viewResults', $batch), 403);

        $query = \App\Models\ReconciliationQueue::where('import_batch_id', $batch->id);

        if ($source = $request->input('source')) {
            match ($source) {
                'ims' => $query->where(fn($q) => $q->where('match_method', 'like', '%ims:%')->orWhere('original_match_method', 'like', '%ims:%')),
                'hs' => $query->where(fn($q) => $q->where('match_method', 'like', '%hs:%')->orWhere('original_match_method', 'like', '%hs:%')),
                'locklist' => $query->where(fn($q) => $q->where('match_method', 'like', '%locklist%')->orWhere('override_source', 'lock_list')),
                'unmatched' => $query->whereNull('match_method')->whereNull('original_match_method'),
                default => null,
            };
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('contract_id', 'like', "%{$search}%")
                    ->orWhere('member_first_name', 'like', "%{$search}%")
                    ->orWhere('member_last_name', 'like', "%{$search}%")
                    ->orWhere('aligned_agent_name', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $start = (int) $request->input('startRow', 0);
        $end = (int) $request->input('endRow', 100);
        $limit = max(1, $end - $start);

        $rows = $query
            ->orderBy('created_at', 'asc')
            ->skip($start)
            ->take($limit)
            ->get()
            ->map(fn(\App\Models\ReconciliationQueue $rec) => [
                'id' => $rec->id,
                'contract_id' => $rec->contract_id,
                'member_name' => trim("{$rec->member_first_name} {$rec->member_last_name}"),
                'carrier' => $rec->carrier,
                'effective_date' => $rec->effective_date instanceof \DateTimeInterface
                    ? $rec->effective_date->format('Y-m-d')
                    : ($rec->effective_date ? (string) $rec->effective_date : null),
                'status' => $rec->status,
                'aligned_agent' => $rec->aligned_agent_name,
                'agent_code' => $rec->aligned_agent_code,
                'department' => $rec->group_team_sales,
                'payee_name' => $rec->payee_name,
                'match_method' => $rec->match_method,
                'match_method_label' => $rec->match_method_label,
                'match_confidence' => $rec->match_confidence_percent,
                'match_bucket' => $rec->match_confidence_bucket,
                'override_flag' => (bool) $rec->override_flag,
                'override_source' => $rec->override_source,
                'is_promoted' => false,
                'can_promote' => in_array($rec->status, ['resolved', 'matched']) && !empty($rec->contract_id),
            ]);

        return response()->json(['rows' => $rows, 'totalCount' => $total]);
    }
}
