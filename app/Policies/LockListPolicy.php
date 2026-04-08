<?php

namespace App\Policies;

use App\Models\LockList;
use App\Models\User;

/**
 * LockListPolicy
 *
 * Canonical authorization logic for all LockList CRUD, import and export operations.
 *
 * Previously these checks were scattered across:
 *  - A private 'authorizeManagerOrAdmin()' helper in LockListController
 *  - ReconciliationQueue's bulkApprove policy (wrong model context)
 *
 * All authorization now lives here, registered in AppServiceProvider.
 */
class LockListPolicy
{
    /**
     * Any user with view access can browse the lock list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    /**
     * View a single lock list entry.
     */
    public function view(User $user, LockList $lockList): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    /**
     * Manually create a lock list entry — requires bulk approval authority.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.bulk_approve');
    }

    /**
     * Edit an existing lock list entry — requires bulk approval authority.
     */
    public function update(User $user, LockList $lockList): bool
    {
        return $user->hasPermissionTo('reconciliation.bulk_approve');
    }

    /**
     * Delete an entry — requires the explicit destructive permission.
     * Separated from update so delete can be restricted without removing edit access.
     */
    public function delete(User $user, LockList $lockList): bool
    {
        return $user->hasPermissionTo('reconciliation.delete');
    }

    /**
     * Bulk import a lock list from file — requires bulk approval authority.
     */
    public function import(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.bulk_approve');
    }

    /**
     * Export the lock list — requires the export permission.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.export.download');
    }
}
