<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();
DB::statement("SET FOREIGN_KEY_CHECKS=0;");

// Movements for Item A
DB::table("stock_movements")->insert([
    ["item_id" => 9991, "branch_id" => 1, "recorded_by" => 1, "movement_type" => "opening_stock", "qty_in" => 50, "qty_out" => 0, "qty_before" => 0, "qty_after" => 50, "moved_at" => "2026-08-30 10:00:00"],
    ["item_id" => 9991, "branch_id" => 1, "recorded_by" => 1, "movement_type" => "sale", "qty_in" => 0, "qty_out" => 10, "qty_before" => 50, "qty_after" => 40, "moved_at" => "2026-08-31 15:00:00"]
]);

// Movements for Item B
DB::table("stock_movements")->insert([
    ["item_id" => 9992, "branch_id" => 1, "recorded_by" => 1, "movement_type" => "opening_stock", "qty_in" => 50, "qty_out" => 0, "qty_before" => 0, "qty_after" => 50, "moved_at" => "2026-09-05 10:00:00"]
]);

// Report logic
$from = "2026-09-01";
$to = "2026-09-30";

$openingRows = DB::table("stock_movements")
    ->select("item_id", DB::raw("SUM(qty_in) - SUM(qty_out) as net_qty"))
    ->whereDate("moved_at", "<", $from)
    ->groupBy("item_id")
    ->pluck("net_qty", "item_id");

$newStockRows = DB::table("stock_movements")
    ->select("item_id", DB::raw("SUM(qty_in) as qty_in"))
    ->whereIn("movement_type", ["goods_receipt", "opening_stock"])
    ->whereDate("moved_at", ">=", $from)
    ->whereDate("moved_at", "<=", $to)
    ->groupBy("item_id")
    ->pluck("qty_in", "item_id");

echo "Item A Opening Stock: " . ($openingRows[9991] ?? 0) . "\n";
echo "Item A New Stock: " . ($newStockRows[9991] ?? 0) . "\n";
echo "Item B Opening Stock: " . ($openingRows[9992] ?? 0) . "\n";
echo "Item B New Stock: " . ($newStockRows[9992] ?? 0) . "\n";

DB::rollBack();

