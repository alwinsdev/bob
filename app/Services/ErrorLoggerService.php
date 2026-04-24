<?php

namespace App\Services;

use App\Models\SystemErrorLog;
use Illuminate\Support\Facades\Log;

class ErrorLoggerService
{
    public function log(string $level, string $source, \Throwable $e, ?array $context = [], ?int $userId = null)
    {
        try {
            SystemErrorLog::create([
                'level' => $level,
                'source' => $source,
                'message' => $e->getMessage(),
                'context' => array_merge($context ?? [], [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => substr($e->getTraceAsString(), 0, 1000)
                ]),
                'user_id' => $userId ?? auth()->id(),
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Exception $fallback) {
            Log::error("Failed to write to SystemErrorLog: " . $fallback->getMessage());
        }

        Log::log($level, "[$source] " . $e->getMessage(), $context ?? []);
    }
}
