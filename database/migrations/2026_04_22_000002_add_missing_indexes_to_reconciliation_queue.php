<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing composite and single-column indexes to reconciliation_queue.
 *
 * Performance analysis identified these common query patterns that lack
 * dedicated indexes, causing full table scans on large datasets:
 *
 *  1. (import_batch_id, status)  — Dashboard and batch-results data endpoints
 *     filter by batch and status on every page load.
 *  2. transaction_id             — Used in audit log joins, search, and the
 *     unique constraint; missing from the index list.
 *  3. locked_by                  — "Records locked by me" query in the UI.
 *  4. (import_batch_id, locked_by) — Bulk lock-check query added by the
 *     bulkResolve pre-flight check (Issue 1.4 remediation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            // (import_batch_id, status) — most common dashboard query pattern
            if (!$this->indexExists('reconciliation_queue', 'idx_batch_status')) {
                $table->index(['import_batch_id', 'status'], 'idx_batch_status');
            }

            // transaction_id — used in audit log joins, full-text search, and
            // the UNION query in AuditLogController. Already has a UNIQUE
            // constraint but a named index aids query planning on some drivers.
            if (!$this->indexExists('reconciliation_queue', 'idx_transaction_id')) {
                $table->index('transaction_id', 'idx_transaction_id');
            }

            // locked_by — "show records I have locked" filter
            if (!$this->indexExists('reconciliation_queue', 'idx_locked_by')) {
                $table->index('locked_by', 'idx_locked_by');
            }

            // (import_batch_id, locked_by) — bulk resolve pre-flight:
            // WHERE import_batch_id IN (...) AND locked_by != auth()->id()
            if (!$this->indexExists('reconciliation_queue', 'idx_batch_locked')) {
                $table->index(['import_batch_id', 'locked_by'], 'idx_batch_locked');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            foreach (['idx_batch_status', 'idx_transaction_id', 'idx_locked_by', 'idx_batch_locked'] as $index) {
                if ($this->indexExists('reconciliation_queue', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    /**
     * Cross-driver index existence check (reused from enterprise patch migration pattern).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            return match (DB::connection()->getDriverName()) {
                'mysql'  => !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])),
                'pgsql'  => !empty(DB::select(
                    'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                    [$table, $indexName]
                )),
                'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                    ->contains(fn ($row) => (($row->name ?? $row->Name ?? null) === $indexName)),
                'sqlsrv' => !empty(DB::select(
                    'SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
                    [$indexName, $table]
                )),
                default => false,
            };
        } catch (\Throwable) {
            return false;
        }
    }
};
