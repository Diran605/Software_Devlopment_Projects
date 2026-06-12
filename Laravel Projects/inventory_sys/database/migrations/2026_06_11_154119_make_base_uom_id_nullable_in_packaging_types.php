<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packaging_types', function (Blueprint $table) {
            // Drop the foreign key first so we can change the column
            $table->dropForeign(['base_uom_id']);

            // Make base_uom_id nullable
            $table->unsignedBigInteger('base_uom_id')->nullable()->change();

            // Give units_per_pack a default of 1
            $table->unsignedInteger('units_per_pack')->default(1)->change();

            // Re-add the foreign key as nullable-compatible (nullOnDelete so orphan rows are handled)
            $table->foreign('base_uom_id')->references('id')->on('unit_of_measures')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packaging_types', function (Blueprint $table) {
            $table->dropForeign(['base_uom_id']);
            $table->unsignedBigInteger('base_uom_id')->nullable(false)->change();
            $table->unsignedInteger('units_per_pack')->change();
            $table->foreign('base_uom_id')->references('id')->on('unit_of_measures');
        });
    }
};
