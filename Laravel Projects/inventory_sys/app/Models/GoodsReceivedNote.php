<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'department_id', 'supplier_id', 'purchase_order_id',
    'received_by', 'grn_number', 'supplier_reference_no',
    'received_at', 'total_qty', 'total_cost', 'notes'
])]
class GoodsReceivedNote extends Model
{
    use SoftDeletes;

    protected $casts = [
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function grnLineItems()
    {
        return $this->hasMany(GrnLineItem::class, 'grn_id');
    }
}
