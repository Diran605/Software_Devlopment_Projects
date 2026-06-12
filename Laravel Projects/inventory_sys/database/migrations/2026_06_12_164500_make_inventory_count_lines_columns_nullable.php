<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_count_lines', function (Blueprint $table) {
            $table->unsignedInteger('qty_counted')->nullable()->change();
            $table->integer('qty_variance')->nullable()->change();
            $table->decimal('variance_value', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_count_lines', function (Blueprint $table) {
            $table->unsignedInteger('qty_counted')->nullable(false)->change();
            $table->integer('qty_variance')->nullable(false)->change();
            $table->decimal('variance_value', 12, 2)->nullable(false)->change();
        });
    }
};
