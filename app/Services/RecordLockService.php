<?php

namespace App\Services;

use App\Models\ReconciliationQueue;
use App\Models\User;
use Carbon\Carbon;

class RecordLockService
{
    private int $lockTimeoutMinutes = 30;

    public function acquire(ReconciliationQueue $record, User $user): bool
    {
        if ($record->isLockedByOther($user)) {
             if (Carbon::parse($record->locked_at)->diffInMinutes(now()) > $this->lockTimeoutMinutes) {
                 // expired, steal lock
                 $record->acquireLock($user);
                 return true;
             }
             return false;
        }

        $record->acquireLock($user);
        return true;
    }

    public function release(ReconciliationQueue $record, User $user): bool
    {
        if ($record->locked_by === $user->id) {
            $record->releaseLock();
            return true;
        }
        return false;
    }

    public function releaseExpired()
    {
        $expiredThreshold = now()->subMinutes($this->lockTimeoutMinutes);
        return ReconciliationQueue::whereNotNull('locked_at')
            ->where('locked_at', '<', $expiredThreshold)
            ->update(['locked_by' => null, 'locked_at' => null]);
    }
}
