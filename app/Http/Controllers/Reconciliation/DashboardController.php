<?php

namespace App\Http\Controllers\Reconciliation;

use App\Exports\ReconciliationExport;
use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\ReconciliationQueue;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $metrics = [
            'total_pending' => ReconciliationQueue::pending()->count(),
            'total_flagged' => ReconciliationQueue::flagged()->count(),
            'total_resolved' => ReconciliationQueue::resolved()->count(),
            'total_batches' => ImportBatch::count(),
            'total_locklist_overrides' => ReconciliationQueue::where('override_flag', true)->count(),
        ];

        return view('reconciliation.dashboard', compact('metrics'));
    }

    public function data(Request $request)
    {
        $query = $this->buildQuery($request);

        $perPage = max(1, min((int) $request->integer('per_page', 50), 250));
        $paginator = $query->paginate($perPage);

        $rows = $paginator->getCollection()->map(function (ReconciliationQueue $record) {
            return [
                'id' => $record->id,
                'transaction_id' => $record->transaction_id,
                'status' => $record->status,
                'flag_value' => $record->flag_value,
                'contract_id' => $record->contract_id,
                'member_first_name' => $record->member_first_name,
                'member_last_name' => $record->member_last_name,
                'carrier' => $record->carrier,
                'effective_date' => $record->effective_date,
                'ims_transaction_id' => $record->ims_transaction_id,
                'agent_id' => $record->agent_id,
                'aligned_agent_code' => $record->aligned_agent_code,
                'aligned_agent_name' => $record->aligned_agent_name,
                'group_team_sales' => $record->group_team_sales,
                'payee_name' => $record->payee_name,
                'match_method' => $record->match_method,
                'match_method_label' => $record->match_method_label,
                'match_confidence' => $record->match_confidence,
                'match_confidence_percent' => $record->match_confidence_percent,
                'match_confidence_bucket' => $record->match_confidence_bucket,
                'field_scores' => $record->field_scores,
                'override_flag' => (bool) $record->override_flag,
                'override_source' => $record->override_source,
                'created_at' => $record->created_at,
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Export data based on user preference (XLSX, CSV, PDF).
     */
    public function export(Request $request)
    {
        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $format = $prefs['export_format'] ?? 'xlsx';

        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $fileName = 'Reconciliation_Hub_'.now()->format('Ymd_His');

        if ($format === 'pdf') {
            $records = $this->buildQuery($request)->get();
            $pdf = Pdf::loadView('reports.reconciliation-pdf', [
                'records' => $records,
                'status' => $status,
                'search' => $search,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileName.'.pdf');
        }

        $export = new ReconciliationExport($status, $search);
        $ext = in_array($format, ['xlsx', 'csv']) ? $format : 'xlsx';

        return $export->download($fileName.'.'.$ext);
    }

    /**
     * Shared query builder for data and export.
     */
    private function buildQuery(Request $request)
    {
        $query = ReconciliationQueue::query()->select([
            'id',
            'transaction_id',
            'status',
            'flag_value',
            'contract_id',
            'member_first_name',
            'member_last_name',
            'carrier',
            'effective_date',
            'ims_transaction_id',
            'agent_id',
            'aligned_agent_code',
            'aligned_agent_name',
            'group_team_sales',
            'payee_name',
            'match_method',
            'match_confidence',
            'field_scores',
            'override_flag',
            'override_source',
            'created_at',
        ]);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contract_id', 'like', "%{$search}%")
                    ->orWhere('member_first_name', 'like', "%{$search}%")
                    ->orWhere('member_last_name', 'like', "%{$search}%")
                    ->orWhere('carrier', 'like', "%{$search}%")
                    ->orWhere('ims_transaction_id', 'like', "%{$search}%");
            });
        }

        $allowedSorts = [
            'created_at', 'contract_id', 'member_first_name', 'member_last_name',
            'carrier', 'ims_transaction_id', 'status', 'match_confidence',
        ];
        $sortCol = in_array($request->input('sort_by'), $allowedSorts) ? $request->input('sort_by') : 'created_at';
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortCol, $sortDir);
    }
}
