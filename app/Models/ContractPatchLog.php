<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ContractPatchLog
 *
 * Immutable audit record written by the Contract Patch Engine for every
 * successfully applied patch, capturing the full before/after field state
 * and the historical context that justified the change.
 *
 * @property string  $id
 * @property string  $contract_id
 * @property string  $batch_id
 * @property ?string $parent_batch_id
 * @property ?string $previous_batch_id
 * @property ?string $old_agent_code
 * @property ?string $old_agent_name
 * @property ?string $new_agent_code
 * @property ?string $new_agent_name
 * @property ?string $old_payee_name
 * @property ?string $new_payee_name
 * @property ?string $old_department
 * @property ?string $new_department
 * @property ?string $old_match_source
 * @property string  $new_match_source
 * @property ?string $flag_value
 * @property string  $change_type
 * @property ?int    $updated_by
 * @property ?string $queue_record_id
 */
class ContractPatchLog extends Model
{
    use HasUlids;

    protected $table = 'contract_patch_logs';

    /**
     * Narrow fillable surface — operator identity, change_type, and flag_value
     * are NEVER user-supplied. They are set by the ETL service via explicit
     * attribute assignment (`$model->updated_by = ...; $model->save()`),
     * never via `Model::create($request->validated())`. This guards against
     * a future controller accidentally allowing audit-trail tampering
     * (false `updated_by`, falsified `change_type=analysis_resolved`).
     *
     * If you need to add a new field that IS user-supplied, add it here.
     * If you need to set an audit field, use explicit attribute assignment
     * inside a service — see ReconciliationETLService::persistContractPatchLog.
     */
    protected $fillable = [
        'contract_id',
        'batch_id',
        'parent_batch_id',
        'previous_batch_id',
        'old_agent_code',
        'old_agent_name',
        'new_agent_code',
        'new_agent_name',
        'old_payee_name',
        'new_payee_name',
        'old_department',
        'new_department',
        'old_match_source',
        'new_match_source',
        'match_key',
        'diagnosis',
        'queue_record_id',
        // ── Audit / system-managed fields (NOT fillable) ────────────────
        // 'flag_value'   — set explicitly by patch engine
        // 'change_type'  — set explicitly by patch engine
        // 'updated_by'   — set from auth() context, never from request
    ];

    /** The contract patch ImportBatch that created this entry. */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }

    /** The parent Final BOB batch this patch targeted. */
    public function parentBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'parent_batch_id');
    }

    /** The operator who uploaded the contract patch file. */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
