<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class ItemPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'items';
    }
}
