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

/**
 * Job to process a mid-week Contract File patch batch asynchronously.
 * Delegates to ReconciliationETLService::processContractPatch().
 */
class ProcessContractPatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Retry at most 3 total attempts. */
    public int $tries = 3;

    /** Maximum wall-clock time for a single attempt. */
    public $timeout = 3600; // 1 hr max, consistent with standard batch job

    /**
     * Retry up to 3 times total before hard-failing.
     * Transient failures (DB timeout, memory spike) should resolve on retry.
     */
    public int $maxExceptions = 3;

    /**
     * Exponential backoff between attempts: 1 min → 5 min → 15 min.
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
            return;
        }

        try {
            $etlService->processContractPatch($this->batch);
        } catch (\Throwable $e) {
            Log::error("[ContractPatch] Failed to process batch {$this->batch->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $message = $exception->getMessage();
        $userFriendlyMessage = str_starts_with($message, 'Invalid Format:') 
            ? $message 
            : 'A system error occurred during contract patch processing. Please try again or contact support if the issue persists.';

        $this->batch->update([
            'status'        => 'failed',
            'error_message' => $userFriendlyMessage,
        ]);

        Log::error("[ContractPatch] Batch Job Hard Failed: ID {$this->batch->id}. Error: " . $exception->getMessage());
    }
}
