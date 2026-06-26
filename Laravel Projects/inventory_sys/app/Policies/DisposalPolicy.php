<?php

namespace App\Policies;

use App\Models\Disposal;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class DisposalPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'disposals';
    }

    public function update(User $user, Disposal $disposal): bool
    {
        return false;
    }
}
