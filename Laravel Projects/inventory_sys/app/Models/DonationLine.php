<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'donation_id', 'item_id', 'batch_inventory_id',
    'qty_donated', 'unit_cost', 'total_value', 'notes'
])]
class DonationLine extends Model
{
    use SoftDeletes;

    public function donation()
    {
        return $this->belongsTo(Donation::class);
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
