<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_stock_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('posted_by')->constrained('users')->restrictOnDelete();
            $table->string('entry_number')->unique();
            $table->timestamp('posted_at');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'posted_at']);
        });

        Schema::create('opening_stock_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_stock_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('qty_on_hand');
            $table->decimal('unit_cost', 12, 2);
            $table->boolean('is_consumed')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->unsignedSmallInteger('edit_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['opening_stock_entry_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_stock_lines');
        Schema::dropIfExists('opening_stock_entries');
    }
};
