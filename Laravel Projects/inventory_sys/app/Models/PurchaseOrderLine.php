<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'purchase_order_id', 'item_id', 'qty_ordered',
    'qty_received', 'unit_cost', 'line_total', 'notes'
])]
class PurchaseOrderLine extends Model
{
    use SoftDeletes;

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
