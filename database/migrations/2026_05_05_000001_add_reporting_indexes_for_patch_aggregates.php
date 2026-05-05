<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reporting performance indexes — addresses two hot paths surfaced by the
 * audit:
 *
 *   1. ContractPatchReportingController::aggregateSourceDistribution()
 *      runs `GROUP BY new_match_source WHERE batch_id|parent_batch_id = ?`
 *      on every grid scroll. Without these covering indexes, MySQL does a
 *      full table scan on contract_patch_logs (~50ms per call at 100K rows,
 *      multiplied by every page click × every concurrent analyst).
 *
 *   2. CommissionReportingController agent-distribution queries
 *      `GROUP BY aligned_agent_name WHERE import_batch_id = ?`
 *      against reconciliation_queue — same pattern, same scan cost.
 *
 * Each index is composite (filter column first, group/select column second)
 * so MySQL can satisfy both the WHERE and the GROUP BY from the index
 * without touching the heap.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('contract_patch_logs', function (Blueprint $table) {
            // aggregateSourceDistribution() — single-batch lookup
            $table->index(['batch_id', 'new_match_source'], 'cpl_batch_source_idx');

            // aggregateSourceDistribution() — parent-batch (multi-run aggregate)
            $table->index(['parent_batch_id', 'new_match_source'], 'cpl_parent_source_idx');

            // Final BOB report's per-contract patch trace
            $table->index(['parent_batch_id', 'contract_id'], 'cpl_parent_contract_idx');
        });

        Schema::table('reconciliation_queue', function (Blueprint $table) {
            // CommissionReportingController agent distribution
            $table->index(['import_batch_id', 'aligned_agent_name'], 'rq_agent_dist_idx');
        });
    }

    public function down(): void
    {
        Schema::table('contract_patch_logs', function (Blueprint $table) {
            $table->dropIndex('cpl_batch_source_idx');
            $table->dropIndex('cpl_parent_source_idx');
            $table->dropIndex('cpl_parent_contract_idx');
        });

        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->dropIndex('rq_agent_dist_idx');
        });
    }
};
