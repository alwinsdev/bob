<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Contract Patch Engine Migration
 *
 * Adds:
 *   import_batches    → previous_batch_id, skipped_records
 *   reconciliation_queue → is_patched
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── import_batches ───────────────────────────────────────────────────
        Schema::table('import_batches', function (Blueprint $table) {
            // Links a contract patch run to the preceding Final BOB run it
            // used as its historical source of truth for flag decisions.
            if (!Schema::hasColumn('import_batches', 'previous_batch_id')) {
                $table->string('previous_batch_id', 26)->nullable()->after('parent_batch_id')
                    ->comment('The completed batch used as historical truth for flag resolution');
            }

            // Tracks gracefully-skipped rows (Not in Batch / No Flag / Already Patched / Locked)
            if (!Schema::hasColumn('import_batches', 'skipped_records')) {
                $table->unsignedInteger('skipped_records')->default(0)->after('failed_records')
                    ->comment('Rows skipped gracefully during contract patch processing');
            }
        });

        // ── reconciliation_queue ─────────────────────────────────────────────
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            // Idempotency flag – prevents the same record being patched twice
            if (!Schema::hasColumn('reconciliation_queue', 'is_patched')) {
                $table->boolean('is_patched')->default(false)->after('override_source')
                    ->comment('Set true after a contract patch is applied; prevents re-patching');
            }
        });

        // ── Indexes ──────────────────────────────────────────────────────────
        Schema::table('import_batches', function (Blueprint $table) {
            if (!$this->indexExists('import_batches', 'import_batches_previous_batch_id_index')) {
                $table->index('previous_batch_id', 'import_batches_previous_batch_id_index');
            }
        });

        Schema::table('reconciliation_queue', function (Blueprint $table) {
            if (!$this->indexExists('reconciliation_queue', 'recon_queue_is_patched_index')) {
                $table->index(['import_batch_id', 'is_patched'], 'recon_queue_is_patched_index');
            }
        });
    }

    public function down(): void
    {
        $hasImportBatchesIndex = $this->indexExists('import_batches', 'import_batches_previous_batch_id_index');
        $hasPreviousBatchId = Schema::hasColumn('import_batches', 'previous_batch_id');
        $hasSkippedRecords = Schema::hasColumn('import_batches', 'skipped_records');

        if ($hasImportBatchesIndex || $hasPreviousBatchId || $hasSkippedRecords) {
            Schema::table('import_batches', function (Blueprint $table) use ($hasImportBatchesIndex, $hasPreviousBatchId, $hasSkippedRecords) {
                if ($hasImportBatchesIndex) {
                    $table->dropIndex('import_batches_previous_batch_id_index');
                }

                $columnsToDrop = [];
                if ($hasPreviousBatchId) {
                    $columnsToDrop[] = 'previous_batch_id';
                }
                if ($hasSkippedRecords) {
                    $columnsToDrop[] = 'skipped_records';
                }

                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        $hasReconQueueIndex = $this->indexExists('reconciliation_queue', 'recon_queue_is_patched_index');
        $hasIsPatched = Schema::hasColumn('reconciliation_queue', 'is_patched');

        if ($hasReconQueueIndex || $hasIsPatched) {
            Schema::table('reconciliation_queue', function (Blueprint $table) use ($hasReconQueueIndex, $hasIsPatched) {
                if ($hasReconQueueIndex) {
                    $table->dropIndex('recon_queue_is_patched_index');
                }

                if ($hasIsPatched) {
                    $table->dropColumn('is_patched');
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $driver = DB::connection()->getDriverName();

            return match ($driver) {
                'mysql' => !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])),
                'pgsql' => !empty(DB::select(
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
        } catch (\Throwable $e) {
            return false;
        }
    }
};
