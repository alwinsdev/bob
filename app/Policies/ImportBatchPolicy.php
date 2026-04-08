<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

/**
 * ImportBatchPolicy
 *
 * Canonical authorization logic for all ImportBatch (standard ETL run) operations.
 * Uses hasPermissionTo() which leverages Spatie's internal permission cache
 * rather than getAllPermissions()->contains() which bypasses the cache.
 */
class ImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    public function view(User $user, ImportBatch $importBatch): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.etl.run');
    }

    public function rerun(User $user, ImportBatch $importBatch): bool
    {
        return $user->hasPermissionTo('reconciliation.reanalysis.run');
    }

    public function viewResults(User $user, ImportBatch $importBatch): bool
    {
        return $user->hasPermissionTo('reconciliation.results.view');
    }

    public function downloadOutput(User $user, ImportBatch $importBatch): bool
    {
        return $user->hasPermissionTo('reconciliation.export.download');
    }

    public function delete(User $user, ImportBatch $importBatch): bool
    {
        return $user->hasPermissionTo('reconciliation.delete');
    }
}
