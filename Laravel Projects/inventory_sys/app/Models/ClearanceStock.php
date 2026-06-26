<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id',
    'department_id',
    'clearance_item_id',
    'item_id',
    'batch_inventory_id',
    'batch_number',
    'expiry_date',
    'qty_on_clearance',
    'qty_remaining',
    'original_price',
    'clearance_price',
    'unit_cost'
])]
class ClearanceStock extends Model
{
    use SoftDeletes;

    protected $table = 'clearance_stocks';

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function clearanceItem()
    {
        return $this->belongsTo(ClearanceItem::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batchInventory()
    {
        return $this->belongsTo(BatchInventory::class);
    }

    public function clearanceActions()
    {
        return $this->hasMany(ClearanceAction::class);
    }
}
