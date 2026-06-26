<?php

namespace App\Policies;

use App\Models\DeletionLog;
use App\Models\User;

class DeletionLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view.deletion-logs');
    }

    public function view(User $user, DeletionLog $deletionLog): bool
    {
        return $user->can('view.deletion-logs');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DeletionLog $deletionLog): bool
    {
        return false;
    }

    public function delete(User $user, DeletionLog $deletionLog): bool
    {
        return false;
    }

    public function restore(User $user, DeletionLog $deletionLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, DeletionLog $deletionLog): bool
    {
        return false;
    }
}
