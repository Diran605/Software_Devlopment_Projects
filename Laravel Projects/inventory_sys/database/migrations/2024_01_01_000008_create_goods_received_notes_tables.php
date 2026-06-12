<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('grn_number')->unique();
            $table->string('supplier_reference_no')->nullable();
            $table->timestamp('received_at');
            $table->unsignedInteger('total_qty')->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'received_at']);
            $table->index(['branch_id', 'supplier_id']);
        });

        Schema::create('grn_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('packaging_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('entry_mode', ['unit', 'pack'])->default('unit');
            $table->unsignedInteger('pack_quantity')->nullable();
            $table->unsignedInteger('units_per_pack')->nullable();
            $table->unsignedInteger('qty_received');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['grn_id', 'item_id']);
            $table->index(['item_id', 'batch_number']);
            $table->index(['item_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_line_items');
        Schema::dropIfExists('goods_received_notes');
    }
};
