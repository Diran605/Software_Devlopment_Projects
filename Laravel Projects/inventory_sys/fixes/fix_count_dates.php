<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lines = DB::table("inventory_count_lines")
    ->join("inventory_counts", "inventory_counts.id", "=", "inventory_count_lines.inventory_count_id")
    ->select("inventory_count_lines.id as line_id", "inventory_counts.count_at")
    ->get();

foreach ($lines as $line) {
    DB::table("stock_movements")
        ->where("reference_type", "App\\\\Models\\\\InventoryCountLine")
        ->where("reference_id", $line->line_id)
        ->update(["moved_at" => $line->count_at]);
}

echo "Count dates synced successfully.\n";

