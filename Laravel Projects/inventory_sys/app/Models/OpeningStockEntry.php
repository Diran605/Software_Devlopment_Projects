<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'department_id', 'posted_by',
    'entry_number', 'posted_at', 'notes'
])]
class OpeningStockEntry extends Model
{
    use SoftDeletes;

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function openingStockLines()
    {
        return $this->hasMany(OpeningStockLine::class);
    }
}
