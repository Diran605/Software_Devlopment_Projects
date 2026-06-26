<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class ItemCategoryPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'item-categories';
    }
}
