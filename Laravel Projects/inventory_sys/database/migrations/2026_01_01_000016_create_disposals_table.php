<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('disposal_number')->unique();
            $table->enum('reason', ['damage', 'expired', 'obsolescence', 'other']);
            $table->text('notes')->nullable();
            $table->timestamp('disposed_at');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'disposed_at']);
        });

        Schema::create('disposal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_inventory_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_disposed');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_value', 12, 2);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['disposal_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_lines');
        Schema::dropIfExists('disposals');
    }
};
