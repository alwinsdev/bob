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

    public $timeout = 3600; // 1 hr max, consistent with standard batch job

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
            $this->fail($e);
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
