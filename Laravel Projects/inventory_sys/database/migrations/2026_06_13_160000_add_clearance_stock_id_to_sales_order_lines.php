<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_order_lines', 'clearance_stock_id')) {
                $table->foreignId('clearance_stock_id')
                    ->nullable()
                    ->after('batch_inventory_id')
                    ->constrained('clearance_stocks')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table) {
            if (Schema::hasColumn('sales_order_lines', 'clearance_stock_id')) {
                $table->dropConstrainedForeignId('clearance_stock_id');
            }
        });
    }
};
