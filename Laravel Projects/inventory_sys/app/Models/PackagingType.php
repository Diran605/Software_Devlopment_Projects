<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'base_uom_id', 'name', 'units_per_pack', 'description'])]
class PackagingType extends Model
{
    use SoftDeletes;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function baseUom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_uom_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function grnLineItems()
    {
        return $this->hasMany(GrnLineItem::class);
    }

    public function salesOrderLines()
    {
        return $this->hasMany(SalesOrderLine::class);
    }
}
