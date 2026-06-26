<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class ExpenseCategoryPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'expense-categories';
    }
}
