<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ReconciliationQueue;
use App\Models\ImportBatch;
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
        ];

        return view('reconciliation.dashboard', compact('metrics'));
    }

    public function data(Request $request)
    {
        $query = ReconciliationQueue::with('agent', 'lockedBy');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contract_id', 'like', "%{$search}%")
                  ->orWhere('member_first_name', 'like', "%{$search}%")
                  ->orWhere('member_last_name', 'like', "%{$search}%")
                  ->orWhere('carrier', 'like', "%{$search}%")
                  ->orWhere('ims_transaction_id', 'like', "%{$search}%");
            });
        }

        $sortCol = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortCol, $sortDir);

        $perPage = $request->input('per_page', 50);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => tap($paginator->getCollection(), function($collection) {
                // Formatting data for UI rendering. Decrypt necessary fields carefully.
                $collection->transform(function($item) {
                    $item->member_dob_decrypted = $item->member_dob ? decrypt($item->member_dob) : null;
                    return $item;
                });
            }),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage()
        ]);
    }
}
