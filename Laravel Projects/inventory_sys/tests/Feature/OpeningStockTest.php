<?php

use App\Models\Branch;
use App\Models\Item;
use App\Models\OpeningStockEntry;
use App\Models\OpeningStockLine;
use App\Models\User;
use App\Models\ItemStockLevel;
use App\Models\StockMovement;
use App\Models\BatchInventory;
use App\Services\OpeningStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('posting opening stock updates stock levels and records stock movement', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $branch = Branch::create([
        'name' => 'Test Branch',
        'code' => 'TB001',
        'is_active' => true,
    ]);

    $category = \App\Models\ItemCategory::create([
        'branch_id' => $branch->id,
        'name' => 'Test Category',
    ]);

    $uom = \App\Models\UnitOfMeasure::create([
        'branch_id' => $branch->id,
        'name' => 'Piece',
        'abbreviation' => 'pc',
    ]);

    $item = Item::create([
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'uom_id' => $uom->id,
        'name' => 'Test Item',
        'unit_cost' => 100,
        'selling_price' => 150,
        'is_active' => true,
    ]);

    $entry = OpeningStockEntry::create([
        'branch_id' => $branch->id,
        'posted_by' => $user->id,
        'entry_number' => 'OS-0001',
        'posted_at' => now(),
    ]);

    $lineData = [
        [
            'item_id' => $item->id,
            'batch_number' => 'BCH-001',
            'expiry_date' => now()->addYear(),
            'qty_on_hand' => 50,
            'unit_cost' => 100,
        ]
    ];

    app(OpeningStockService::class)->post($entry, $lineData);

    // Assert Opening Stock Line was created
    $this->assertDatabaseHas('opening_stock_lines', [
        'opening_stock_entry_id' => $entry->id,
        'item_id' => $item->id,
        'batch_number' => 'BCH-001',
        'qty_on_hand' => 50,
        'unit_cost' => 100,
    ]);

    // Assert Batch Inventory was created
    $this->assertDatabaseHas('batch_inventories', [
        'branch_id' => $branch->id,
        'item_id' => $item->id,
        'batch_number' => 'BCH-001',
        'qty_received' => 50,
        'qty_remaining' => 50,
        'unit_cost' => 100,
    ]);

    // Assert Item Stock Level was updated
    $this->assertDatabaseHas('item_stock_levels', [
        'branch_id' => $branch->id,
        'item_id' => $item->id,
        'qty_on_hand' => 50,
        'unit_cost' => 100,
    ]);

    // Assert Stock Movement was recorded
    $this->assertDatabaseHas('stock_movements', [
        'branch_id' => $branch->id,
        'item_id' => $item->id,
        'movement_type' => 'opening_stock',
        'qty_in' => 50,
        'qty_out' => 0,
        'qty_before' => 0,
        'qty_after' => 50,
        'unit_cost' => 100,
    ]);
});
