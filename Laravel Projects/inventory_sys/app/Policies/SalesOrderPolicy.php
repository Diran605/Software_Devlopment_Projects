<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class SalesOrderPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'sales-orders';
    }
}
