<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'file_name',
        'original_name',
        'type',
        'total_records',
        'processed_records',
        'failed_records',
        'skipped_duplicates',
        'status',
        'error_message',
        'duplicate_strategy',
        'uploaded_by',
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reconciliationRecords()
    {
        return $this->hasMany(ReconciliationQueue::class, 'import_batch_id');
    }

    public function rowErrors()
    {
        return $this->hasMany(ImportRowError::class, 'import_batch_id');
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeProcessing($query) { return $query->where('status', 'processing'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeFailed($query) { return $query->where('status', 'failed'); }
    public function scopeCompletedWithErrors($query) { return $query->where('status', 'completed_with_errors'); }
}
