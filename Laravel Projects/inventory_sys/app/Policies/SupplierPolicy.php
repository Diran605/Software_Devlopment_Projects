<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class SupplierPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'suppliers';
    }
}
