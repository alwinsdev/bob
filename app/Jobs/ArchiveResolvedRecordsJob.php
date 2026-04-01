<?php

namespace App\Jobs;

use App\Services\ArchivalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveResolvedRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ArchivalService $archiveService): void
    {
        $count = $archiveService->archiveResolvedRecords(90);
        Log::info("Archived $count resolved records older than 90 days.");
    }
}
