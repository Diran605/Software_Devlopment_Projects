<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('donation_number')->unique();
            $table->string('recipient');
            $table->text('notes')->nullable();
            $table->timestamp('donated_at');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'donated_at']);
        });

        Schema::create('donation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_inventory_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_donated');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_value', 12, 2);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['donation_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_lines');
        Schema::dropIfExists('donations');
    }
};
