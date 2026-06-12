<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'disposal_id', 'item_id', 'batch_inventory_id',
    'qty_disposed', 'unit_cost', 'total_value', 'notes'
])]
class DisposalLine extends Model
{
    use SoftDeletes;

    public function disposal()
    {
        return $this->belongsTo(Disposal::class);
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
