<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class CustomerPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'customers';
    }
}
