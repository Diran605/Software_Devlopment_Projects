<?php

namespace App\Policies;

use App\Models\GoodsReceivedNote;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class GoodsReceivedNotePolicy
{
    use ChecksModulePermissions;

    protected function permissionModule(): string
    {
        return 'goods-received-notes';
    }

    public function update(User $user, GoodsReceivedNote $goodsReceivedNote): bool
    {
        return false;
    }
}
