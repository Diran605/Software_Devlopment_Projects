<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fix:stock-movement-dates')]
#[Description('Retroactively syncs stock movement dates with their logical transaction dates')]
class FixStockMovementDates extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Syncing Sales Orders...");
        $salesOrders = \Illuminate\Support\Facades\DB::table("sales_orders")->get();
        foreach ($salesOrders as $so) {
            \Illuminate\Support\Facades\DB::table("stock_movements")
                ->where("reference_type", "App\\Models\\SalesOrder")
                ->where("reference_id", $so->id)
                ->update(["moved_at" => $so->sold_at]);
        }

        $this->info("Syncing Goods Received Notes...");
        $grns = \Illuminate\Support\Facades\DB::table("goods_received_notes")->get();
        foreach ($grns as $grn) {
            \Illuminate\Support\Facades\DB::table("stock_movements")
                ->where("reference_type", "App\\Models\\GoodsReceivedNote")
                ->where("reference_id", $grn->id)
                ->update(["moved_at" => $grn->received_at]);
        }

        $this->info("Syncing Opening Stock Entries...");
        $oses = \Illuminate\Support\Facades\DB::table("opening_stock_entries")->get();
        foreach ($oses as $ose) {
            \Illuminate\Support\Facades\DB::table("stock_movements")
                ->where("reference_type", "App\\Models\\OpeningStockEntry")
                ->where("reference_id", $ose->id)
                ->update(["moved_at" => $ose->posted_at]);
        }

        $this->info("Syncing Stock Transfers...");
        $sts = \Illuminate\Support\Facades\DB::table("stock_transfers")->get();
        foreach ($sts as $st) {
            \Illuminate\Support\Facades\DB::table("stock_movements")
                ->where("reference_type", "App\\Models\\StockTransfer")
                ->where("reference_id", $st->id)
                ->where("movement_type", "transfer_out")
                ->update(["moved_at" => $st->transferred_at]);
                
            \Illuminate\Support\Facades\DB::table("stock_movements")
                ->where("reference_type", "App\\Models\\StockTransfer")
                ->where("reference_id", $st->id)
                ->where("movement_type", "transfer_in")
                ->update(["moved_at" => $st->received_at]);
        }

        $this->info("Syncing Inventory Counts...");
        $lines = \Illuminate\Support\Facades\DB::table("inventory_count_lines")
            ->join("inventory_counts", "inventory_counts.id", "=", "inventory_count_lines.inventory_count_id")
            ->select("inventory_count_lines.id as line_id", "inventory_counts.count_at")
            ->get();
        
        foreach ($lines as $line) {
            \Illuminate\Support\Facades\DB::table("stock_movements")
                ->where("reference_type", "App\\Models\\InventoryCountLine")
                ->where("reference_id", $line->line_id)
                ->update(["moved_at" => $line->count_at]);
        }

        $this->info("All historical stock movement dates have been successfully synced!");
    }
}
