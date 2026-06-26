<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sales_order_id', 'item_id', 'batch_inventory_id', 'clearance_stock_id', 'packaging_type_id', 'entry_mode',
    'pack_quantity', 'units_per_pack', 'qty_sold', 'unit_price',
    'unit_cost', 'line_total', 'line_cost', 'gross_profit',
    'is_low_margin', 'is_negative_margin', 'edited_at', 'edit_count'
])]
class SalesOrderLine extends Model
{
    use SoftDeletes;

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function clearanceStock()
    {
        return $this->belongsTo(ClearanceStock::class);
    }

    public function packagingType()
    {
        return $this->belongsTo(PackagingType::class);
    }

    public function batchInventory()
    {
        return $this->belongsTo(BatchInventory::class);
    }

    public function salesStockAllocations()
    {
        return $this->hasMany(SalesStockAllocation::class);
    }
}
