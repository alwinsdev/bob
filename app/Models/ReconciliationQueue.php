<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReconciliationQueue extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'reconciliation_queue';

    // Only Column C + lock/status fields are fillable via request.
    // The BOB immutable fields are intentionally kept out of general fillable updates
    // but handled via explicit creation during ETL.
    protected $fillable = [
        'import_batch_id',
        'transaction_id',
        'status',
        'aligned_agent_code',
        'aligned_agent_name',
        'group_team_sales',
        'payee_name',
        'compensation_type',
        'locked_by',
        'locked_at',
        'resolved_by',
        'resolved_at',
        'archived_at',
        'match_confidence',
        'match_method',
        'field_scores',
        'carrier',
        'contract_id',
        'product',
        'member_first_name',
        'member_last_name',
        'member_dob',
        'member_email',
        'member_phone',
        'effective_date',
        'ims_transaction_id',
        'client_first_name',
        'client_last_name',
        'client_email',
        'client_phone',
        'agent_id',
        'agent_first_name',
    ];

    protected function casts(): array
    {
        return [
            'member_dob' => 'encrypted',
            'member_phone' => 'encrypted',
            'match_confidence' => 'decimal:2',
            'field_scores' => 'array',
            'locked_at' => 'datetime',
            'resolved_at' => 'datetime',
            'archived_at' => 'datetime',
            'effective_date' => 'date',
        ];
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(ReconciliationAuditLog::class, 'transaction_id', 'transaction_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'aligned_agent_code', 'agent_code');
    }

    // Scopes
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeResolved($query) { return $query->where('status', 'resolved'); }
    public function scopeFlagged($query) { return $query->where('status', 'flagged'); }
    public function scopeUnmatched($query) { return $query->whereNull('match_confidence')->orWhere('match_confidence', '<', 90); }
    public function scopeLocked($query) { return $query->whereNotNull('locked_at'); }
    public function scopeNotArchived($query) { return $query->whereNull('archived_at'); }
    public function scopeArchived($query) { return $query->whereNotNull('archived_at'); }

    // Lock Handling helpers
    public function isLockedByOther(?User $user)
    {
        if (!$this->locked_by) return false;
        if (!$user) return true;
        return $this->locked_by !== $user->id;
    }

    public function acquireLock(User $user)
    {
        $this->update([
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);
        return true;
    }

    public function releaseLock()
    {
        return $this->update([
            'locked_by' => null,
            'locked_at' => null,
        ]);
    }
}
