<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->foreignId('packaging_type_id')->nullable()->constrained('packaging_types')->nullOnDelete();
            $table->string('entry_mode')->default('loose'); // loose or pack
            $table->integer('pack_quantity')->nullable();
            $table->integer('units_per_pack')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['packaging_type_id']);
            $table->dropColumn(['packaging_type_id', 'entry_mode', 'pack_quantity', 'units_per_pack']);
        });
    }
};

