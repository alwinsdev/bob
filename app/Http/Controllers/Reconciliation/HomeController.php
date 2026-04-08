<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\LockList;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Reconciliation Hub — Home (Landing Page)
     *
     * Aggregates pipeline metrics, recent activity, and quick-nav
     * into a single executive summary view.
     */
    public function index()
    {
        $user = Auth::user();

        // ── Cache Heavy System Metrics for 60 Seconds ───────────
        $stats = \Illuminate\Support\Facades\Cache::remember('reconciliation_home_stats', 60, function () {
            return [
                'pipeline' => [
                    'total'     => ReconciliationQueue::count(),
                    'pending'   => ReconciliationQueue::pending()->count(),
                    'flagged'   => ReconciliationQueue::flagged()->count(),
                    'resolved'  => ReconciliationQueue::resolved()->count(),
                    'locked'    => ReconciliationQueue::locked()->count(),
                ],
                'batches' => [
                    'total'     => ImportBatch::count(),
                    'completed' => ImportBatch::completed()->count(),
                    'failed'    => ImportBatch::failed()->count(),
                    'processing'=> ImportBatch::processing()->count(),
                ],
                'lockList' => [
                    'total_rules'     => LockList::count(),
                    'overrides_applied' => ReconciliationQueue::where('override_flag', true)->count(),
                ],
                'sourceBreakdown' => ReconciliationQueue::resolved()
                    ->select('match_method', DB::raw('count(*) as total'))
                    ->groupBy('match_method')
                    ->orderByDesc('total')
                    ->limit(6)
                    ->get()
                    ->map(fn($row) => [
                        'method'  => $row->match_method ?: 'Unspecified',
                        'total'   => $row->total,
                    ]),
                'recentActivity' => ReconciliationAuditLog::with('modifiedBy')
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get(),
                'recentBatches' => ImportBatch::with('uploadedBy')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(),
                'today' => [
                    'resolved_today' => ReconciliationQueue::resolved()
                        ->whereDate('resolved_at', today())
                        ->count(),
                    'imports_today' => ImportBatch::whereDate('created_at', today())
                        ->count(),
                    'audit_actions_today' => ReconciliationAuditLog::whereDate('created_at', today())
                        ->count(),
                ]
            ];
        });

        $pipeline = $stats['pipeline'];
        $pipeline['resolution_rate'] = $pipeline['total'] > 0
            ? round(($pipeline['resolved'] / $pipeline['total']) * 100, 1)
            : 0;

        $batches = $stats['batches'];
        $lockList = $stats['lockList'];
        $sourceBreakdown = $stats['sourceBreakdown'];
        $recentActivity = $stats['recentActivity'];
        $recentBatches = $stats['recentBatches'];
        $today = $stats['today'];

        return view('reconciliation.home', compact(
            'user',
            'pipeline',
            'batches',
            'lockList',
            'sourceBreakdown',
            'recentActivity',
            'recentBatches',
            'today',
        ));
    }
}
