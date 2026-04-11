<x-reconciliation-layout>
    <x-slot name="pageTitle">Access Control</x-slot>
    <x-slot name="pageSubtitle">Administration for role governance, permission strategy, and user access boundaries</x-slot>

    @php
        $permissionCatalog = [
            'reconciliation.view' => [
                'label' => 'View Reconciliation Workspace',
                'description' => 'Read-only access to reconciliation screens and reporting views.',
                'module' => 'Read Access',
            ],
            'reconciliation.edit' => [
                'label' => 'Edit Reconciliation Records',
                'description' => 'Lock, unlock, resolve, and flag reconciliation records.',
                'module' => 'Record Actions',
            ],
            'reconciliation.bulk_approve' => [
                'label' => 'Approve Bulk Operations',
                'description' => 'Run bulk resolution workflows, access audit logs, and manage lock list.',
                'module' => 'Approvals',
            ],
            'reconciliation.delete' => [
                'label' => 'Delete Batches and Patches',
                'description' => 'Delete import runs, contract patch runs, and lock list entries.',
                'module' => 'Destructive Actions',
            ],
            'reconciliation.etl.run' => [
                'label' => 'Run ETL Uploads',
                'description' => 'Upload new ETL runs and contract patches.',
                'module' => 'Data Intake',
            ],
            'reconciliation.reanalysis.run' => [
                'label' => 'Rerun Existing Batches',
                'description' => 'Rerun analysis on previously uploaded batches.',
                'module' => 'Data Intake',
            ],
            'reconciliation.results.view' => [
                'label' => 'View Results & Reports',
                'description' => 'View commission dashboards, Final BOB, and batch results.',
                'module' => 'Read Access',
            ],
            'reconciliation.export.download' => [
                'label' => 'Download Exports',
                'description' => 'Export data to Excel/CSV/PDF, download batch output files.',
                'module' => 'Export',
            ],
            'access.manage' => [
                'label' => 'Manage Access Control',
                'description' => 'Open Access Control and manage roles, permissions, and user-role assignments.',
                'module' => 'Administration',
            ],
        ];

        $permissionGroups = [
            'Read & Reporting' => [
                'reconciliation.view',
                'reconciliation.results.view',
            ],
            'Record Operations' => [
                'reconciliation.edit',
                'reconciliation.bulk_approve',
                'reconciliation.delete',
            ],
            'Data Intake' => [
                'reconciliation.etl.run',
                'reconciliation.reanalysis.run',
            ],
            'Export' => [
                'reconciliation.export.download',
            ],
            'Administration' => [
                'access.manage',
            ],
        ];

        $permissionFallback = static function (string $permissionName): array {
            return [
                'label' => \Illuminate\Support\Str::of($permissionName)->replace('.', ' ')->replace('_', ' ')->headline()->value(),
                'description' => 'Custom permission. Review and document intended usage before assigning.',
                'module' => 'Custom',
            ];
        };
    @endphp

    @push('head')
    <style>
        .ac-shell { max-width: 1500px; margin: 0 auto; }
        .ac-shell [x-cloak] { display: none !important; }
        .ac-hero { position: relative; overflow: hidden; background: var(--bob-grid-shell-bg); }
        .ac-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(120deg, rgba(99, 102, 241, 0.12), transparent 55%); pointer-events: none; }
        .ac-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--bob-text-faint); }
        .ac-title { color: var(--bob-text-primary); font-size: 20px; font-weight: 800; letter-spacing: -0.01em; margin-top: 4px; }
        .ac-subtitle { color: var(--bob-text-muted); font-size: 12px; font-weight: 600; margin-top: 6px; max-width: 720px; }
        .ac-kpi { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 6px 12px; font-size: 11px; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
        .ac-kpi.roles { color: #c7d2fe; background: rgba(99, 102, 241, 0.14); border-color: rgba(129, 140, 248, 0.35); }
        .ac-kpi.permissions { color: #86efac; background: rgba(16, 185, 129, 0.14); border-color: rgba(16, 185, 129, 0.35); }
        .ac-kpi.users { color: #fcd34d; background: rgba(245, 158, 11, 0.14); border-color: rgba(245, 158, 11, 0.35); }
        .ac-section-switch { display: flex; flex-wrap: wrap; gap: 6px; padding: 4px; border-radius: 12px; background: rgba(15, 23, 42, 0.48); border: 1px solid var(--bob-border-light); }
        .ac-switch-btn { display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; padding: 7px 12px; font-size: 11px; font-weight: 700; color: var(--bob-text-muted); transition: all 0.18s ease; border: 1px solid transparent; }
        .ac-switch-btn.active { color: #ffffff; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-color: rgba(255, 255, 255, 0.15); box-shadow: 0 10px 24px -16px rgba(99, 102, 241, 0.8); }
        .ac-card { border-radius: 18px; border: 1px solid var(--bob-border-subtle); background: linear-gradient(180deg, rgba(15, 23, 42, 0.36), rgba(15, 23, 42, 0.12)); box-shadow: var(--bob-shadow-card); }
        .ac-card-head { padding: 18px 22px; border-bottom: 1px solid var(--bob-border-subtle); display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .ac-card-title { color: var(--bob-text-primary); font-size: 13px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; }
        .ac-card-desc { color: var(--bob-text-muted); font-size: 11px; font-weight: 600; margin-top: 4px; }
        .ac-table { width: 100%; border-collapse: collapse; }
        .ac-table thead th { padding: 12px 22px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.11em; color: var(--bob-text-faint); border-bottom: 1px solid var(--bob-border-subtle); }
        .ac-table tbody td { padding: 14px 22px; border-bottom: 1px solid var(--bob-border-subtle); color: var(--bob-text-secondary); font-size: 13px; font-weight: 600; }
        .ac-table tbody tr:last-child td { border-bottom: none; }
        .ac-chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 800; }
        .ac-chip.standard { color: #c7d2fe; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(129, 140, 248, 0.35); }
        .ac-chip.protected { color: #fecdd3; background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(251, 113, 133, 0.35); }
        .ac-role-form { padding: 18px 22px 22px; display: grid; gap: 14px; }
        .ac-perm-grid { display: grid; gap: 9px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
        .ac-perm-chip { display: flex; align-items: flex-start; gap: 9px; border-radius: 10px; padding: 10px 11px; border: 1px solid var(--bob-border-subtle); background: rgba(255, 255, 255, 0.02); color: var(--bob-text-secondary); font-size: 12px; font-weight: 600; transition: all 0.16s ease; }
        .ac-perm-chip.is-checked { background: rgba(99, 102, 241, 0.16); border-color: rgba(129, 140, 248, 0.38); color: #e0e7ff; }
        .ac-perm-chip input { margin-top: 2px; flex-shrink: 0; accent-color: #6366f1; }
        .ac-perm-stack { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .ac-perm-name { font-size: 12px; font-weight: 700; color: inherit; line-height: 1.3; }
        .ac-perm-meta { font-size: 10px; color: var(--bob-text-muted); font-weight: 600; line-height: 1.3; }
        .ac-perm-chip.is-checked .ac-perm-meta { color: #c7d2fe; }
        .ac-group-title { font-size: 10px; text-transform: uppercase; letter-spacing: 0.11em; font-weight: 800; color: var(--bob-text-faint); margin-bottom: 8px; }
        .ac-matrix-wrap { padding: 0 0 18px; }
        .ac-users-wrap { display: grid; gap: 14px; padding: 18px 22px 22px; }
        .ac-user-card { border-radius: 14px; border: 1px solid var(--bob-border-subtle); background: rgba(255, 255, 255, 0.02); padding: 14px; }
        .ac-user-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
        .ac-user-name { color: var(--bob-text-primary); font-size: 14px; font-weight: 800; letter-spacing: -0.01em; }
        .ac-user-email { color: var(--bob-text-muted); font-size: 11px; margin-top: 2px; font-weight: 600; }
        .ac-user-current { display: flex; flex-wrap: wrap; gap: 6px; }
        .ac-role-toggle-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 8px; }
        .ac-role-toggle { display: flex; align-items: center; gap: 8px; border-radius: 10px; padding: 9px 10px; border: 1px solid var(--bob-border-subtle); background: rgba(255, 255, 255, 0.02); color: var(--bob-text-secondary); font-size: 12px; font-weight: 600; transition: all 0.16s ease; }
        .ac-role-toggle.is-checked { color: #e0e7ff; border-color: rgba(129, 140, 248, 0.36); background: rgba(99, 102, 241, 0.15); }
        .ac-role-toggle input { accent-color: #6366f1; }
        .ac-banner-ok { border: 1px solid rgba(16, 185, 129, 0.32); background: rgba(16, 185, 129, 0.09); }
        .ac-banner-error { border: 1px solid rgba(244, 63, 94, 0.32); background: rgba(244, 63, 94, 0.09); }
        
        .ac-role-hierarchy-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; padding: 18px 22px; }
        .ac-role-summary-card { background: rgba(255,255,255,0.03); border: 1px solid var(--bob-border-subtle); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
        .ac-role-summary-title { font-weight: 800; font-size: 15px; color: var(--bob-text-primary); }
        .ac-role-summary-desc { font-size: 12px; color: var(--bob-text-muted); line-height: 1.4; }
        .ac-role-summary-stats { display: flex; gap: 12px; font-size: 11px; font-weight: 600; color: var(--bob-text-secondary); }
        .ac-role-summary-perms { background: rgba(0,0,0,0.2); border-radius: 8px; padding: 10px; font-size: 11px; display: flex; flex-direction: column; gap: 6px; margin-top: auto; }
        .ac-role-summary-perm-item { display: flex; gap: 6px; align-items: baseline; }
        
        .ac-matrix-table-container { overflow-x: auto; max-height: 600px; }
        .ac-matrix-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
        .ac-matrix-table th { position: sticky; top: 0; background: var(--bob-grid-header-bg); padding: 12px 16px; text-transform: uppercase; font-size: 10px; font-weight: 800; color: var(--bob-text-faint); border-bottom: 1px solid var(--bob-border-subtle); z-index: 10; text-align: center; }
        .ac-matrix-table th:first-child { left: 0; z-index: 20; text-align: left; }
        .ac-matrix-table td { padding: 10px 16px; border-bottom: 1px solid var(--bob-border-subtle); color: var(--bob-text-secondary); text-align: center; }
        .ac-matrix-table td:first-child { position: sticky; left: 0; background: var(--bob-grid-shell-bg); z-index: 5; text-align: left; font-weight: 600; color: var(--bob-text-primary); border-right: 1px solid var(--bob-border-subtle); }
        .ac-matrix-table td:nth-child(2) { text-align: left; }
        .ac-matrix-perm-title { font-size: 11px; font-weight: 700; color: var(--bob-text-primary); line-height: 1.3; }
        .ac-matrix-perm-desc { font-size: 10px; color: var(--bob-text-muted); margin-top: 2px; line-height: 1.3; }
        .ac-matrix-table tr:hover td { background: rgba(255,255,255,0.02); }
        .ac-matrix-table tr:hover td:first-child { background: var(--bob-grid-header-bg); }
        .ac-check-icon { display: inline-block; width: 16px; height: 16px; color: #10b981; }
        .ac-x-icon { display: inline-block; width: 16px; height: 16px; color: #475569; opacity: 0.5; }
        
        html.bob-light .ac-hero { background: linear-gradient(160deg, rgba(37, 99, 235, 0.08), rgba(79, 70, 229, 0.05) 58%, rgba(255, 255, 255, 0.96)); }
        html.bob-light .ac-card { background: linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(248, 251, 255, 0.96)); border-color: rgba(148, 163, 184, 0.24); }
        html.bob-light .ac-perm-chip, html.bob-light .ac-role-toggle, html.bob-light .ac-user-card { background: rgba(255, 255, 255, 0.95); border-color: rgba(148, 163, 184, 0.24); }
        html.bob-light .ac-perm-chip.is-checked, html.bob-light .ac-role-toggle.is-checked { color: #1e3a8a; background: rgba(59, 130, 246, 0.12); border-color: rgba(59, 130, 246, 0.32); }
        html.bob-light .ac-role-summary-card { background: #fff; }
        html.bob-light .ac-role-summary-perms { background: #f8fafc; }
        html.bob-light .ac-matrix-table th { background: #f1f5f9; color: #475569; }
        html.bob-light .ac-matrix-table td:first-child { background: #fff; border-right-color: #e2e8f0; }
        html.bob-light .ac-matrix-table tr:hover td { background: #f8fafc; }
        html.bob-light .ac-matrix-table tr:hover td:first-child { background: #f1f5f9; }
    </style>
    @endpush

    <div x-data="{ panel: 'overview' }" class="ac-shell space-y-6">
        @if (session('status'))
            <div class="ac-banner-ok rounded-xl p-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background: rgba(16,185,129,0.16);">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </div>
                <div class="text-sm font-semibold text-emerald-200">{{ session('status') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="ac-banner-error rounded-xl p-4">
                <h3 class="text-sm font-bold text-rose-200 mb-2">Validation errors</h3>
                <ul class="space-y-1 text-xs text-rose-100 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="ac-hero bob-glass-panel p-5 md:p-6">
            <div class="relative z-[1] space-y-4">
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                    <div>
                        <p class="ac-label">Access governance workspace</p>
                        <h2 class="ac-title">Role assignment and permission strategy center</h2>
                        <p class="ac-subtitle">
                            {{ $isSuperAdmin ? 'Super Administrator mode is active. You have unrestricted administrative authority.' : 'Administrator mode is active. Super admin entities are strictly protected from modification.' }}
                        </p>
                    </div>
                    <div class="ac-section-switch">
                        <button type="button" @click="panel = 'overview'" class="ac-switch-btn" :class="panel === 'overview' ? 'active' : ''">Role Overview</button>
                        <button type="button" @click="panel = 'matrix'" class="ac-switch-btn" :class="panel === 'matrix' ? 'active' : ''">Screen Access Matrix</button>
                        <button type="button" @click="panel = 'governance'" class="ac-switch-btn" :class="panel === 'governance' ? 'active' : ''">Role Governance</button>
                        <button type="button" @click="panel = 'assignment'" class="ac-switch-btn" :class="panel === 'assignment' ? 'active' : ''">User Role Assignment</button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="ac-kpi roles">Roles: {{ count($roleHierarchy) }}</span>
                    <span class="ac-kpi permissions">Permissions: {{ count($permissions) }}</span>
                    <span class="ac-kpi users">Users: {{ count($users) }}</span>
                </div>
            </div>
        </section>

        {{-- TAB 1: ROLE OVERVIEW --}}
        <div x-show="panel === 'overview'" x-cloak class="space-y-6">
            <section class="ac-card">
                <header class="ac-card-head">
                    <div>
                        <h3 class="ac-card-title">Role Hierarchy & Capabilities</h3>
                        <p class="ac-card-desc">Summary of established operational roles and their designated authority</p>
                    </div>
                </header>
                <div class="ac-role-hierarchy-grid">
                    @foreach ($roleHierarchy as $rh)
                        <div class="ac-role-summary-card">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="ac-role-summary-title">{{ $rh['name'] }}</h4>
                                    <div class="ac-role-summary-stats mt-2">
                                        <span>{{ $rh['user_count'] }} users</span>
                                        <span>{{ $rh['is_super_admin'] ? 'All' : $rh['permission_count'] }} permissions</span>
                                    </div>
                                </div>
                                @if ($rh['is_super_admin'])
                                    <span class="ac-chip protected">Protected</span>
                                @endif
                            </div>
                            <p class="ac-role-summary-desc">{{ $rh['description'] }}</p>
                            
                            <div class="ac-role-summary-perms">
                                <div class="font-bold mb-1 text-white">Capabilities</div>
                                @foreach ($permissionGroups as $groupLabel => $perms)
                                    @php 
                                        $hasAny = $rh['is_super_admin'] || count(array_intersect($perms, $rh['permissions'])) > 0;
                                    @endphp
                                    <div class="ac-role-summary-perm-item">
                                        @if ($hasAny)
                                            <svg class="ac-check-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        @else
                                            <svg class="ac-x-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        @endif
                                        <span class="{{ $hasAny ? 'text-gray-200' : 'text-gray-500' }}">{{ $groupLabel }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- TAB 2: SCREEN ACCESS MATRIX --}}
        <div x-show="panel === 'matrix'" x-cloak class="space-y-6">
            <section class="ac-card">
                <header class="ac-card-head">
                    <div>
                        <h3 class="ac-card-title">Screen × Role Access Matrix</h3>
                        <p class="ac-card-desc">Definitive guide to which roles can open each screen and what level of access is required.</p>
                    </div>
                </header>
                <div class="ac-matrix-wrap">
                    <div class="ac-matrix-table-container">
                        <table class="ac-matrix-table">
                            <thead>
                                <tr>
                                    <th>Screen / Module</th>
                                    <th>Required Access</th>
                                    @foreach ($roleHierarchy as $rh)
                                        <th>{{ $rh['name'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($screenAccessMatrix as $row)
                                    @php
                                        $permissionMeta = $permissionCatalog[$row['permission']] ?? $permissionFallback($row['permission']);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-bold">{{ $row['screen'] }}</div>
                                        </td>
                                        <td>
                                            <div class="ac-matrix-perm-title">{{ $permissionMeta['label'] }}</div>
                                            <div class="ac-matrix-perm-desc">{{ $permissionMeta['description'] }}</div>
                                        </td>
                                        @foreach ($roleHierarchy as $rh)
                                            <td>
                                                @if ($row['roles'][$rh['name']] ?? false)
                                                    <svg class="ac-check-icon mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                @else
                                                    <svg class="ac-x-icon mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        {{-- TAB 3: ROLE GOVERNANCE --}}
        <div x-show="panel === 'governance'" x-cloak class="space-y-6">
            <div class="grid grid-cols-1 2xl:grid-cols-3 gap-6">
                <!-- Create Role Card -->
                <section class="ac-card 2xl:col-span-1 overflow-hidden h-max">
                    <header class="ac-card-head">
                        <div>
                            <h3 class="ac-card-title">Create Role</h3>
                            <p class="ac-card-desc">Add a new operational role with baseline permissions</p>
                        </div>
                    </header>
                    <form method="POST" action="{{ route('reconciliation.access-control.roles.store') }}" class="ac-role-form">
                        @csrf
                        <div>
                            <label for="new_role_name" class="bob-form-label">Role Name</label>
                            <input id="new_role_name" name="name" type="text" required maxlength="50" class="bob-form-input" placeholder="example: compliance_manager" value="{{ old('name') }}" />
                        </div>
                        <div>
                            <label class="bob-form-label">Initial Permission Set</label>
                            <div class="max-h-64 overflow-y-auto pr-1 space-y-4 mt-2">
                                @foreach ($permissionGroups as $groupLabel => $groupPermissions)
                                    <div>
                                        <p class="ac-group-title">{{ $groupLabel }}</p>
                                        <div class="space-y-2">
                                            @foreach ($groupPermissions as $permissionName)
                                                @php
                                                    $permission = $permissions->firstWhere('name', $permissionName);
                                                @endphp
                                                @if ($permission)
                                                    @php
                                                        $meta = $permissionCatalog[$permission->name] ?? $permissionFallback($permission->name);
                                                        $checked = collect(old('permission_names', []))->contains($permission->name);
                                                    @endphp
                                                    <label class="ac-perm-chip {{ $checked ? 'is-checked' : '' }}">
                                                        <input type="checkbox" name="permission_names[]" value="{{ $permission->name }}" @checked($checked)>
                                                        <span class="ac-perm-stack">
                                                            <span class="ac-perm-name">{{ $meta['label'] }}</span>
                                                        </span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="pt-1">
                            <button type="submit" class="bob-btn-primary w-full justify-center">Create Role</button>
                        </div>
                    </form>
                </section>

                <!-- Update Roles List -->
                <div class="2xl:col-span-2 space-y-4">
                    @forelse ($roles as $role)
                        @php
                            $rolePermissionNames = $role->permissions->pluck('name')->all();
                            $isSuperAdminRole = strtolower($role->name) === $superAdminRoleName;
                        @endphp
                        <article class="ac-card overflow-hidden">
                            <header class="ac-card-head">
                                <div>
                                    <h4 class="ac-card-title" style="font-size: 14px;">{{ $role->name }}</h4>
                                    <p class="ac-card-desc">{{ $role->permissions->count() }} permissions | {{ $role->users()->count() }} users assigned</p>
                                </div>
                                @if ($isSuperAdminRole)
                                    <span class="ac-chip protected">Protected Core Role</span>
                                @endif
                            </header>
                            <form method="POST" action="{{ route('reconciliation.access-control.roles.permissions', $role) }}" class="ac-role-form">
                                @csrf
                                @method('PUT')
                                <div class="ac-perm-grid">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $checked = in_array($permission->name, $rolePermissionNames, true) || $isSuperAdminRole;
                                            $meta = $permissionCatalog[$permission->name] ?? $permissionFallback($permission->name);
                                        @endphp
                                        <label class="ac-perm-chip {{ $checked ? 'is-checked' : '' }}" style="{{ $isSuperAdminRole ? 'opacity: 0.7;' : '' }}">
                                            <input type="checkbox" name="permission_names[]" value="{{ $permission->name }}" @checked($checked) {{ $isSuperAdminRole ? 'disabled' : '' }}>
                                            <span class="ac-perm-stack">
                                                <span class="ac-perm-name">{{ $meta['label'] }}</span>
                                                <span class="ac-perm-meta">{{ $meta['module'] }} · {{ $meta['description'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @if (!$isSuperAdminRole)
                                    <div class="flex justify-end pt-1">
                                        <button type="submit" class="bob-btn-primary">Save Permissions</button>
                                    </div>
                                @endif
                            </form>
                        </article>
                    @empty
                        <div class="ac-card p-6 text-sm" style="color: var(--bob-text-muted);">No roles available to manage.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TAB 4: USER ROLE ASSIGNMENT --}}
        <section x-show="panel === 'assignment'" x-cloak class="ac-card overflow-hidden">
            <header class="ac-card-head">
                <div>
                    <h3 class="ac-card-title">User Role Assignment</h3>
                    <p class="ac-card-desc">Assign one or more operational roles per user with protected-role controls</p>
                </div>
            </header>
            <div class="ac-users-wrap">
                @forelse ($users as $managedUser)
                    @php
                        $userRoleNames = $managedUser->roles->pluck('name')->all();
                    @endphp
                    <form method="POST" action="{{ route('reconciliation.access-control.users.roles', $managedUser) }}" class="ac-user-card flex flex-col md:flex-row md:items-center justify-between gap-4">
                        @csrf
                        @method('PUT')
                        <div class="min-w-[200px]">
                            <p class="ac-user-name">{{ $managedUser->name }}</p>
                            <p class="ac-user-email">{{ $managedUser->email }}</p>
                        </div>
                        <div class="flex-grow flex flex-wrap items-center gap-2">
                            @foreach ($assignableRoles as $assignableRole)
                                @php
                                    $isChecked = in_array($assignableRole->name, $userRoleNames, true);
                                    $isSuperAdminRole = strtolower($assignableRole->name) === $superAdminRoleName;
                                @endphp
                                <label class="ac-role-toggle {{ $isChecked ? 'is-checked' : '' }} {{ $isSuperAdminRole ? 'border-rose-900/30' : '' }}">
                                    <input type="checkbox" name="role_names[]" value="{{ $assignableRole->name }}" @checked($isChecked)>
                                    <span>{{ $assignableRole->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div>
                            <button type="submit" class="bob-btn-secondary py-2 text-xs">Save</button>
                        </div>
                    </form>
                @empty
                    <div class="text-sm" style="color: var(--bob-text-muted);">No users available for assignment.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-reconciliation-layout>
