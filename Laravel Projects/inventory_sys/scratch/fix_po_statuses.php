<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchaseOrder;

$pos = PurchaseOrder::whereIn('status', ['fully_received', 'partially_received'])->get();

$fixedCount = 0;
foreach ($pos as $po) {
    $totalOrdered = $po->purchaseOrderLines->sum('qty_ordered');
    $totalReceived = $po->purchaseOrderLines->sum('qty_received');

    if ($totalReceived == 0) {
        $po->status = 'issued';
        $po->save();
        echo "Fixed PO #{$po->po_number}: status reverted to 'issued'\n";
        $fixedCount++;
    } elseif ($totalReceived < $totalOrdered && $po->status === 'fully_received') {
        $po->status = 'partially_received';
        $po->save();
        echo "Fixed PO #{$po->po_number}: status reverted to 'partially_received'\n";
        $fixedCount++;
    }
}

echo "Done! Fixed {$fixedCount} PO statuses.\n";
