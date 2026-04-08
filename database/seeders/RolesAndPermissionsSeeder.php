<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * RolesAndPermissionsSeeder
 *
 * Establishes the enterprise RBAC hierarchy for the Reconciliation module.
 *
 * Role Hierarchy (highest → lowest authority):
 *   super_admin          → Bypasses all Gate checks via Gate::before in AppServiceProvider.
 *                          DB permissions synced for auditability only.
 *   admin                → Full reconciliation management. Cannot access identity governance.
 *   Manager              → Operational management — run ETL/reanalysis, approve, export, manage lock list.
 *                          Cannot delete historical runs or access identity governance.
 *   Reconciliation_Analyst → Analyst workflow — view/edit records and consume reporting outputs.
 *                          Cannot run ETL/reanalysis, bulk approve, export, delete, or manage access.
 *
 * Permission Map:
 *   reconciliation.view              → Read access to all reconciliation screens
 *   reconciliation.edit              → Lock/unlock/resolve/flag individual records
 *   reconciliation.bulk_approve      → Bulk resolve, promote to lock list, audit logs, lock list write
 *   reconciliation.delete            → Delete batches, contract patches, lock list entries
 *   reconciliation.etl.run          → Upload new ETL runs and contract patches
 *   reconciliation.reanalysis.run   → Rerun existing batches
 *   reconciliation.results.view     → View commission dashboard, Final BOB, batch results
 *   reconciliation.export.download  → Export data to Excel/CSV/PDF, download batch output
 *   access.manage                    → Open Access Control, manage roles, permissions, user-role assignments
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions so fresh state is used
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. Create All Permissions ────────────────────────────────────────
        $permissionsList = [
            'reconciliation.view',
            'reconciliation.edit',
            'reconciliation.bulk_approve',
            'reconciliation.delete',
            'reconciliation.etl.run',
            'reconciliation.reanalysis.run',
            'reconciliation.results.view',
            'reconciliation.export.download',
            'access.manage',
        ];

        foreach ($permissionsList as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Remove known stale permissions from legacy RBAC iterations.
        foreach (['import.upload'] as $deprecatedPermissionName) {
            $deprecatedPermission = Permission::query()
                ->where('name', $deprecatedPermissionName)
                ->where('guard_name', 'web')
                ->first();

            if (!$deprecatedPermission) {
                continue;
            }

            DB::table(config('permission.table_names.role_has_permissions'))
                ->where('permission_id', $deprecatedPermission->id)
                ->delete();

            DB::table(config('permission.table_names.model_has_permissions'))
                ->where('permission_id', $deprecatedPermission->id)
                ->delete();

            $deprecatedPermission->delete();
        }

        // ── 2. Create Roles ──────────────────────────────────────────────────
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin',             'guard_name' => 'web']);
        $roleAdmin      = Role::firstOrCreate(['name' => 'admin',                   'guard_name' => 'web']);
        $roleManager    = Role::firstOrCreate(['name' => 'Manager',                 'guard_name' => 'web']);
        $roleAnalyst    = Role::firstOrCreate(['name' => 'Reconciliation_Analyst',  'guard_name' => 'web']);

        // ── 3. Assign Permissions Per Role ───────────────────────────────────

        // super_admin — all permissions
        $roleSuperAdmin->syncPermissions(Permission::all());

        // admin — full reconciliation management. NOT access.manage.
        $roleAdmin->syncPermissions([
            'reconciliation.view',
            'reconciliation.edit',
            'reconciliation.bulk_approve',
            'reconciliation.delete',
            'reconciliation.etl.run',
            'reconciliation.reanalysis.run',
            'reconciliation.results.view',
            'reconciliation.export.download',
        ]);

        // Manager — operational oversight without destructive delete.
        $roleManager->syncPermissions([
            'reconciliation.view',
            'reconciliation.edit',
            'reconciliation.bulk_approve',
            'reconciliation.etl.run',
            'reconciliation.reanalysis.run',
            'reconciliation.results.view',
            'reconciliation.export.download',
        ]);

        // Reconciliation_Analyst — review/resolve/report workflows only.
        $roleAnalyst->syncPermissions([
            'reconciliation.view',
            'reconciliation.edit',
            'reconciliation.results.view',
        ]);

        $this->command->info('✅ RBAC seeded: 9 permissions, 4 roles with proper hierarchy.');

        // ── 4. Create Demo Users ─────────────────────────────────────────────
        $analyst = User::firstOrCreate(['email' => 'analyst@bob.test'], [
            'name' => 'Data Analyst',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        $analyst->assignRole('Reconciliation_Analyst');

        $manager = User::firstOrCreate(['email' => 'manager@bob.test'], [
            'name' => 'Operations Manager',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        if (!$manager->hasRole('Manager')) {
            $manager->assignRole('Manager');
        }

        $admin = User::firstOrCreate([
            'email' => 'admin@bob.test',
        ], [
            'name' => 'Platform Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@bob.test',
        ], [
            'name' => 'Super Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }
    }
}
