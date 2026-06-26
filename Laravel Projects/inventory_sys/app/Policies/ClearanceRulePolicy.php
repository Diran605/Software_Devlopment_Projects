<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class ClearanceRulePolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'clearance-manager';
    }
}
