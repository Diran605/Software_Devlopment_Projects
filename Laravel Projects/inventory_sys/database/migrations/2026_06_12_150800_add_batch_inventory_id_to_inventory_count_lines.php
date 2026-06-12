<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_count_lines', function (Blueprint $table) {
            $table->foreignId('batch_inventory_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
            $table->decimal('selling_price', 12, 2)->nullable()->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_count_lines', function (Blueprint $table) {
            $table->dropForeign(['batch_inventory_id']);
            $table->dropColumn(['batch_inventory_id', 'selling_price']);
        });
    }
};
