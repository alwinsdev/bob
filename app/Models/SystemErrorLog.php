<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SystemErrorLog extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'level',
        'source',
        'message',
        'context',
        'user_id',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setIpAddressAttribute(mixed $value): void
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '') {
            $this->attributes['ip_address'] = null;
            return;
        }

        if (preg_match('/^[a-f0-9]{64}$/i', $normalized) === 1) {
            $this->attributes['ip_address'] = strtolower($normalized);
            return;
        }

        $this->attributes['ip_address'] = hash('sha256', $normalized);
    }

    public function setContextAttribute(mixed $value): void
    {
        if ($value === null) {
            $this->attributes['context'] = null;
            return;
        }

        $this->attributes['context'] = json_encode($this->sanitizeContext($value));
    }

    private function sanitizeContext(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sensitiveKeys = [
            'authorization',
            'cookie',
            'ip_address',
            'member_dob',
            'member_email',
            'member_phone',
            'password',
            'token',
            'user_agent',
        ];

        $sanitized = [];

        foreach ($value as $key => $item) {
            $normalizedKey = is_string($key) ? strtolower($key) : $key;

            if (is_string($normalizedKey) && in_array($normalizedKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            $sanitized[$key] = $this->sanitizeContext($item);
        }

        return $sanitized;
    }
}
