<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'category_id', 'uom_id', 'packaging_type_id',
    'name', 'description', 'unit_cost', 'min_selling_price',
    'selling_price', 'reorder_level', 'reorder_quantity',
    'is_packaged', 'is_active'
])]
class Item extends Model
{
    use SoftDeletes;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function packagingType()
    {
        return $this->belongsTo(PackagingType::class);
    }

    public function itemStockLevels()
    {
        return $this->hasMany(ItemStockLevel::class);
    }

    public function openingStockLines()
    {
        return $this->hasMany(OpeningStockLine::class);
    }

    public function grnLineItems()
    {
        return $this->hasMany(GrnLineItem::class);
    }

    public function salesOrderLines()
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function batchInventory()
    {
        return $this->hasMany(BatchInventory::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
