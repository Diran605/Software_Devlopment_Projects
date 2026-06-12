<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __invoke(SalesOrder $order)
    {
        // Authorize: user must belong to same branch as the order
        abort_unless(
            auth()->user()->branches->contains($order->branch_id)
            || auth()->user()->hasRole('super-admin'),
            403
        );

        $order->load([
            'salesOrderLines.item.uom',
            'salesOrderLines.salesStockAllocations.batchInventory',
            'customer',
            'servedBy',
            'branch',
            'department',
        ]);

        return view('receipts.sales', compact('order'));
    }
}
