<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'department_id', 'item_id', 'source_type', 'source_id',
    'batch_number', 'expiry_date', 'qty_received', 'qty_remaining',
    'unit_cost', 'received_at'
])]
class BatchInventory extends Model
{
    use SoftDeletes;
    
    protected $table = 'batch_inventories';

    protected $casts = [
        'expiry_date' => 'datetime',
        'received_at' => 'datetime',
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

    public function source()
    {
        return $this->morphTo();
    }

    public function salesStockAllocations()
    {
        return $this->hasMany(SalesStockAllocation::class);
    }

    public function stockTransferLines()
    {
        return $this->hasMany(StockTransferLine::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
