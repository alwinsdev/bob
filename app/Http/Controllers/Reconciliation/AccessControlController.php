<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignUserRolesRequest;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlController extends Controller
{
    private const SUPER_ADMIN_ROLE = 'super_admin';

    public function index(Request $request)
    {
        $actor = $request->user();
        $isSuperAdmin = $this->isSuperAdminUser($actor);

        $rolesQuery = Role::query()
            ->with(['permissions:id,name', 'users:id'])
            ->orderBy('name');

        if (!$isSuperAdmin) {
            $rolesQuery->whereRaw('LOWER(name) <> ?', [self::SUPER_ADMIN_ROLE]);
        }

        $assignableRolesQuery = Role::query()->orderBy('name');
        if (!$isSuperAdmin) {
            $assignableRolesQuery->whereRaw('LOWER(name) <> ?', [self::SUPER_ADMIN_ROLE]);
        }

        $usersQuery = User::query()
            ->with('roles:id,name')
            ->orderBy('name');

        if (!$isSuperAdmin) {
            $usersQuery->whereDoesntHave('roles', function ($query) {
                $query->whereRaw('LOWER(name) = ?', [self::SUPER_ADMIN_ROLE]);
            });
        }

        $roles         = $rolesQuery->get();
        $permissions   = Permission::query()->orderBy('name')->get(['id', 'name']);
        $allRoles      = Role::query()->with('permissions:id,name')->orderBy('name')->get();

        return view('reconciliation.access-control', [
            'roles'               => $roles,
            'permissions'         => $permissions,
            'assignableRoles'     => $assignableRolesQuery->get(['id', 'name']),
            'users'               => $usersQuery->get(['id', 'name', 'email']),
            'isSuperAdmin'        => $isSuperAdmin,
            'superAdminRoleName'  => self::SUPER_ADMIN_ROLE,
            'screenAccessMatrix'  => $this->buildScreenAccessMatrix($allRoles),
            'roleHierarchy'       => $this->buildRoleHierarchy($allRoles),
        ]);
    }

    public function storeRole(CreateRoleRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $isSuperAdmin = $this->isSuperAdminUser($actor);

        $normalizedRoleName = Str::of((string) $request->validated('name'))
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->value();

        if ($this->isSuperAdminRoleName($normalizedRoleName) && !$isSuperAdmin) {
            abort(403, 'Only super administrators can create the super_admin role.');
        }

        $alreadyExists = Role::query()
            ->whereRaw('LOWER(name) = ?', [$normalizedRoleName])
            ->exists();

        if ($alreadyExists) {
            return redirect()->route('reconciliation.access-control.index')
                ->withErrors(['name' => 'Role already exists. Choose a different role name.'])
                ->withInput();
        }

        $role = Role::query()->create([
            'name' => $normalizedRoleName,
            'guard_name' => 'web',
        ]);

        $permissionNames = $this->normalizePermissionNames($request->validated('permission_names', []));
        $role->syncPermissions($permissionNames);

        return redirect()->route('reconciliation.access-control.index')
            ->with('status', "Role '{$role->name}' was created successfully.");
    }

    public function syncRolePermissions(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $actor = $request->user();
        $isSuperAdmin = $this->isSuperAdminUser($actor);

        if ($this->isSuperAdminRoleName($role->name) && !$isSuperAdmin) {
            abort(403, 'Only super administrators can modify the super_admin role.');
        }

        $permissionNames = $this->normalizePermissionNames($request->validated('permission_names', []));
        $role->syncPermissions($permissionNames);

        return redirect()->route('reconciliation.access-control.index')
            ->with('status', "Permissions updated for role '{$role->name}'.");
    }

    public function syncUserRoles(AssignUserRolesRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $isSuperAdminActor = $this->isSuperAdminUser($actor);

        $requestedRoles = collect($request->validated('role_names', []))
            ->filter(fn ($roleName) => is_string($roleName) && trim($roleName) !== '')
            ->map(fn (string $roleName) => trim($roleName))
            ->unique()
            ->values();

        $targetIsSuperAdmin = $this->isSuperAdminUser($user);
        $assignsSuperAdmin = $requestedRoles
            ->contains(fn (string $roleName) => $this->isSuperAdminRoleName($roleName));

        if (!$isSuperAdminActor && $targetIsSuperAdmin) {
            abort(403, 'Only super administrators can modify users with super_admin access.');
        }

        if (!$isSuperAdminActor && $assignsSuperAdmin) {
            abort(403, 'Only super administrators can assign the super_admin role.');
        }

        if ($targetIsSuperAdmin && !$assignsSuperAdmin) {
            $superAdminCount = User::role(self::SUPER_ADMIN_ROLE)->count();
            if ($superAdminCount <= 1) {
                return redirect()->route('reconciliation.access-control.index')
                    ->withErrors(['role_names' => 'At least one super_admin user must remain in the system.']);
            }
        }

        $user->syncRoles($requestedRoles->all());

        return redirect()->route('reconciliation.access-control.index')
            ->with('status', "Roles updated for user '{$user->name}'.");
    }

    private function isSuperAdminRoleName(string $roleName): bool
    {
        return Str::lower(trim($roleName)) === self::SUPER_ADMIN_ROLE;
    }

    private function isSuperAdminUser(User $user): bool
    {
        return $user->roles->contains(fn (Role $role) => $this->isSuperAdminRoleName((string) $role->name));
    }

    private function normalizePermissionNames(array $permissionNames): array
    {
        return collect($permissionNames)
            ->filter(fn ($permissionName) => is_string($permissionName) && trim($permissionName) !== '')
            ->map(fn (string $permissionName) => trim($permissionName))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build the screen × role access matrix.
     *
     * Each entry describes one screen/endpoint, the permission required to
     * access it, and which roles currently hold that permission.
     *
     * @param  \Illuminate\Support\Collection  $allRoles
     * @return array<int, array{screen: string, route: string, permission: string, roles: array<string, bool>}>
     */
    private function buildScreenAccessMatrix(\Illuminate\Support\Collection $allRoles): array
    {
        // [screen label, friendly route/URL, permission required]
        $screens = [
            ['Executive Home',              '/reconciliation/home',                          'reconciliation.view'],
            ['Reconciliation Dashboard',    '/reconciliation/',                              'reconciliation.view'],
            ['Dashboard Export',            '/reconciliation/export',                        'reconciliation.export.download'],
            ['Import Feeds (Upload)',        '/reconciliation/upload',                        'reconciliation.etl.run'],
            ['Run ETL Upload',              'POST /reconciliation/upload',                   'reconciliation.etl.run'],
            ['Rerun Batch',                 'POST /ops/runs/{id}/reanalysis',                'reconciliation.reanalysis.run'],
            ['Download Batch Output',       '/reconciliation/batches/{id}/download',         'reconciliation.export.download'],
            ['Delete Batch',                'DELETE /reconciliation/batches/{id}',           'reconciliation.delete'],
            ['Contract Patch Upload',       'POST /reconciliation/contract-patch',           'reconciliation.etl.run'],
            ['Contract Patch Download',     '/reconciliation/contract-patch/{id}/download',  'reconciliation.export.download'],
            ['Contract Patch Delete',       'DELETE /reconciliation/contract-patch/{id}',    'reconciliation.delete'],
            ['Lock/Unlock/Resolve/Flag',    'POST /reconciliation/records/{id}/...',         'reconciliation.edit'],
            ['Bulk Resolve',                'POST /reconciliation/records/bulk-resolve',     'reconciliation.bulk_approve'],
            ['Bulk Promote to LockList',    'POST /reconciliation/records/bulk-promote-...',  'reconciliation.bulk_approve'],
            ['Audit Logs',                  '/reconciliation/audit-logs',                    'reconciliation.bulk_approve'],
            ['Lock List (view)',             '/reconciliation/locklist',                      'reconciliation.view'],
            ['Lock List Export',             '/reconciliation/locklist/export',               'reconciliation.export.download'],
            ['Lock List Create/Update',      'POST/PUT /reconciliation/locklist',             'reconciliation.bulk_approve'],
            ['Lock List Delete',             'DELETE /reconciliation/locklist/{id}',          'reconciliation.delete'],
            ['Batch Results',               '/reconciliation/batches/{id}/results',           'reconciliation.results.view'],
            ['Commission Dashboard',        '/reconciliation/commission-dashboard',           'reconciliation.results.view'],
            ['Final BOB View & Export',      '/reconciliation/final-bob',                     'reconciliation.results.view'],
            ['Final BOB Export',             '/reconciliation/final-bob/export',              'reconciliation.export.download'],
            ['Contract Patch Ledger',        '/reconciliation/contract-patch-ledger',         'reconciliation.results.view'],
            ['Locklist Impact View',         '/reconciliation/locklist-impact',               'reconciliation.results.view'],
            ['Locklist Impact Export',       '/reconciliation/locklist-impact/export',        'reconciliation.export.download'],
            ['Settings',                    '/reconciliation/settings',                      'reconciliation.view'],
            ['Access Control',              '/reconciliation/access-control',                'access.manage'],
        ];

        $matrix = [];
        foreach ($screens as [$screen, $route, $permission]) {
            $roleAccess = [];
            foreach ($allRoles as $role) {
                // super_admin bypasses all gates
                $isSuperAdmin = $this->isSuperAdminRoleName((string) $role->name);
                $hasPermission = $isSuperAdmin
                    || $role->permissions->contains('name', $permission);
                $roleAccess[$role->name] = $hasPermission;
            }
            $matrix[] = [
                'screen'     => $screen,
                'route'      => $this->maskTechnicalRoute($route),
                'permission' => $permission,
                'roles'      => $roleAccess,
            ];
        }

        return $matrix;
    }

    /**
     * Hide internal route details from the UI while preserving action context.
     */
    private function maskTechnicalRoute(string $route): string
    {
        $normalized = trim($route);

        if (preg_match('/^(GET|POST|PUT|PATCH|DELETE)\s+/i', $normalized, $matches)) {
            return strtoupper((string) $matches[1]) . ' [protected endpoint]';
        }

        return '[protected screen path]';
    }

    /**
     * Build role hierarchy summary cards for Tab 1.
     *
     * @param  \Illuminate\Support\Collection  $allRoles
     * @return array<int, array{name: string, user_count: int, permission_count: int, permissions: array<string>, is_super_admin: bool}>
     */
    private function buildRoleHierarchy(\Illuminate\Support\Collection $allRoles): array
    {
        $descriptions = [
            'super_admin'           => 'Unrestricted authority. Bypasses all authorization gates. Reserved for platform ownership only.',
            'admin'                 => 'Full reconciliation management. Can run ETL, approve and delete. Cannot modify identity governance.',
            'Manager'               => 'Operational ownership. Can run ETL/reanalysis, bulk approve, manage lock list, and export. Cannot delete runs or manage access.',
            'Reconciliation_Analyst'=> 'Analyst scope. Can view/edit records and review reporting outputs. Cannot run ETL/reanalysis, bulk approve, export, or delete.',
        ];

        return $allRoles->map(function (Role $role) use ($descriptions) {
            return [
                'name'             => $role->name,
                'description'      => $descriptions[$role->name] ?? 'Custom operational role.',
                'user_count'       => $role->users()->count(),
                'permission_count' => $role->permissions->count(),
                'permissions'      => $role->permissions->pluck('name')->sort()->values()->all(),
                'is_super_admin'   => $this->isSuperAdminRoleName((string) $role->name),
            ];
        })->values()->all();
    }
}
