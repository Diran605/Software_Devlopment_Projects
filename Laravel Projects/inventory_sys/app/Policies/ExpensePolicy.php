<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class ExpensePolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'expenses';
    }
}
