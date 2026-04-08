<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

/**
 * ContractPatchPolicy
 *
 * Authorization for the contract_patch subset of ImportBatch records.
 * Registered separately from ImportBatchPolicy for semantic clarity —
 * the two batch types have distinct permission requirements.
 *
 * Previously ContractPatchController::download() had ZERO authorization.
 * This policy, enforced via route middleware, closes that gap.
 */
class ContractPatchPolicy
{
    /**
     * Upload/start a new contract patch run — requires ETL authority.
     */
    public function store(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.etl.run');
    }

    /**
     * Download the output file of a contract patch run.
     * Previously unprotected — any authenticated user could download.
     */
    public function download(User $user, ImportBatch $batch): bool
    {
        return $user->hasPermissionTo('reconciliation.export.download');
    }

    /**
     * Delete a contract patch run — requires the destructive permission.
     */
    public function delete(User $user, ImportBatch $batch): bool
    {
        return $user->hasPermissionTo('reconciliation.delete');
    }
}
