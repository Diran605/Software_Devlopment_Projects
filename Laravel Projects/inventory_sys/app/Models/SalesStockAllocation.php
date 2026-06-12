<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sales_order_line_id', 'batch_inventory_id', 'qty_allocated', 'unit_cost'])]
class SalesStockAllocation extends Model
{
    public function salesOrderLine()
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    public function batchInventory()
    {
        return $this->belongsTo(BatchInventory::class);
    }
}
