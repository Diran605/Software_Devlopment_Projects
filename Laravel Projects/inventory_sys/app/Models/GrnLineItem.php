<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'grn_id', 'item_id', 'packaging_type_id', 'entry_mode',
    'pack_quantity', 'units_per_pack', 'qty_received',
    'unit_cost', 'line_total', 'batch_number', 'expiry_date'
])]
class GrnLineItem extends Model
{
    use SoftDeletes;

    protected $casts = [
        'expiry_date' => 'datetime',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function packagingType()
    {
        return $this->belongsTo(PackagingType::class);
    }

    public function batchInventory()
    {
        return $this->morphOne(BatchInventory::class, 'source');
    }
}
