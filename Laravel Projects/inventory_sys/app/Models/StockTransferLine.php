<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'stock_transfer_id', 'item_id', 'batch_inventory_id',
    'qty_requested', 'qty_transferred', 'qty_received',
    'unit_cost', 'batch_number', 'expiry_date', 'notes'
])]
class StockTransferLine extends Model
{
    use SoftDeletes;

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
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
