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
            // User & team management
            'manage_users',
            'manage_departments',
            
            // Configuration
            'manage_work_types',
            'manage_project_clients',
            'manage_roles',
            
            // Task management
            'assign_plans',
            
            // Viewing & reporting
            'view_team_stats',
            'export_reports',
            'view_audit_logs',
            
            // Base (all users)
            'view_own_data',
            'manage_own_work',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Super Admin — gets ALL permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions($permissions);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            'manage_roles',
            'manage_users',
            'manage_departments',
            'manage_work_types',
            'manage_project_clients',
            'assign_plans',
            'view_team_stats',
            'export_reports',
            'view_audit_logs',
            'view_own_data',
            'manage_own_work',
        ]);

        // Worker — frontend only, basic permissions
        $workerRole = Role::firstOrCreate(['name' => 'worker']);
        $workerRole->syncPermissions([
            'view_own_data',
            'manage_own_work',
        ]);
    }
}
