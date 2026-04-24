<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\ReconciliationETLService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessImportBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Retry at most 3 total attempts. */
    public int $tries = 3;

    /** Maximum wall-clock time for a single attempt. */
    public $timeout = 3600; // 1 hr max

    /**
     * Retry up to 3 times total before hard-failing.
     * Transient failures (DB timeout, memory spike) should resolve on retry.
     */
    public int $maxExceptions = 3;

    /**
     * Exponential backoff between attempts: 1 min → 5 min → 15 min.
     * Gives the system time to recover from transient overload conditions.
     *
     * @var array<int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public ImportBatch $batch
    ) {}

    public function handle(ReconciliationETLService $etlService): void
    {
        $jobBatch = $this->batch();

        if ($jobBatch && $jobBatch->cancelled()) {
            return; // In case we use bus::batch
        }

        try {
            $etlService->processBatch($this->batch);
        } catch (\Throwable $e) {
            Log::error("Failed to process batch {$this->batch->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $message = $exception->getMessage();
        $isUserDomainError = str_starts_with($message, 'Invalid Format:') || str_starts_with($message, 'Missing Required');
        $userFriendlyMessage = $isUserDomainError 
            ? $message 
            : 'A system error occurred during ETL processing. Please try again or contact support if the issue persists.';

        $this->batch->update([
            'status'        => 'failed',
            'error_message' => $userFriendlyMessage,
        ]);
        
        Log::error("[Enterprise] Batch Job Hard Failed: ID {$this->batch->id}. Error: " . $exception->getMessage());
    }
}
