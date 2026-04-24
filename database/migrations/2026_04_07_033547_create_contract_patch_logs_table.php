<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * contract_patch_logs table
 *
 * A dedicated, immutable audit log for every field updated by the
 * Contract Patch Engine. Unlike the general ReconciliationAuditLog,
 * this table captures the before/after state of every corrected field,
 * the change's origin batch, and who triggered it — enabling full
 * regulatory-grade traceability.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_patch_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // ── Identity ──────────────────────────────────────────────────
            $table->string('contract_id', 100)->index()
                ->comment('The contract/policy identifier that was patched');

            // ── Batch Provenance ──────────────────────────────────────────
            $table->string('batch_id', 26)->index()
                ->comment('The contract patch ImportBatch that triggered this change');
            $table->string('parent_batch_id', 26)->nullable()->index()
                ->comment('The parent Final BOB batch that this patch targets');
            $table->string('previous_batch_id', 26)->nullable()
                ->comment('The historical batch used as source of truth for flag decision');

            // ── Agent Before/After ────────────────────────────────────────
            $table->string('old_agent_code', 100)->nullable();
            $table->string('old_agent_name', 255)->nullable();
            $table->string('new_agent_code', 100)->nullable();
            $table->string('new_agent_name', 255)->nullable();

            // ── Payee Before/After ────────────────────────────────────────
            $table->string('old_payee_name', 255)->nullable();
            $table->string('new_payee_name', 255)->nullable();

            // ── Department Before/After ───────────────────────────────────
            $table->string('old_department', 255)->nullable();
            $table->string('new_department', 255)->nullable();

            // ── Match Source Before/After ─────────────────────────────────
            $table->string('old_match_source', 100)->nullable()
                ->comment('Match method before patching, e.g. IMS:Email, Unmatched');
            $table->string('new_match_source', 100)->nullable()->default('Contract Patch')
                ->comment('Always Contract Patch after a successful update');

            // ── Flag Context ──────────────────────────────────────────────
            $table->string('flag_value', 50)->nullable()
                ->comment('The historical flag (House Open / House Close) that justified this patch');

            // ── Classification ────────────────────────────────────────────
            $table->string('change_type', 50)->default('patch_applied')
                ->comment('patch_applied | skipped_locked | skipped_no_flag | etc.');

            // ── Operator ──────────────────────────────────────────────────
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('The user who uploaded the contract patch file');

            $table->string('queue_record_id', 26)->nullable()
                ->comment('The reconciliation_queue record that was updated');

            $table->timestamps();
        });

        // Composite index for fast audit lookups per batch
        Schema::table('contract_patch_logs', function (Blueprint $table) {
            $table->index(['batch_id', 'change_type'], 'cpl_batch_change_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_patch_logs');
    }
};
