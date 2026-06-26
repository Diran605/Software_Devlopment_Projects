<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class RolePolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'roles';
    }
}
