<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class DonationPolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'donations';
    }

    public function update(User $user, Donation $donation): bool
    {
        return false;
    }
}
