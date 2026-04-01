<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReconciliationQueue;
use Illuminate\Auth\Access\Response;

class ReconciliationQueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    public function view(User $user, ReconciliationQueue $reconciliationQueue): bool
    {
        return $user->hasPermissionTo('reconciliation.view');
    }

    public function update(User $user, ReconciliationQueue $reconciliationQueue): bool
    {
        return $user->hasPermissionTo('reconciliation.edit');
    }

    public function lock(User $user, ReconciliationQueue $reconciliationQueue): bool
    {
        return $user->hasPermissionTo('reconciliation.edit');
    }

    public function bulkApprove(User $user): bool
    {
        return $user->hasPermissionTo('reconciliation.bulk_approve');
    }
}
