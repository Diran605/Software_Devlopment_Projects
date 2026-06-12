<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->foreignId('batch_inventory_id')->nullable()->after('item_id')->constrained('batch_inventories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropForeign(['batch_inventory_id']);
            $table->dropColumn('batch_inventory_id');
        });
    }
};
