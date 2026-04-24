<?php

namespace App\Services;

use App\Models\ReconciliationQueue;
use App\Models\User;
use Carbon\Carbon;

class RecordLockService
{
    /**
     * Lock timeout in minutes. Read from config so different deployments
     * can tune the value without code changes.
     */
    private int $lockTimeoutMinutes;

    public function __construct()
    {
        $this->lockTimeoutMinutes = (int) config('reconciliation.lock_timeout_minutes', 30);
    }

    public function acquire(ReconciliationQueue $record, User $user): bool
    {
        if ($record->isLockedByOther($user)) {
            // Allow lock acquisition if the existing lock has expired
            if ($this->isLockExpired($record)) {
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

    public function releaseExpired(): int
    {
        $expiredThreshold = now()->subMinutes($this->lockTimeoutMinutes);
        return ReconciliationQueue::whereNotNull('locked_at')
            ->where('locked_at', '<', $expiredThreshold)
            ->update(['locked_by' => null, 'locked_at' => null]);
    }

    /**
     * Determine if a record's lock has expired.
     *
     * A 1-minute grace period is included to account for minor clock skew
     * between application servers. This prevents a lock from appearing expired
     * on one node while still valid on another.
     */
    private function isLockExpired(ReconciliationQueue $record): bool
    {
        if (!$record->locked_at) {
            return true;
        }

        $elapsed = Carbon::parse($record->locked_at)->diffInMinutes(now());

        // Grace period: 1 extra minute on top of configured timeout
        return $elapsed > ($this->lockTimeoutMinutes + 1);
    }
}
