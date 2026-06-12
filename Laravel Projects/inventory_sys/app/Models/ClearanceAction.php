<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'branch_id',
    'clearance_stocks_id',
    'item_id',
    'batch_inventory_id',
    'action_type',
    'qty',
    'loss_value',
    'sales_order_id',
    'disposal_id',
    'donation_id',
    'notes'
])]
class ClearanceAction extends Model
{
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function clearanceStock()
    {
        return $this->belongsTo(ClearanceStock::class, 'clearance_stocks_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batchInventory()
    {
        return $this->belongsTo(BatchInventory::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function disposal()
    {
        return $this->belongsTo(Disposal::class);
    }

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }
}
