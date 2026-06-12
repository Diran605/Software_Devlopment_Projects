<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'branch_id', 'department_id', 'item_id', 'batch_inventory_id',
    'recorded_by', 'movement_type', 'qty_in', 'qty_out',
    'qty_before', 'qty_after', 'unit_cost', 'unit_price',
    'reference_type', 'reference_id', 'batch_number',
    'expiry_date', 'notes', 'moved_at'
])]
class StockMovement extends Model
{
    protected $casts = [
        'expiry_date' => 'datetime',
        'moved_at' => 'datetime',
    ];
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

    public function batchInventory()
    {
        return $this->belongsTo(BatchInventory::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
