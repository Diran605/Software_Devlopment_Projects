<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('qty_received');
            $table->integer('qty_remaining');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamp('received_at');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'item_id', 'expiry_date', 'received_at'], 'batch_inv_branch_item_expiry_rec_idx');
            $table->index(['branch_id', 'department_id', 'item_id'], 'batch_inv_branch_dept_item_idx');
            $table->index(['source_type', 'source_id'], 'batch_inv_source_type_source_id_idx');
            $table->index(['item_id', 'qty_remaining'], 'batch_inv_item_qty_remaining_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_inventories');
    }
};
