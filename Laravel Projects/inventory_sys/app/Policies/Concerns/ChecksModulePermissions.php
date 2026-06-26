<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksModulePermissions
{
    abstract protected function permissionModule(): string;

    public function viewAny(User $user): bool
    {
        return $user->can('view.'.$this->permissionModule());
    }

    public function view(User $user, mixed $model): bool
    {
        return $user->can('view.'.$this->permissionModule());
    }

    public function create(User $user): bool
    {
        return $user->can('create.'.$this->permissionModule());
    }

    public function update(User $user, mixed $model): bool
    {
        return $user->can('edit.'.$this->permissionModule());
    }

    public function delete(User $user, mixed $model): bool
    {
        return $user->can('delete.'.$this->permissionModule());
    }

    public function restore(User $user, mixed $model): bool
    {
        return $user->can('delete.'.$this->permissionModule());
    }

    public function forceDelete(User $user, mixed $model): bool
    {
        return $user->hasRole('super-admin');
    }
}
