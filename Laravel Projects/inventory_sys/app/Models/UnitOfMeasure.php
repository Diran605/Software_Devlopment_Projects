<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'name', 'abbreviation', 'is_active'])]
class UnitOfMeasure extends Model
{
    use SoftDeletes;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function packagingTypes()
    {
        return $this->hasMany(PackagingType::class, 'base_uom_id');
    }
}
