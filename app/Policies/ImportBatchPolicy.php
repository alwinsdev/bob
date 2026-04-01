<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        return $user->hasPermissionTo('import.upload');
    }
}
