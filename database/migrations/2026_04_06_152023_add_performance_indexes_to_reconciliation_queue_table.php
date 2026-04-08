<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            // Composite index for high-performance Contract Patch lookups
            // Pattern: where import_batch_id = ? AND status = 'flagged' AND contract_id = ?
            $table->index(['import_batch_id', 'status', 'contract_id'], 'rq_patch_lookup_idx');
            
            // Composite index for flag-level batch operations
            // Pattern: where import_batch_id = ? AND flag_value = ?
            $table->index(['import_batch_id', 'flag_value'], 'rq_batch_flag_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->dropIndex('rq_patch_lookup_idx');
            $table->dropIndex('rq_batch_flag_idx');
        });
    }
};
