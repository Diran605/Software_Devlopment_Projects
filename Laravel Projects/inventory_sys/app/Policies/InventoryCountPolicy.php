<?php

namespace App\Policies;

use App\Models\InventoryCount;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class InventoryCountPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'inventory-counts';
    }

    public function approve(User $user, InventoryCount $inventoryCount): bool
    {
        return $user->can('approve.inventory-counts');
    }

    public function post(User $user, InventoryCount $inventoryCount): bool
    {
        return $user->can('post.inventory-counts');
    }
}
