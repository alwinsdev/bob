<?php

namespace App\Providers;

use App\Models\ImportBatch;
use App\Models\LockList;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationQueue;
use App\Models\User;
use App\Policies\ImportBatchPolicy;
use App\Policies\LockListPolicy;
use App\Policies\ReconciliationQueuePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schedule;
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

        // ── Config Validation ───────────────────────────────────────────────
        // Prevent orphaned records by ensuring the designated system user actually exists.
        // We skip this check during tests and console commands (e.g., migrations) to avoid breaking the boot sequence.
        if (!app()->runningInConsole() && !app()->environment('testing')) {
            $systemUserId = config('reconciliation.system_user_id');
            if ($systemUserId && !User::where('id', $systemUserId)->exists()) {
                throw new \RuntimeException(sprintf('Invalid SYSTEM_USER_ID configured: %s does not exist in the database.', $systemUserId));
            }
        }

        // ── Data Retention Schedule ─────────────────────────────────────────
        // Purge reconciliation audit log entries older than 6 months.
        // This enforces a data lifecycle policy and limits unbounded log growth.
        // withoutOverlapping() prevents double-execution on slow servers.
        Schedule::call(function () {
            ReconciliationAuditLog::where('created_at', '<', now()->subMonths(6))->delete();
        })->monthly()->name('cleanup-old-audit-logs')->withoutOverlapping();
    }
}
