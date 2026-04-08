<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReconciliationQueue extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    private const AUTO_REVIEW_THRESHOLD = 90.0;

    protected $table = 'reconciliation_queue';

    // Only Column C + lock/status fields are fillable via request.
    // The BOB immutable fields are intentionally kept out of general fillable updates
    // but handled via explicit creation during ETL.
    protected $fillable = [
        'import_batch_id',
        'transaction_id',
        'status',
        'flag_value',
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
        'override_flag',
        'override_source',
        'is_patched',
        'original_agent_name',
        'original_match_method',
    ];

    protected $appends = [
        'match_confidence_percent',
        'match_confidence_bucket',
        'match_method_label',
        'score_reason_short',
        'score_reason',
        'score_signal_summary',
    ];

    protected function casts(): array
    {
        return [
            'member_dob' => 'encrypted',
            'member_phone' => 'encrypted',
            'member_email' => 'encrypted',
            'match_confidence' => 'decimal:2',
            'field_scores' => 'array',
            'override_flag' => 'boolean',
            'locked_at' => 'datetime',
            'resolved_at' => 'datetime',
            'archived_at' => 'datetime',
            'effective_date' => 'date',
        ];
    }

    public function getMatchConfidencePercentAttribute(): ?float
    {
        if ($this->match_confidence === null) {
            return null;
        }

        $value = (float) $this->match_confidence;
        return max(0.0, min(100.0, $value));
    }

    public function getMatchConfidenceBucketAttribute(): string
    {
        $score = $this->match_confidence_percent;

        if ($score === null) {
            return 'none';
        }

        if ($score >= self::AUTO_REVIEW_THRESHOLD) {
            return 'high';
        }

        if ($score >= 70.0) {
            return 'medium';
        }

        return 'low';
    }

    public function getMatchMethodLabelAttribute(): string
    {
        $methodRaw = trim((string) $this->match_method);
        if ($methodRaw === '') {
            $status = Str::lower((string) $this->status);
            if (in_array($status, ['pending', 'flagged'], true)) {
                return 'No deterministic match';
            }

            return 'No confident signal';
        }

        $method = Str::lower($methodRaw);

        return match (true) {
            $method === 'ims:email' => 'IMS Email Exact',
            $method === 'ims:phone' => 'IMS Phone Exact',
            $method === 'ims:firstlastname' => 'IMS First + Last Name',
            $method === 'ims:dob+lastname' => 'IMS DOB + Last Name',
            $method === 'hs:email' => 'Health Sherpa Email Exact',
            str_contains($method, 'hs:phone+date') => 'Health Sherpa Phone + Effective Date',
            $method === 'locklist override' => 'Lock List Override',
            $method === 'email' => 'Email Similarity',
            $method === 'phone' => 'Phone Similarity',
            $method === 'name' => 'Name Similarity',
            $method === 'dob' => 'DOB Similarity',
            default => $methodRaw,
        };
    }

    public function getScoreSignalSummaryAttribute(): ?string
    {
        $signals = $this->normalizedFieldScores();
        if (!empty($signals)) {
            $strongest = $signals[0];
            $weakest = $signals[count($signals) - 1];

            if ($strongest['field'] === $weakest['field']) {
                return sprintf('Signal strength: %s %.2f%%.', $strongest['field'], $strongest['score']);
            }

            return sprintf(
                'Strongest signal: %s %.2f%%; weakest signal: %s %.2f%%.',
                $strongest['field'],
                $strongest['score'],
                $weakest['field'],
                $weakest['score']
            );
        }

        $status = Str::lower((string) $this->status);
        if (!in_array($status, ['pending', 'flagged'], true)) {
            return null;
        }

        $diagnostics = $this->deterministicMatchDiagnostics();
        $parts = [];

        if (!empty($diagnostics['checked'])) {
            $parts[] = 'Checked keys: ' . implode(', ', $diagnostics['checked']) . '.';
        } else {
            $parts[] = 'No valid deterministic keys were available to run automated matching.';
        }

        if (!empty($diagnostics['blocked'])) {
            $parts[] = 'Unavailable checks: ' . implode('; ', $diagnostics['blocked']) . '.';
        }

        if (!empty($this->contract_id)) {
            $parts[] = "Contract {$this->contract_id} has no Lock List override.";
        }

        return implode(' ', $parts);
    }

    public function getScoreReasonShortAttribute(): string
    {
        $status = Str::lower((string) $this->status);
        $score = $this->match_confidence_percent;

        if ($status === 'flagged') {
            if ($score === null) {
                $diagnostics = $this->deterministicMatchDiagnostics();
                if (empty($diagnostics['checked'])) {
                    return 'Flagged: required identifiers are missing for automated matching.';
                }

                return 'Flagged: no IMS or Health Sherpa record matched the available identifiers.';
            }
            if ($score < 70.0) {
                return 'Flagged: low-confidence match needs manual verification.';
            }
            if ($score < self::AUTO_REVIEW_THRESHOLD) {
                return 'Flagged: below 90% confidence threshold.';
            }
            return 'Flagged by workflow policy for analyst validation.';
        }

        if ($status === 'pending') {
            if ($score === null) {
                $diagnostics = $this->deterministicMatchDiagnostics();
                if (empty($diagnostics['checked'])) {
                    return 'Pending: automated matching could not run due to missing required identifiers.';
                }

                return 'Pending: no IMS or Health Sherpa record matched the available identifiers.';
            }
            if ($score < self::AUTO_REVIEW_THRESHOLD) {
                return 'Pending: confidence below auto-review threshold.';
            }
            return 'Pending analyst confirmation before resolution.';
        }

        if ($status === 'matched') {
            return 'Matched: confidence is sufficient for suggested alignment.';
        }

        if ($status === 'resolved') {
            return 'Resolved after analyst action.';
        }

        return 'Review record details before taking action.';
    }

    public function getScoreReasonAttribute(): string
    {
        $summary = $this->score_reason_short;
        $method = $this->match_method_label;
        $signalSummary = $this->score_signal_summary;

        $parts = [$summary, "Method: {$method}."];

        if ($signalSummary) {
            $parts[] = $signalSummary;
        }

        return implode(' ', $parts);
    }

    private function normalizedFieldScores(): array
    {
        $rawScores = is_array($this->field_scores) ? $this->field_scores : [];
        $normalized = [];

        foreach ($rawScores as $field => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $score = (float) $value;
            // Legacy payloads store scores as 0-1 decimals. Newer payloads may store 0-100.
            if ($score <= 1.0) {
                $score *= 100.0;
            }

            $score = max(0.0, min(100.0, $score));

            $normalized[] = [
                'field' => $this->humanizeScoreField((string) $field),
                'score' => $score,
            ];
        }

        usort($normalized, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $normalized;
    }

    private function deterministicMatchDiagnostics(): array
    {
        $checked = [];
        $blocked = [];

        $email = trim((string) ($this->member_email ?? ''));
        $phone = $this->normalizePhone((string) ($this->member_phone ?? ''));
        $firstName = trim((string) ($this->member_first_name ?? ''));
        $lastName = trim((string) ($this->member_last_name ?? ''));
        $dob = trim((string) ($this->member_dob ?? ''));
        $effectiveDate = trim((string) ($this->effective_date ?? ''));

        if ($email !== '') {
            $checked[] = 'Email exact (IMS / Health Sherpa)';
        } else {
            $blocked[] = 'Email is missing';
        }

        if ($phone !== '') {
            $checked[] = 'Phone exact (IMS)';
        } else {
            $blocked[] = 'Phone number is missing';
        }

        if ($firstName !== '' && $lastName !== '') {
            $checked[] = 'First + Last Name (IMS)';
        } elseif ($firstName !== '' || $lastName !== '') {
            $blocked[] = 'Name rule needs both first and last name';
        } else {
            $blocked[] = 'First and last name are missing';
        }

        if ($dob !== '' && $lastName !== '') {
            $checked[] = 'DOB + Last Name (IMS)';
        } elseif ($dob !== '' && $lastName === '') {
            $blocked[] = 'DOB is present but last name is missing for DOB+Last Name rule';
        }

        if ($phone !== '' && $effectiveDate !== '') {
            $checked[] = 'Phone + Effective Date (Health Sherpa ±30d)';
        } elseif ($phone !== '' && $effectiveDate === '') {
            $blocked[] = 'Effective date is missing for Health Sherpa phone+date rule';
        }

        return [
            'checked' => array_values(array_unique($checked)),
            'blocked' => array_values(array_unique($blocked)),
        ];
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value) ?? '';
    }

    private function humanizeScoreField(string $field): string
    {
        return match (Str::snake($field)) {
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'full_name' => 'Full Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'dob' => 'Date of Birth',
            default => Str::title(str_replace('_', ' ', $field)),
        };
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
