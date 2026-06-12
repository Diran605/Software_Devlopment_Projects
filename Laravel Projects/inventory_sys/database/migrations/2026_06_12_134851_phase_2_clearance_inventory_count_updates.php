<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update clearance_rules table to match spec
        Schema::table('clearance_rules', function (Blueprint $table) {
            // Check if columns exist before dropping
            if (Schema::hasColumn('clearance_rules', 'trigger_type') && Schema::hasColumn('clearance_rules', 'trigger_value')) {
                $table->dropColumn(['trigger_type', 'trigger_value']);
            }
            // Check if column exists before renaming
            if (Schema::hasColumn('clearance_rules', 'discount_percent')) {
                $table->renameColumn('discount_percent', 'discount');
            }
            // Add days_min and days_max if they don't exist
            if (!Schema::hasColumn('clearance_rules', 'days_min')) {
                $table->unsignedInteger('days_min')->nullable();
            }
            if (!Schema::hasColumn('clearance_rules', 'days_max')) {
                $table->unsignedInteger('days_max')->nullable();
            }
        });

        // 2. Update clearance_items table to match spec
        Schema::table('clearance_items', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('clearance_items', 'batch_inventory_id')) {
                $table->foreignId('batch_inventory_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('clearance_items', 'qty_flagged')) {
                $table->unsignedInteger('qty_flagged')->default(0);
            }
            if (!Schema::hasColumn('clearance_items', 'days_to_expiry')) {
                $table->integer('days_to_expiry')->nullable();
            }
            if (!Schema::hasColumn('clearance_items', 'urgency_status')) {
                $table->string('urgency_status')->nullable();
            }
            if (!Schema::hasColumn('clearance_items', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'declined', 'actioned'])->default('pending');
            }
            if (!Schema::hasColumn('clearance_items', 'action_type')) {
                $table->enum('action_type', ['sell', 'donate', 'dispose'])->nullable();
            }
            if (!Schema::hasColumn('clearance_items', 'qty_to_move')) {
                $table->unsignedInteger('qty_to_move')->nullable();
            }
            if (!Schema::hasColumn('clearance_items', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // 3. Create clearance_stock table if not exists
        if (!Schema::hasTable('clearance_stock')) {
            Schema::create('clearance_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('clearance_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('item_id')->constrained()->restrictOnDelete();
                $table->foreignId('batch_inventory_id')->constrained()->restrictOnDelete();
                $table->string('batch_number');
                $table->date('expiry_date')->nullable();
                $table->unsignedInteger('qty_on_clearance');
                $table->unsignedInteger('qty_remaining');
                $table->decimal('original_price', 12, 2);
                $table->decimal('clearance_price', 12, 2);
                $table->decimal('unit_cost', 12, 2);
                $table->softDeletes();
                $table->timestamps();

                $table->index(['branch_id', 'item_id']);
                $table->index(['clearance_item_id']);
            });
        }

        // 4. Create clearance_actions table if not exists
        if (!Schema::hasTable('clearance_actions')) {
            Schema::create('clearance_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('clearance_stock_id');
                $table->foreign('clearance_stock_id')->references('id')->on('clearance_stock')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained()->restrictOnDelete();
                $table->foreignId('batch_inventory_id')->constrained()->restrictOnDelete();
                $table->enum('action_type', ['sell', 'donate', 'dispose']);
                $table->unsignedInteger('qty');
                $table->decimal('loss_value', 12, 2)->nullable();
                $table->unsignedBigInteger('sales_order_id')->nullable();
                $table->unsignedBigInteger('disposal_id')->nullable();
                $table->unsignedBigInteger('donation_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. Add is_clearance to sales_orders if not exists
        if (!Schema::hasColumn('sales_orders', 'is_clearance')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->boolean('is_clearance')->default(false);
            });
        }

        // 6. Update stock_movements enum
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
                'opening_stock', 'goods_receipt', 'sale', 'transfer_out', 'transfer_in',
                'count_adjustment', 'disposal', 'donation', 'reversal', 'clearance_out', 'clearance_sale'
            )");
        } catch (\Exception $e) {
            // Ignore if enum already updated
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert stock_movements enum
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
                'opening_stock', 'goods_receipt', 'sale', 'transfer_out', 'transfer_in',
                'count_adjustment', 'disposal', 'donation', 'reversal'
            )");
        } catch (\Exception $e) {
            // Ignore
        }

        // 2. Remove is_clearance from sales_orders
        if (Schema::hasColumn('sales_orders', 'is_clearance')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('is_clearance');
            });
        }

        // 3. Drop clearance_actions
        Schema::dropIfExists('clearance_actions');

        // 4. Drop clearance_stock
        Schema::dropIfExists('clearance_stock');
    }
};
