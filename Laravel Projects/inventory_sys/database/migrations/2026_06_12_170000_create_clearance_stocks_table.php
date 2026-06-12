<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clearance_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_inventory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('qty_on_clearance')->default(0);
            $table->unsignedInteger('qty_remaining')->default(0);
            $table->decimal('original_price', 12, 2)->default(0);
            $table->decimal('clearance_price', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'item_id']);
            $table->index(['clearance_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_stocks');
    }
};
