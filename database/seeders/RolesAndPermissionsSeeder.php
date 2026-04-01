<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'reconciliation.view',
            'reconciliation.edit',
            'reconciliation.bulk_approve',
            'import.upload',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $roleAnalyst = Role::findOrCreate('Reconciliation_Analyst');
        $roleAnalyst->givePermissionTo(['reconciliation.view', 'reconciliation.edit', 'import.upload']);

        $roleManager = Role::findOrCreate('Manager');
        $roleManager->givePermissionTo(Permission::all());

        // Create Demo Users
        $analyst = User::firstOrCreate([
            'email' => 'analyst@bob.test',
        ], [
            'name' => 'Data Analyst',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        if (!$analyst->hasRole('Reconciliation_Analyst')) {
            $analyst->assignRole('Reconciliation_Analyst');
        }

        $manager = User::firstOrCreate([
            'email' => 'manager@bob.test',
        ], [
            'name' => 'Operations Manager',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        if (!$manager->hasRole('Manager')) {
            $manager->assignRole('Manager');
        }
    }
}
