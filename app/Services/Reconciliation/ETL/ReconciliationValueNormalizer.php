<?php

namespace App\Services\Reconciliation\ETL;

class ReconciliationValueNormalizer
{
    public function string(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    public function phone(mixed $value): string
    {
        return preg_replace('/[^0-9]/', '', (string) $value) ?? '';
    }

    public function date(mixed $value): string
    {
        if (!$value) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return '';
        }

        if (preg_match('/^\d{2}\s\d{2}\s\d{2}$/', $stringValue)) {
            $date = \DateTime::createFromFormat('d m y', $stringValue);
            if ($date) {
                return $date->format('Y-m-d');
            }
        } elseif (preg_match('/^\d{2}\s\d{2}\s\d{4}$/', $stringValue)) {
            $date = \DateTime::createFromFormat('d m Y', $stringValue);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($stringValue);

        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    public function patchId(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = trim((string) $value);

        if (preg_match('/^[0-9.]+E\+[0-9]+$/i', $normalized)) {
            $normalized = (string) (float) $normalized;
        }

        if (str_contains($normalized, '.')) {
            $parts = explode('.', $normalized);
            if (isset($parts[1]) && in_array($parts[1], ['0', '00'], true)) {
                $normalized = $parts[0];
            }
        }

        return $normalized;
    }

    public function headerKey(string $header): string
    {
        $key = strtolower(trim($header));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';

        return trim($key, '_');
    }

    public function extractColumnValue(array $row, ?string $column): string
    {
        if (!$column) {
            return '';
        }

        return trim((string) ($row[$column] ?? ''));
    }

    public function flagValue(?string $raw): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        $normalized = strtolower($value);
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return match ($normalized) {
            'home open',
            'house open' => 'House Open',
            'home close',
            'house close' => 'House Close',
            default => null,
        };
    }
}
