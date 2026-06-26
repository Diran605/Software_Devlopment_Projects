<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class UnitOfMeasurePolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'unit-of-measures';
    }
}
