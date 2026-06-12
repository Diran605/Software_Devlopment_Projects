<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_inventory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->enum('movement_type', [
                'opening_stock',
                'goods_receipt',
                'sale',
                'transfer_out',
                'transfer_in',
                'count_adjustment',
                'disposal',
                'donation',
                'reversal',
            ]);
            $table->unsignedInteger('qty_in')->default(0);
            $table->unsignedInteger('qty_out')->default(0);
            $table->integer('qty_before');
            $table->integer('qty_after');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
            $table->index(['branch_id', 'item_id', 'moved_at']);
            $table->index(['branch_id', 'movement_type', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['batch_inventory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
