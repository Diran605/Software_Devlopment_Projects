<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'opening_stock_entry_id', 'item_id', 'batch_number',
    'expiry_date', 'qty_on_hand', 'unit_cost', 'is_consumed',
    'edited_at', 'edit_count'
])]
class OpeningStockLine extends Model
{
    use SoftDeletes;

    protected $casts = [
        'expiry_date' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function openingStockEntry()
    {
        return $this->belongsTo(OpeningStockEntry::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batchInventory()
    {
        return $this->morphOne(BatchInventory::class, 'source');
    }
}
