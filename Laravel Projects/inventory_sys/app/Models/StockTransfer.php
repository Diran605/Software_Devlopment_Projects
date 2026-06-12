<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'transfer_number', 'transfer_type', 'from_branch_id',
    'from_department_id', 'to_branch_id', 'to_department_id',
    'requested_by', 'approved_by', 'status', 'notes',
    'transferred_at', 'received_at'
])]
class StockTransfer extends Model
{
    use SoftDeletes;

    protected $casts = [
        'transferred_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function stockTransferLines()
    {
        return $this->hasMany(StockTransferLine::class);
    }
}
