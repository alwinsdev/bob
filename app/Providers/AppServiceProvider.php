<?php

namespace App\Providers;

use App\Models\ImportBatch;
use App\Models\LockList;
use App\Models\ReconciliationQueue;
use App\Models\User;
use App\Policies\ImportBatchPolicy;
use App\Policies\LockListPolicy;
use App\Policies\ReconciliationQueuePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Super Admin Gate Bypass ─────────────────────────────────
        // Users with the super_admin role bypass ALL authorization
        // checks. Returning `true` grants access; returning `null`
        // allows the normal policy/gate logic to proceed.
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        // ── Explicit Policy Registration ────────────────────────────
        // Registered explicitly rather than relying on auto-discovery
        // so that the binding is guaranteed regardless of folder structure.
        Gate::policy(ReconciliationQueue::class, ReconciliationQueuePolicy::class);
        Gate::policy(ImportBatch::class, ImportBatchPolicy::class);
        Gate::policy(LockList::class, LockListPolicy::class);
    }
}
