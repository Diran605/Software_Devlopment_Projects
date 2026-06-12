<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'name', 'days_min', 'days_max', 'discount', 'is_active'
])]
class ClearanceRule extends Model
{
    use SoftDeletes;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function clearanceItems()
    {
        return $this->hasMany(ClearanceItem::class);
    }
}
