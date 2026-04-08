<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage-users',
            'manage-roles',
            'manage-system',
            'view-all-data',
            'view-team-data',
            'export-team-data',
            'view-own-data',
            'manage-own-work',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create super_admin role and assign permissions
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo([
            'manage-users',
            'manage-roles',
            'view-all-data',
            'manage-system',
            'view-own-data',
            'manage-own-work',
        ]);

        // Create admin role and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view-team-data',
            'export-team-data',
            'view-own-data',
            'manage-own-work',
        ]);

        // Create worker role and assign permissions
        $workerRole = Role::create(['name' => 'worker']);
        $workerRole->givePermissionTo([
            'view-own-data',
            'manage-own-work', // Workers manage their own plans and logs
        ]);
    }
}
