<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'item_id', 'batch_inventory_id', 'rule_id', 'original_price', 'clearance_price',
    'is_active', 'activated_at', 'qty_flagged', 'days_to_expiry',
    'urgency_status', 'approval_status', 'action_type', 'qty_to_move', 'notes'
])]
class ClearanceItem extends Model
{
    use SoftDeletes;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batchInventory()
    {
        return $this->belongsTo(BatchInventory::class);
    }

    public function rule()
    {
        return $this->belongsTo(ClearanceRule::class);
    }

    public function clearanceStock()
    {
        return $this->hasOne(ClearanceStock::class);
    }
}
