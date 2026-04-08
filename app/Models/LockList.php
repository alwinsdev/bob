<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * LockList — "Final Authority" entries for the Locklist Override stage.
 *
 * A match on policy_id forces department_name, agent_name, and payee_name
 * onto the BOB record, overriding any IMS or Health Sherpa assignment.
 */
class LockList extends Model
{
    use HasFactory;

    protected $table = 'lock_lists';

    protected $fillable = [
        'policy_id',
        'agent_name',
        'department',
        'payee_name',
        'promoted_from_batch_id',
        'promoted_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** The batch this entry was promoted from (if applicable). */
    public function promotedFromBatch()
    {
        return $this->belongsTo(ImportBatch::class, 'promoted_from_batch_id');
    }

    /** The user who promoted this entry. */
    public function promotedBy()
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }
}
