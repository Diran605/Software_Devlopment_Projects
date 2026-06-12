<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Copy data from old clearance_stock to new clearance_stocks if old table exists
        if (Schema::hasTable('clearance_stock')) {
            \Illuminate\Support\Facades\DB::statement('
                INSERT INTO clearance_stocks 
                (branch_id, department_id, clearance_item_id, item_id, batch_inventory_id, 
                 batch_number, expiry_date, qty_on_clearance, qty_remaining, original_price, 
                 clearance_price, unit_cost, deleted_at, created_at, updated_at)
                SELECT branch_id, department_id, clearance_item_id, item_id, batch_inventory_id,
                       batch_number, expiry_date, qty_on_clearance, qty_remaining, original_price,
                       clearance_price, unit_cost, NULL, created_at, updated_at
                FROM clearance_stock
                WHERE NOT EXISTS (
                    SELECT 1 FROM clearance_stocks cs 
                    WHERE cs.id = clearance_stock.id
                )
            ');
        }

        // 2. Update clearance_actions to reference clearance_stocks
        Schema::table('clearance_actions', function (Blueprint $table) {
            // Drop old FK if it exists
            try {
                $table->dropForeign(['clearance_stock_id']);
            } catch (\Exception $e) {
                // Already dropped or doesn't exist
            }

            // Rename the column to clearance_stocks_id
            if (Schema::hasColumn('clearance_actions', 'clearance_stock_id')) {
                $table->renameColumn('clearance_stock_id', 'clearance_stocks_id');
            }
        });

        // 3. Add proper FK to clearance_stocks
        Schema::table('clearance_actions', function (Blueprint $table) {
            if (!Schema::hasColumn('clearance_actions', 'clearance_stocks_id')) {
                $table->foreignId('clearance_stocks_id')->nullable()->constrained()->cascadeOnDelete();
            } else {
                // Make it a proper foreign key if it's not already
                try {
                    $table->foreign('clearance_stocks_id')->references('id')->on('clearance_stocks')->cascadeOnDelete();
                } catch (\Exception $e) {
                    // FK might already exist
                }
            }
        });

        // 4. Drop the old clearance_stock table
        if (Schema::hasTable('clearance_stock')) {
            Schema::dropIfExists('clearance_stock');
        }
    }

    public function down(): void
    {
        // Revert is complex, so just document that manual intervention may be needed
        // The safest approach is to not rollback this migration
    }
};
