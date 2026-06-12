Inventory Management System --- MVP Build Specification This document is
the single source of truth for building the MVP. Read it fully before
writing any code. Every decision, constraint, and business rule is here.

1.  Project Overview A multi-branch retail inventory management system
    built with Laravel and Filament . Core purpose: Track stock from
    purchase order → goods receiving → batch storage → sale, with full
    FIFO traceability per batch, multi-branch/department support, and a
    clean audit trail on every action. Stack: PHP Laravel Filament
    (admin panel) MySQL Spatie Laravel Permission (roles & permissions)

2.  Setup Instructions

```{=html}
<!-- -->
```
    laravel new inventory-app
    cd inventory-app

    composer require filament/filament:"^3.0" -W
    php artisan filament:install --panels

    composer require spatie/laravel-permission
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

Add HasRoles trait to the User model:

    use Spatie\Permission\Traits\HasRoles;

    class User extends Authenticatable
    {

    use HasRoles;

} Configure config/auth.php to use the users table. Configure .env for
MySQL.

3.  Database Migrations Run migrations in this exact order. Copy each
    file into database/migrations/. All tables use soft deletes
    (deleted_at). Nothing is ever hard deleted except through explicit
    cascade rules defined in the schema. File 1 ---
    0001_01_01_000001_create_branches_table.php

```{=html}
<!-- -->
```
    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');          // e.g. WHSE, SHELF, FRIDGE
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
    }

}; File 2 --- 0001_01_01_000002_create_users_table.php

    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['branch_id', 'user_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('users');
    }

}; File 3 --- 2024_01_01_000003_create_catalogue_tables.php

    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });

        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');               // e.g. Piece, Carton, Bottle, Kg
            $table->string('abbreviation');        // e.g. pcs, ctn, btl, kg
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });

        Schema::create('packaging_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->string('name');               // e.g. Carton of 12, Crate of 24
            $table->unsignedInteger('units_per_pack');
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('item_categories')->restrictOnDelete();
            $table->foreignId('uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('packaging_type_id')->nullable()->constrained('packaging_types')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('min_selling_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->unsignedInteger('reorder_quantity')->default(0);
            $table->boolean('is_packaged')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'name']);
            $table->index(['branch_id', 'category_id']);
        });

        Schema::create('item_stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('qty_on_hand')->default(0);
            $table->integer('qty_reserved')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'department_id', 'item_id']);
            $table->index(['branch_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_stock_levels');
        Schema::dropIfExists('items');
        Schema::dropIfExists('packaging_types');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('item_categories');
    }

}; File 4 --- 2024_01_01_000004_create_suppliers_customers_tables.php

    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'name']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
    }

}; File 5 --- 2024_01_01_000005_create_opening_stock_tables.php

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
            $table->boolean('is_consumed')->default(false); // blocks edit if batch already used in a sale
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

}; File 6 --- 2024_01_01_000006_create_batch_inventory_table.php

    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('batch_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('source_type');           // App\Models\OpeningStockLine | GrnLineItem | StockTransferLine
            $table->unsignedBigInteger('source_id');
            $table->string('batch_number');          // always required
            $table->date('expiry_date')->nullable(); // nullable for non-perishables
            $table->unsignedInteger('qty_received');
            $table->integer('qty_remaining');
            $table->decimal('unit_cost', 12, 2);     // locked at receipt time
            $table->timestamp('received_at');        // FIFO tie-breaker
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'item_id', 'expiry_date', 'received_at']);
            $table->index(['branch_id', 'department_id', 'item_id']);
            $table->index(['source_type', 'source_id']);
            $table->index(['item_id', 'qty_remaining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_inventory');
    }

}; File 7 --- 2024_01_01_000007_create_purchase_orders_tables.php

    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('po_number')->unique();
            $table->enum('status', ['draft','issued','partially_received','fully_received','cancelled'])->default('draft');
            $table->timestamp('ordered_at')->nullable();
            $table->date('expected_delivery_at')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'supplier_id']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_ordered');
            $table->unsignedInteger('qty_received')->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['purchase_order_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }

}; File 8 --- 2024_01_01_000008_create_goods_received_notes_tables.php

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
            $table->string('batch_number');          // always required
            $table->date('expiry_date')->nullable(); // optional for non-perishables
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

}; File 9 --- 2024_01_01_000009_create_sales_tables.php

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
            $table->string('customer_name')->nullable(); // walk-in fallback
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
            $table->decimal('unit_cost', 12, 2)->default(0);  // FIFO cost at sale time
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

}; File 10 --- 2024_01_01_000010_create_stock_transfers_tables.php

    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('transfer_number')->unique();
            $table->enum('transfer_type', ['inter_department', 'inter_branch']);
            $table->foreignId('from_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft','pending_approval','approved','in_transit','received','cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
            $table->index(['from_branch_id', 'to_branch_id']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_inventory_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_requested');
            $table->unsignedInteger('qty_transferred')->default(0);
            $table->unsignedInteger('qty_received')->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['stock_transfer_id', 'item_id']);
            $table->index(['batch_inventory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }

}; File 11 --- 2024_01_01_000011_create_stock_movements_table.php

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

}; File 12 --- 2024_01_01_000012_create_log_tables.php

    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up(): void
    {
        Schema::create('deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deleted_by')->constrained('users')->restrictOnDelete();
            $table->string('record_type');
            $table->unsignedBigInteger('record_id');
            $table->string('record_number');
            $table->text('reason');
            $table->json('snapshot');         // full record + all lines at deletion time
            $table->json('stock_reversal');   // which batches were restocked and by how much
            $table->timestamp('deleted_at');
            $table->index(['branch_id', 'record_type', 'deleted_at']);
            $table->index(['record_type', 'record_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('event', ['created','updated','deleted','approved','rejected','posted','cancelled','received','login','logout']);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'event', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('deletion_logs');
    }

};

4.  Seeders database/seeders/RolesAndPermissionsSeeder.php Permissions
    use the format action.module. Roles are seeded with exact permission
    sets.

```{=html}
<!-- -->
```
    <?php

namespace Database`\Seeders`{=tex};

    use Illuminate\Database\Seeder;
    use Spatie\Permission\Models\Permission;
    use Spatie\Permission\Models\Role;

    class RolesAndPermissionsSeeder extends Seeder
    {

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'branches'             => ['view', 'create', 'edit', 'delete'],
            'departments'          => ['view', 'create', 'edit', 'delete'],
            'users'                => ['view', 'create', 'edit', 'delete'],
            'roles'                => ['view', 'create', 'edit', 'delete'],
            'items'                => ['view', 'create', 'edit', 'delete'],
            'item-categories'      => ['view', 'create', 'edit', 'delete'],
            'unit-of-measures'     => ['view', 'create', 'edit', 'delete'],
            'packaging-types'      => ['view', 'create', 'edit', 'delete'],
            'suppliers'            => ['view', 'create', 'edit', 'delete'],
            'customers'            => ['view', 'create', 'edit', 'delete'],
            'purchase-orders'      => ['view', 'create', 'edit', 'delete', 'approve', 'cancel'],
            'opening-stock'        => ['view', 'create', 'edit', 'delete'],
            'goods-received-notes' => ['view', 'create', 'delete'],
            'sales-orders'         => ['view', 'create', 'edit', 'delete'],
            'stock-transfers'      => ['view', 'create', 'delete', 'approve', 'receive'],
            'stock-movements'      => ['view'],
            'audit-logs'           => ['view'],
            'deletion-logs'        => ['view'],
            // Phase 2 — seeded now for future role assignment
            'inventory-counts'     => ['view', 'create', 'edit', 'delete', 'approve', 'post'],
            'clearance-manager'    => ['view', 'create', 'edit', 'delete', 'approve'],
            'clearance-sales'      => ['view', 'create', 'delete'],
            'disposals'            => ['view', 'create', 'delete'],
            'donations'            => ['view', 'create', 'delete'],
            'expenses'             => ['view', 'create', 'edit', 'delete'],
            'expense-categories'   => ['view', 'create', 'edit', 'delete'],
            'reports'              => ['view'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}.{$module}"]);
            }
        }

        // super-admin: everything
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        // branch-manager: everything (branch-scoped in policies)
        $branchManager = Role::firstOrCreate(['name' => 'branch-manager']);
        $branchManager->syncPermissions(Permission::all());

        // inventory-manager: stock operations
        $inventoryManager = Role::firstOrCreate(['name' => 'inventory-manager']);
        $inventoryManager->syncPermissions(
            Permission::whereIn('name', $this->prefixed([
                'items'                => ['view'],
                'item-categories'      => ['view'],
                'unit-of-measures'     => ['view'],
                'packaging-types'      => ['view'],
                'suppliers'            => ['view', 'create', 'edit', 'delete'],
                'purchase-orders'      => ['view', 'create', 'edit', 'delete', 'approve', 'cancel'],
                'opening-stock'        => ['view', 'create', 'edit', 'delete'],
                'goods-received-notes' => ['view', 'create', 'delete'],
                'stock-transfers'      => ['view', 'create', 'delete', 'approve', 'receive'],
                'stock-movements'      => ['view'],
                'audit-logs'           => ['view'],
                'deletion-logs'        => ['view'],
                'reports'              => ['view'],
            ]))->get()
        );

        // cashier: sales only
        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions(
            Permission::whereIn('name', $this->prefixed([
                'items'        => ['view'],
                'customers'    => ['view'],
                'sales-orders' => ['view', 'create', 'edit', 'delete'],
            ]))->get()
        );

        // auditor: view only everywhere
        $auditor = Role::firstOrCreate(['name' => 'auditor']);
        $auditor->syncPermissions(
            Permission::where('name', 'like', 'view.%')->get()
        );
    }

    private function prefixed(array $modules): array
    {
        $names = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$action}.{$module}";
            }
        }
        return $names;
    }

} database/seeders/DatabaseSeeder.php

    <?php

namespace Database`\Seeders`{=tex};

    use Illuminate\Database\Seeder;

    class DatabaseSeeder extends Seeder
    {

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);
    }

}

5.  Eloquent Models Create one model per table. Key rules: All models
    use SoftDeletes All models use HasFactory User model uses HasRoles
    from Spatie Use \$fillable (not \$guarded) Define all relationships
    Model list and their relationships: Branch → hasMany: departments,
    users (via pivot), items, suppliers, customers, purchaseOrders,
    goodsReceivedNotes, salesOrders, stockTransfers, batchInventory,
    stockMovements Department → belongsTo: branch \| hasMany: users,
    itemStockLevels, batchInventory, stockMovements User → belongsTo:
    branch, department \| belongsToMany: branches (via branch_user) \|
    HasRoles ItemCategory → belongsTo: branch \| hasMany: items
    UnitOfMeasure → belongsTo: branch \| hasMany: items, packagingTypes
    PackagingType → belongsTo: branch, baseUom (UnitOfMeasure) \|
    hasMany: items, grnLineItems, salesOrderLines Item → belongsTo:
    branch, category (ItemCategory), uom (UnitOfMeasure), packagingType
    \| hasMany: itemStockLevels, openingStockLines, grnLineItems,
    salesOrderLines, batchInventory, stockMovements ItemStockLevel →
    belongsTo: branch, department, item Supplier → belongsTo: branch \|
    hasMany: purchaseOrders, goodsReceivedNotes Customer → belongsTo:
    branch \| hasMany: salesOrders OpeningStockEntry → belongsTo:
    branch, department, postedBy (User) \| hasMany: openingStockLines
    OpeningStockLine → belongsTo: openingStockEntry, item \| hasOne:
    batchInventory (polymorphic) BatchInventory → belongsTo: branch,
    department, item \| morphTo: source \| hasMany:
    salesStockAllocations, stockTransferLines, stockMovements
    PurchaseOrder → belongsTo: branch, supplier, createdBy (User),
    approvedBy (User) \| hasMany: purchaseOrderLines, goodsReceivedNotes
    PurchaseOrderLine → belongsTo: purchaseOrder, item GoodsReceivedNote
    → belongsTo: branch, department, supplier, purchaseOrder, receivedBy
    (User) \| hasMany: grnLineItems GrnLineItem → belongsTo: grn
    (GoodsReceivedNote), item, packagingType \| hasOne: batchInventory
    (polymorphic) SalesOrder → belongsTo: branch, department, servedBy
    (User), customer \| hasMany: salesOrderLines SalesOrderLine →
    belongsTo: salesOrder, item, packagingType \| hasMany:
    salesStockAllocations SalesStockAllocation → belongsTo:
    salesOrderLine, batchInventory StockTransfer → belongsTo: branch,
    fromBranch, fromDepartment, toBranch, toDepartment, requestedBy
    (User), approvedBy (User) \| hasMany: stockTransferLines
    StockTransferLine → belongsTo: stockTransfer, item, batchInventory
    StockMovement → belongsTo: branch, department, item, batchInventory,
    recordedBy (User) \| morphTo: reference DeletionLog → belongsTo:
    branch, deletedBy (User) AuditLog → belongsTo: branch, user \|
    morphTo: auditable

6.  Business Logic & Service Classes Create a app/Services/ directory.
    Each service handles one domain. Never put business logic in
    controllers or Filament resources --- use services. InventoryService
    Handles all stock level updates. Called by every other service that
    touches stock. updateStockLevel(branchId, departmentId, itemId,
    qtyChange, unitCost): void

-   Finds or creates item_stock_levels record
-   Increments or decrements qty_on_hand
-   Updates unit_cost if qtyChange is positive (new stock coming in)
    BatchInventoryService The FIFO engine. All other services call this
    when stock needs to be deducted. createBatch(sourceType, sourceId,
    branchId, departmentId, itemId, batchNumber, expiryDate,
    qtyReceived, unitCost, receivedAt): BatchInventory
-   Creates batch_inventory record

allocateStock(salesOrderLine): array of \[batchInventoryId =\>
qtyAllocated\] - Queries batch_inventory WHERE item_id = ? AND branch_id
= ? AND qty_remaining \> 0 - Orders by: expiry_date ASC NULLS LAST,
received_at ASC (FIFO) - Fills allocations until qty_sold is satisfied -
Decrements qty_remaining on each batch touched - Throws
InsufficientStockException if stock runs out - Returns allocation map

reverseAllocations(salesOrderLine): void - Loops through
sales_stock_allocations for the line - Adds back qty_allocated to each
batch_inventory.qty_remaining - Deletes the allocation records
OpeningStockService post(OpeningStockEntry, lines\[\]): void - For each
line: calls
BatchInventoryService::createBatch(source=OpeningStockLine) - Calls
InventoryService::updateStockLevel (qty_in) - Calls
StockMovementService::record(type=opening_stock)

editLine(OpeningStockLine, newQty, newCost): void - Blocks if
is_consumed = true → throw EditBlockedException - Calculates diff
between old and new qty - Updates batch_inventory.qty_remaining by the
diff - Calls InventoryService::updateStockLevel with the diff - Reverses
old stock_movement, posts new one - Sets edited_at = now(), increments
edit_count - Records in audit_log GoodsReceiptService
receive(GoodsReceivedNote, lines\[\]): void - For each GrnLineItem: - If
entry_mode = pack: qty_received = pack_quantity × units_per_pack - Calls
BatchInventoryService::createBatch(source=GrnLineItem) - Calls
InventoryService::updateStockLevel (qty_in) - Calls
StockMovementService::record(type=goods_receipt) - Updates
purchase_order_lines.qty_received if linked to a PO - Updates PO status:
partially_received or fully_received - Recalculates GRN totals

delete(GoodsReceivedNote, reason): void - Creates deletion_log with
snapshot + stock_reversal details - For each GrnLineItem: - Checks if
batch has been sold (batch_inventory.qty_remaining \< qty_received) - If
sold from: blocks deletion, throws BatchConsumedError - If untouched:
reverses InventoryService::updateStockLevel - Calls
StockMovementService::record(type=reversal) - Soft deletes the
batch_inventory record - Soft deletes all GrnLineItems - Soft deletes
the GRN - Reverts PO status if applicable SalesOrderService
create(SalesOrder, lines\[\]): void - For each line: - Calls
BatchInventoryService::allocateStock(line) → allocations - Creates
sales_stock_allocations records - Sets line.unit_cost from weighted
allocation cost - Calculates line_total, line_cost, gross_profit - Sets
is_low_margin if gross_profit \< (min_selling_price × qty_sold) - Sets
is_negative_margin if gross_profit \< 0 - Calls
StockMovementService::record(type=sale) per batch allocation - Calls
InventoryService::updateStockLevel (qty_out) - Recalculates order totals

editLine(SalesOrderLine, newQty, newUnitPrice): void - If only
unit_price changed: update price/profit fields only, no stock impact -
If qty_sold changed: - Calls
BatchInventoryService::reverseAllocations(line) - Posts stock_movements
reversals - Updates qty_sold - Calls
BatchInventoryService::allocateStock(line) fresh - Posts new
stock_movements - Updates line financials - Sets edited_at, increments
edit_count - Recalculates order totals - Records in audit_log

deleteLine(SalesOrderLine): void - Calls
BatchInventoryService::reverseAllocations(line) - Posts stock_movements
reversal - Calls InventoryService::updateStockLevel (reverse qty_out) -
Soft deletes the line - Recalculates order totals

delete(SalesOrder, reason): void - Creates deletion_log with full
snapshot - For each non-deleted line: calls deleteLine(line) - Soft
deletes the SalesOrder StockTransferService dispatch(StockTransfer):
void - Status must be approved - For each line: - Decrements source
batch_inventory.qty_remaining - Calls InventoryService::updateStockLevel
on source (qty_out) - Calls
StockMovementService::record(type=transfer_out) - Increments
qty_reserved on destination item_stock_levels - Sets status =
in_transit, transferred_at = now()

receive(StockTransfer): void - Status must be in_transit - For each
line: - Creates new batch_inventory at destination
(source_type=StockTransferLine, preserves
batch_number/expiry/unit_cost) - Calls
InventoryService::updateStockLevel on destination (qty_in) - Decrements
qty_reserved on destination - Calls
StockMovementService::record(type=transfer_in) - Sets status = received,
received_at = now() StockMovementService record(type, branchId,
departmentId, itemId, batchInventoryId, qtyIn, qtyOut, unitCost,
unitPrice, referenceType, referenceId, batchNumber, expiryDate, notes,
movedAt): StockMovement - Reads current qty_on_hand from
item_stock_levels as qty_before - Calculates qty_after = qty_before +
qty_in - qty_out - Creates stock_movements record - Returns the movement
DeletionLogService record(deletedBy, record, reason, stockReversal):
DeletionLog - Builds snapshot JSON from the record and all its lines
(load fresh with relations) - Saves deletion_log AuditLogService
record(event, auditable, userId, branchId, oldValues, newValues): void -
Creates audit_logs entry - Captures ip_address and user_agent from
request() Number Generators Create
app/Services/NumberGeneratorService.php: generatePoNumber(branchId):
string → PO-{branchCode}-{YYYYMMDD}-{sequence}
generateGrnNumber(branchId): string →
GRN-{branchCode}-{YYYYMMDD}-{sequence} generateOrderNumber(branchId):
string → SO-{branchCode}-{YYYYMMDD}-{sequence}
generateTransferNumber(branchId): string →
TRF-{branchCode}-{YYYYMMDD}-{sequence} generateEntryNumber(branchId):
string → OSE-{branchCode}-{YYYYMMDD}-{sequence} Sequence is zero-padded
to 4 digits, scoped per branch per day.

7.  Filament Panel & Resources Panel Setup Single panel at /admin Login
    page is the entry point After login, redirect to dashboard Dashboard
    shows: today's sales total, low stock alerts (items below
    reorder_level), recent sales orders, pending transfers Navigation
    Groups Settings → Branches, Departments, Users, Roles Catalogue →
    Items, Categories, Units of Measure, Packaging Types Parties →
    Suppliers, Customers Procurement → Purchase Orders Stock In →
    Opening Stock, Goods Received Notes Sales → Sales Orders Stock
    Control → Stock Transfers, Stock Movements (view only) Logs → Audit
    Logs, Deletion Logs Resource Rules (apply to all resources) Gate
    every action with the corresponding Spatie permission using
    AuthorizationException or Filament's -\>authorize() hooks Use
    -\>searchable() on name/number fields Use -\>sortable() on date and
    status fields All list views have filters for date range, status,
    branch All forms use -\>columns(2) layout Monetary fields display as
    number_format(value, 2) with currency label Use Filament's
    RelationManagers for line items (GRN lines, PO lines, Sales lines,
    Transfer lines) Resource-specific behaviour: Items Resource List
    shows: name, category, selling price, qty_on_hand (from
    item_stock_levels), reorder alert badge Form: all item fields +
    packaging toggle Cannot delete if has active batch_inventory records
    Opening Stock Resource Header form: branch, department, notes
    (entry_number auto-generated) Lines repeater: item, batch_number,
    expiry_date, qty_on_hand, unit_cost Save action posts the entry and
    creates batch_inventory records Edit: allowed only if no line has
    is_consumed = true If partial consumption (some lines consumed, some
    not): only unconsumed lines are editable Goods Received Notes
    Resource Header form: supplier, purchase_order (optional, filters by
    supplier), branch, department, supplier_reference_no, received_at,
    notes If PO selected: pre-fill lines from PO lines (item, qty, cost)
    --- user adjusts actuals Lines repeater: item, entry_mode,
    packaging_type (if pack), pack_quantity, units_per_pack,
    qty_received (auto-calc if pack), batch_number, expiry_date,
    unit_cost, line_total (auto-calc) No edit --- only view and delete
    Delete requires reason input → confirmation modal Purchase Orders
    Resource Statuses shown as badges with colours: draft=grey,
    issued=blue, partially_received=yellow, fully_received=green,
    cancelled=red Actions: Approve (draft→issued), Cancel
    (draft/issued→cancelled) Cannot edit once issued Lines show
    qty_ordered vs qty_received progress Sales Orders Resource Header:
    customer (searchable dropdown or type name for walk-in), branch,
    department, sold_at, notes, amount_tendered Lines repeater: item
    (searchable), qty_sold, unit_price (defaults to item.selling_price,
    editable), entry_mode On item select: show current qty_on_hand as
    hint. Block if insufficient stock Live calculation: line_total,
    subtotal, grand_total as user types Show margin warnings inline
    (is_low_margin = yellow, is_negative_margin = red) Edit: allowed.
    Changing qty triggers reallocation (handled by
    SalesOrderService::editLine) Delete: requires reason → creates
    deletion_log → reverses stock Adding/removing lines: allowed on
    existing orders Stock Transfers Resource Form: transfer_type
    (inter_department \| inter_branch), from/to branch+department, notes
    Lines: item, batch (dropdown filtered by item + from branch, shows
    batch_number + expiry + qty_remaining), qty_requested, unit_cost
    (auto-filled from batch) Actions: Submit for Approval, Approve,
    Dispatch (sets in_transit), Receive Receive action: user confirms
    qty_received per line (may differ from qty_transferred) Stock
    Movements Resource Read-only list. No create/edit/delete Columns:
    date, item, batch, type (badge), qty_in, qty_out, before, after,
    reference, recorded by Filters: movement_type, item, date range,
    branch Audit Logs Resource Read-only. Columns: date, user, event
    (badge), record type, record id, branch Expandable row to show
    old_values vs new_values diff Deletion Logs Resource Read-only.
    Columns: date, deleted by, record type, record number, reason
    Expandable row to show snapshot JSON

8.  Authorization Rules Use Filament's resource-level authorization. In
    each Resource class: public static function canViewAny(): bool {
    return auth()-\>user()-\>can('view.{module}'); } public static
    function canCreate(): bool { return
    auth()-\>user()-\>can('create.{module}'); } public static function
    canEdit(Model \$record): bool { return
    auth()-\>user()-\>can('edit.{module}'); } public static function
    canDelete(Model \$record): bool { return
    auth()-\>user()-\>can('delete.{module}'); } For custom actions
    (approve, receive, etc.) use: Action::make('approve') -\>visible(fn
    () =\> auth()-\>user()-\>can('approve.purchase-orders')) Super-admin
    and branch-manager bypass all checks (Spatie handles this via
    syncPermissions(Permission::all())).

9.  Key Validation Rules Items name unique per branch selling_price must
    be \>= min_selling_price Cannot soft-delete if active
    batch_inventory records exist Opening Stock Lines batch_number
    required qty_on_hand must be \> 0 unit_cost must be \> 0 Cannot edit
    line if is_consumed = true GRN Lines batch_number required
    qty_received must be \> 0 unit_cost must be \> 0 If entry_mode =
    pack: pack_quantity and units_per_pack required Cannot delete GRN if
    any batch has been partially consumed Sales Order Lines qty_sold
    must be \> 0 unit_price must be \>= 0 unit_price should warn (not
    block) if below min_selling_price Stock must be sufficient (check
    batch_inventory sum of qty_remaining) Cannot change the item on an
    existing line --- must delete and re-add Stock Transfers
    qty_requested must not exceed batch qty_remaining from and to cannot
    be the same branch+department combination Inter-department:
    from_branch_id must equal to_branch_id

10. FIFO Algorithm (Critical --- implement exactly) When allocating
    stock for a sale line:

```{=html}
<!-- -->
```
    $remaining = $line->qty_sold;
    $allocations = [];

    $batches = BatchInventory::where('branch_id', $line->salesOrder->branch_id)

    ->where('item_id', $line->item_id)
    ->where('qty_remaining', '>', 0)
    ->whereNull('deleted_at')
    ->orderByRaw('expiry_date IS NULL ASC') // non-null expiry first
    ->orderBy('expiry_date', 'asc')
    ->orderBy('received_at', 'asc')
    ->lockForUpdate()                        // prevent race conditions
    ->get();

foreach (\$batches as $batch) {
    if ($remaining \<= 0) break;

    $take = min($remaining, $batch->qty_remaining);
    $batch->decrement('qty_remaining', $take);

    $allocations[] = [
        'batch_inventory_id' => $batch->id,
        'qty_allocated'      => $take,
        'unit_cost'          => $batch->unit_cost,
    ];

    $remaining -= $take;

}

if ($remaining > 0) {
    throw new InsufficientStockException($line-\>item_id,
\$line-\>qty_sold); }

SalesStockAllocation::insert(\$allocations + \['sales_order_line_id' =\>
\$line-\>id\]); Always wrap this in a database transaction.

11. Document Number Format All reference numbers are auto-generated.
    Never allow manual entry. Sequence resets daily per branch. Use a DB
    transaction with SELECT ... FOR UPDATE to avoid duplicates.

12. Soft Delete & Deletion Flow Rule: Every delete in the system follows
    this exact sequence: User clicks delete → modal appears asking for a
    reason (required, min 10 chars) System creates a deletion_log
    record: snapshot: full JSON of the parent record + all child lines
    (loaded fresh) stock_reversal: JSON array of \[batch_inventory_id,
    qty_restored, item_id, item_name\] System reverses all stock
    movements caused by the record (adds back to batch_inventory,
    updates item_stock_levels) System posts stock_movements entries of
    type reversal for each reversal System soft-deletes all child
    records, then the parent AuditLog entry created with event deleted
    Cascade behaviour: Deleting an OpeningStockEntry soft-deletes all
    its lines (if none are consumed) Deleting a GoodsReceivedNote
    soft-deletes all GrnLineItems (if none partially consumed) Deleting
    a SalesOrder soft-deletes all SalesOrderLines and their
    SalesStockAllocations Deleting a StockTransfer only allowed in draft
    or cancelled status

13. Phase 2 Modules (DO NOT BUILD NOW) The following are explicitly out
    of scope for the MVP. The migrations and permissions are already
    seeded to support them later, but no models, services, or Filament
    resources should be built: Inventory Counts (inventory_counts,
    inventory_count_lines) Clearance Manager (clearance_rules,
    clearance_items, clearance_stock, clearance_actions) Clearance Sales
    Disposals (disposals, disposal_lines) Donations (donations,
    donation_lines) Expenses (expenses, expense_categories) Reports
    module

14. Run Commands After all files are in place:

```{=html}
<!-- -->
```
    php artisan migrate
    php artisan db:seed
    php artisan make:filament-user
