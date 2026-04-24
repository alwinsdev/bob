<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportBatch extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'parent_batch_id',
        'previous_batch_id',
        'retry_of_batch_id',
        'retry_group_id',
        'attempt_no',
        'retry_reason',
        'batch_type',
        'sync_strategy',
        'carrier_file_path',
        'carrier_original_name',
        'ims_file_path',
        'ims_original_name',
        'payee_file_path',
        'payee_original_name',
        'health_sherpa_file_path',
        'health_sherpa_original_name',
        'contract_file_path',
        'contract_original_name',
        'contract_patched_records',
        'output_file_path',
        'total_records',
        'processed_records',
        'failed_records',
        'skipped_records',
        'skipped_duplicates',
        'ims_matched_records',
        'hs_matched_records',
        'locklist_matched_records',
        'status',
        'error_message',
        'duplicate_strategy',
        'uploaded_by',
        'skipped_summary',
        'failure_summary',
    ];

    protected $casts = [
        'skipped_summary' => 'array',
        'failure_summary' => 'array',
        'attempt_no' => 'integer',
    ];

    /** Convenience: is this a mid-week contract patch run? */
    public function isContractPatch(): bool
    {
        return $this->batch_type === 'contract_patch';
    }

    /** Convenience: true when this run is a retry attempt. */
    public function isRetryAttempt(): bool
    {
        return !blank($this->retry_of_batch_id) || ($this->attempt_no ?? 1) > 1;
    }

    /**
     * Cascade cleanup of child records when a batch is deleted.
     *
     * - ReconciliationQueue rows are soft-deleted (preserves historical data integrity
     *   while removing them from active queues).
     * - ImportRowError rows are hard-deleted (no historical value; safe to purge).
     *
     * This prevents silent data orphaning when batches are removed via the UI.
     */
    protected static function booted(): void
    {
        static::deleting(function (ImportBatch $batch) {
            try {
                // Soft-delete reconciliation records (preserves audit trail)
                ReconciliationQueue::where('import_batch_id', $batch->id)->delete();

                // Hard-delete row-level errors (no retention value)
                ImportRowError::where('import_batch_id', $batch->id)->forceDelete();
            } catch (\Throwable $e) {
                Log::error("[ImportBatch] Failed to clean up child records for batch {$batch->id}: " . $e->getMessage());
                // Re-throw so the deletion is rolled back rather than leaving partial orphans
                throw $e;
            }
        });
    }

    public function hasOutput(): bool
    {
        if (blank($this->output_file_path)) {
            return false;
        }

        return Storage::disk('local')->exists($this->output_file_path);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The parent standard batch this contract patch belongs to.
     * Null for standard/top-level batches.
     */
    public function parentBatch()
    {
        return $this->belongsTo(ImportBatch::class, 'parent_batch_id');
    }

    /**
     * The previous completed batch used as historical source of truth
     * for contract patch flag resolution.
     */
    public function previousBatch()
    {
        return $this->belongsTo(ImportBatch::class, 'previous_batch_id');
    }

    /**
     * The failed/previous run that this retry attempt was based on.
     */
    public function retrySourceBatch()
    {
        return $this->belongsTo(ImportBatch::class, 'retry_of_batch_id');
    }

    /**
     * All retry attempts that point back to this run.
     */
    public function retryAttempts()
    {
        return $this->hasMany(ImportBatch::class, 'retry_of_batch_id')->orderBy('created_at', 'desc');
    }

    /**
     * Contract patch runs that have been attached to this standard batch.
     */
    public function childPatches()
    {
        return $this->hasMany(ImportBatch::class, 'parent_batch_id')->orderBy('created_at', 'desc');
    }

    /**
     * Audit log entries created by contract patch runs on this batch.
     */
    public function contractPatchLogs()
    {
        return $this->hasMany(ContractPatchLog::class, 'batch_id');
    }

    public function reconciliationRecords()
    {
        return $this->hasMany(ReconciliationQueue::class, 'import_batch_id');
    }

    public function rowErrors()
    {
        return $this->hasMany(ImportRowError::class, 'import_batch_id');
    }

    /**
     * Only top-level batches (standard runs, not nested contract patches).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_batch_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    public function scopeCompletedWithErrors($query)
    {
        return $query->where('status', 'completed_with_errors');
    }
}
