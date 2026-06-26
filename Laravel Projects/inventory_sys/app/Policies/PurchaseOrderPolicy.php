<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class PurchaseOrderPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'purchase-orders';
    }
}
