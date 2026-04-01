<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ImportRowError extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw_data',
        'error_type',
        'error_messages',
        'field_name',
        'is_retryable',
        'retried_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'error_messages' => 'array',
            'is_retryable' => 'boolean',
            'retried_at' => 'datetime',
        ];
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
