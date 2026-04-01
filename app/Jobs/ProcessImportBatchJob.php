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
        public ImportBatch $batch,
        public string $filePath
    ) {}

    public function handle(ReconciliationETLService $etlService): void
    {
        if ($this->batch && $this->batch()->cancelled()) {
            return; // In case we use bus::batch
        }

        try {
            $etlService->processFile($this->filePath, $this->batch);
        } catch (\Exception $e) {
            Log::error("Failed to process batch {$this->batch->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
