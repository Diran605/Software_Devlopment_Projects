<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });

        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('abbreviation');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });

        Schema::create('packaging_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('units_per_pack');
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('item_categories')->restrictOnDelete();
            $table->foreignId('uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('packaging_type_id')->nullable()->constrained('packaging_types')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('min_selling_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->unsignedInteger('reorder_quantity')->default(0);
            $table->boolean('is_packaged')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'name']);
            $table->index(['branch_id', 'category_id']);
        });

        Schema::create('item_stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('qty_on_hand')->default(0);
            $table->integer('qty_reserved')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'department_id', 'item_id']);
            $table->index(['branch_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_stock_levels');
        Schema::dropIfExists('items');
        Schema::dropIfExists('packaging_types');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('item_categories');
    }
};
