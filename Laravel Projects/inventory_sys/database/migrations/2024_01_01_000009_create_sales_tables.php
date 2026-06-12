<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('served_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('order_number')->unique();
            $table->timestamp('sold_at');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('cogs_total', 12, 2)->default(0);
            $table->decimal('gross_profit', 12, 2)->default(0);
            $table->decimal('amount_tendered', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'sold_at']);
            $table->index(['branch_id', 'customer_id']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('packaging_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('entry_mode', ['unit', 'pack'])->default('unit');
            $table->unsignedInteger('pack_quantity')->nullable();
            $table->unsignedInteger('units_per_pack')->nullable();
            $table->unsignedInteger('qty_sold');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->decimal('line_cost', 12, 2)->default(0);
            $table->decimal('gross_profit', 12, 2)->default(0);
            $table->boolean('is_low_margin')->default(false);
            $table->boolean('is_negative_margin')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->unsignedSmallInteger('edit_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['sales_order_id', 'item_id']);
        });

        Schema::create('sales_stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_inventory_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_allocated');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
            $table->index(['sales_order_line_id']);
            $table->index(['batch_inventory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_stock_allocations');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
