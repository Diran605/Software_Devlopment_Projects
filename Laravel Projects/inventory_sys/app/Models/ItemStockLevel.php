<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'branch_id', 'department_id', 'item_id',
    'qty_on_hand', 'qty_reserved', 'reorder_level', 'unit_cost'
])]
class ItemStockLevel extends Model
{
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
