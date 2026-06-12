<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('trigger_type', ['expiry_days', 'slow_moving_days']);
            $table->unsignedInteger('trigger_value'); // days
            $table->decimal('discount_percent', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('clearance_rules')->nullOnDelete();
            $table->decimal('original_price', 12, 2);
            $table->decimal('clearance_price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_items');
        Schema::dropIfExists('clearance_rules');
    }
};
