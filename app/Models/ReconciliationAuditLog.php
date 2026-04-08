<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ReconciliationAuditLog extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'transaction_id',
        'action',
        'previous_values',
        'new_values',
        'previous_agent_code',
        'new_agent_code',
        'modified_by_user_id',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'previous_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by_user_id');
    }
}
