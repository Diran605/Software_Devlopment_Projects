<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'branch_id', 'deleted_by', 'record_type', 'record_id',
    'record_number', 'reason', 'snapshot', 'stock_reversal', 'deleted_at'
])]
class DeletionLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'stock_reversal' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
