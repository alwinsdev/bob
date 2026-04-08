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

    public $timeout = 3600; // 1 hr max

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
            $this->fail($e);
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
