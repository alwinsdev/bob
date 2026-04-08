<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReconciliationQueue;

/**
 * ReconciliationQueuePolicy
 *
 * Canonical authorization logic for all ReconciliationQueue operations.
 * Every method maps to a single Spatie permission string.
 */
class ReconciliationQueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    public function view(User $user, ReconciliationQueue $record): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    public function update(User $user, ReconciliationQueue $record): bool
    {
        return $user->hasPermissionTo('reconciliation.edit');
    }

    public function lock(User $user, ReconciliationQueue $record): bool
    {
        return $user->hasPermissionTo('reconciliation.edit');
    }

    public function bulkApprove(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.bulk_approve');
    }

    /**
     * Promote a record to the Lock List — requires bulk approval authority.
     */
    public function promote(User $user, ReconciliationQueue $record): bool
    {
        return $user->hasPermissionTo('reconciliation.bulk_approve');
    }

    /**
     * Delete a reconciliation record — requires destructive permission.
     */
    public function delete(User $user, ReconciliationQueue $record): bool
    {
        return $user->hasPermissionTo('reconciliation.delete');
    }
}
