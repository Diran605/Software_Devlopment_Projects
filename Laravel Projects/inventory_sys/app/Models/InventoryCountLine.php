<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'inventory_count_id', 'item_id', 'batch_inventory_id', 'qty_system', 'qty_counted', 
    'qty_variance', 'unit_cost', 'selling_price', 'variance_value', 'notes'
])]
class InventoryCountLine extends Model
{
    use SoftDeletes;

    public function inventoryCount()
    {
        return $this->belongsTo(InventoryCount::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batchInventory()
    {
        return $this->belongsTo(BatchInventory::class);
    }
}

