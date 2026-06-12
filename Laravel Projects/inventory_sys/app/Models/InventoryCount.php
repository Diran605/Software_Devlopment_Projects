<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id',
    'department_id',
    'created_by',
    'approved_by',
    'posted_by',
    'count_number',
    'status',
    'count_at',
    'approved_at',
    'posted_at',
    'notes'
])]
class InventoryCount extends Model
{
    use SoftDeletes;

    protected $casts = [
        'count_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'status' => 'string',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function lines()
    {
        return $this->hasMany(InventoryCountLine::class);
    }
}
