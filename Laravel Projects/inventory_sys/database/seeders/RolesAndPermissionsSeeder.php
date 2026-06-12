<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'branches'             => ['view', 'create', 'edit', 'delete'],
            'departments'          => ['view', 'create', 'edit', 'delete'],
            'users'                => ['view', 'create', 'edit', 'delete'],
            'roles'                => ['view', 'create', 'edit', 'delete'],
            'items'                => ['view', 'create', 'edit', 'delete'],
            'item-categories'      => ['view', 'create', 'edit', 'delete'],
            'unit-of-measures'     => ['view', 'create', 'edit', 'delete'],
            'packaging-types'      => ['view', 'create', 'edit', 'delete'],
            'suppliers'            => ['view', 'create', 'edit', 'delete'],
            'customers'            => ['view', 'create', 'edit', 'delete'],
            'purchase-orders'      => ['view', 'create', 'edit', 'delete', 'approve', 'cancel'],
            'opening-stock'        => ['view', 'create', 'edit', 'delete'],
            'goods-received-notes' => ['view', 'create', 'delete'],
            'sales-orders'         => ['view', 'create', 'edit', 'delete'],
            'stock-transfers'      => ['view', 'create', 'delete', 'approve', 'receive'],
            'stock-movements'      => ['view'],
            'audit-logs'           => ['view'],
            'deletion-logs'        => ['view'],
            'inventory-counts'     => ['view', 'create', 'edit', 'delete', 'approve', 'post'],
            'clearance-manager'    => ['view', 'create', 'edit', 'delete', 'approve'],
            'clearance-sales'      => ['view', 'create', 'delete'],
            'disposals'            => ['view', 'create', 'delete'],
            'donations'            => ['view', 'create', 'delete'],
            'expenses'             => ['view', 'create', 'edit', 'delete'],
            'expense-categories'   => ['view', 'create', 'edit', 'delete'],
            'reports'              => ['view'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}.{$module}"]);
            }
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        $branchManager = Role::firstOrCreate(['name' => 'branch-manager']);
        $branchManager->syncPermissions(Permission::all());

        $inventoryManager = Role::firstOrCreate(['name' => 'inventory-manager']);
        $inventoryManager->syncPermissions(
            Permission::whereIn('name', $this->prefixed([
                'items'                => ['view'],
                'item-categories'      => ['view'],
                'unit-of-measures'     => ['view'],
                'packaging-types'      => ['view'],
                'suppliers'            => ['view', 'create', 'edit', 'delete'],
                'purchase-orders'      => ['view', 'create', 'edit', 'delete', 'approve', 'cancel'],
                'opening-stock'        => ['view', 'create', 'edit', 'delete'],
                'goods-received-notes' => ['view', 'create', 'delete'],
                'stock-transfers'      => ['view', 'create', 'delete', 'approve', 'receive'],
                'stock-movements'      => ['view'],
                'audit-logs'           => ['view'],
                'deletion-logs'        => ['view'],
                'reports'              => ['view'],
                'clearance-manager'    => ['view', 'create', 'edit', 'delete', 'approve'],
                'disposals'            => ['view', 'create', 'delete'],
                'donations'            => ['view', 'create', 'delete'],
                'inventory-counts'     => ['view', 'create', 'edit', 'delete', 'approve', 'post'],
                'expenses'             => ['view', 'create', 'edit', 'delete'],
                'expense-categories'   => ['view', 'create', 'edit', 'delete'],
            ]))->get()
        );

        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions(
            Permission::whereIn('name', $this->prefixed([
                'items'           => ['view'],
                'customers'       => ['view'],
                'sales-orders'    => ['view', 'create', 'edit', 'delete'],
                'clearance-sales' => ['view', 'create'],
            ]))->get()
        );

        $auditor = Role::firstOrCreate(['name' => 'auditor']);
        $auditor->syncPermissions(
            Permission::where('name', 'like', 'view.%')->get()
        );
    }

    private function prefixed(array $modules): array
    {
        $names = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$action}.{$module}";
            }
        }
        return $names;
    }
}
