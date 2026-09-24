<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fix Sales Orders
$salesOrders = DB::table("sales_orders")->get();
foreach ($salesOrders as $so) {
    DB::table("stock_movements")
        ->where("reference_type", "App\\\\Models\\\\SalesOrder")
        ->where("reference_id", $so->id)
        ->update(["moved_at" => $so->sold_at]);
}

// Fix Goods Received Notes
$grns = DB::table("goods_received_notes")->get();
foreach ($grns as $grn) {
    DB::table("stock_movements")
        ->where("reference_type", "App\\\\Models\\\\GoodsReceivedNote")
        ->where("reference_id", $grn->id)
        ->update(["moved_at" => $grn->received_at]);
}

// Fix Opening Stock Entries
$oses = DB::table("opening_stock_entries")->get();
foreach ($oses as $ose) {
    DB::table("stock_movements")
        ->where("reference_type", "App\\\\Models\\\\OpeningStockEntry")
        ->where("reference_id", $ose->id)
        ->update(["moved_at" => $ose->posted_at]);
}

// Fix Stock Transfers
$sts = DB::table("stock_transfers")->get();
foreach ($sts as $st) {
    DB::table("stock_movements")
        ->where("reference_type", "App\\\\Models\\\\StockTransfer")
        ->where("reference_id", $st->id)
        ->where("movement_type", "transfer_out")
        ->update(["moved_at" => $st->transferred_at]);
        
    DB::table("stock_movements")
        ->where("reference_type", "App\\\\Models\\\\StockTransfer")
        ->where("reference_id", $st->id)
        ->where("movement_type", "transfer_in")
        ->update(["moved_at" => $st->received_at]);
}

echo "Dates synced successfully.\n";

