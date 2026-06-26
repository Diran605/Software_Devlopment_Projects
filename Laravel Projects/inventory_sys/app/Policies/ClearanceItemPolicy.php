<?php

namespace App\Policies;

use App\Models\ClearanceItem;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class ClearanceItemPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'clearance-manager';
    }

    public function update(User $user, ClearanceItem $clearanceItem): bool
    {
        if ($clearanceItem->approval_status !== 'pending') {
            return false;
        }

        return $user->can('edit.clearance-manager');
    }

    public function delete(User $user, ClearanceItem $clearanceItem): bool
    {
        if (! in_array($clearanceItem->approval_status, ['pending', 'declined'], true)) {
            return false;
        }

        return $user->can('delete.clearance-manager');
    }

    public function approve(User $user, ClearanceItem $clearanceItem): bool
    {
        if ($clearanceItem->approval_status !== 'pending') {
            return false;
        }

        return $user->can('approve.clearance-manager');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve.clearance-manager');
    }
}
