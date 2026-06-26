<?php

namespace App\Policies;

use App\Models\ClearanceStock;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class ClearanceStockPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'clearance-manager';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ClearanceStock $clearanceStock): bool
    {
        return false;
    }

    public function delete(User $user, ClearanceStock $clearanceStock): bool
    {
        return $user->can('delete.clearance-manager') && $clearanceStock->qty_remaining > 0;
    }

    public function restore(User $user, ClearanceStock $clearanceStock): bool
    {
        return false;
    }

    public function sell(User $user, ClearanceStock $clearanceStock): bool
    {
        return $user->can('create.clearance-sales');
    }

    public function donate(User $user, ClearanceStock $clearanceStock): bool
    {
        return $user->can('create.donations');
    }

    public function dispose(User $user, ClearanceStock $clearanceStock): bool
    {
        return $user->can('create.disposals');
    }
}
