<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class OpeningStockPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'opening-stock';
    }
}
