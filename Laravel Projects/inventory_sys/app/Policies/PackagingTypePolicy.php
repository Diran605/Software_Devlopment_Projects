<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class PackagingTypePolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'packaging-types';
    }
}
