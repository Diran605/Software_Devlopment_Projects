<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'department_id', 'served_by', 'customer_id',
    'customer_name', 'order_number', 'sold_at', 'subtotal',
    'discount_total', 'grand_total', 'cogs_total', 'gross_profit',
    'amount_tendered', 'notes', 'is_clearance'
])]
class SalesOrder extends Model
{
    use SoftDeletes;

    protected $casts = [
        'sold_at' => 'datetime',
        'is_clearance' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrderLines()
    {
        return $this->hasMany(SalesOrderLine::class);
    }
}
