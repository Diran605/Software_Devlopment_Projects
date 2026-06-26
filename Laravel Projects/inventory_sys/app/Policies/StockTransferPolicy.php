<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class StockTransferPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'stock-transfers';
    }

    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        return false;
    }

    public function approve(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('approve.stock-transfers');
    }

    public function receive(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('receive.stock-transfers');
    }
}
