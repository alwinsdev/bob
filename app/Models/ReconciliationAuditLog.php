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

    public function setIpAddressAttribute(mixed $value): void
    {
        $this->attributes['ip_address'] = $this->hashSensitiveValue($value);
    }

    public function setUserAgentAttribute(mixed $value): void
    {
        $this->attributes['user_agent'] = $this->hashSensitiveValue($value);
    }

    public function setPreviousValuesAttribute(mixed $value): void
    {
        $this->attributes['previous_values'] = $this->encodeSanitizedPayload($value);
    }

    public function setNewValuesAttribute(mixed $value): void
    {
        $this->attributes['new_values'] = $this->encodeSanitizedPayload($value);
    }

    private function hashSensitiveValue(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^[a-f0-9]{64}$/i', $normalized) === 1) {
            return strtolower($normalized);
        }

        return hash('sha256', $normalized);
    }

    private function encodeSanitizedPayload(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        $sanitized = $this->sanitizePayload($value);

        return json_encode($sanitized);
    }

    private function sanitizePayload(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sensitiveKeys = [
            'client_email',
            'client_phone',
            'ip_address',
            'member_dob',
            'member_email',
            'member_phone',
            'user_agent',
        ];

        $sanitized = [];

        foreach ($value as $key => $item) {
            $normalizedKey = is_string($key) ? strtolower($key) : $key;

            if (is_string($normalizedKey) && in_array($normalizedKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            $sanitized[$key] = $this->sanitizePayload($item);
        }

        return $sanitized;
    }
}
