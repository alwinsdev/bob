<?php

namespace App\Services;

use App\Models\ReconciliationQueue;

class ArchivalService
{
    public function archiveResolvedRecords(int $daysThreshold = 90): int
    {
        $cutoffDate = now()->subDays($daysThreshold);

        $count = ReconciliationQueue::where('status', 'resolved')
            ->where('resolved_at', '<', $cutoffDate)
            ->update(['archived_at' => now()]);

        return $count;
    }
}
