<?php

namespace App\Services;

use App\Models\ClearanceAction;
use App\Models\ClearanceItem;
use App\Models\ClearanceStock;
use Illuminate\Support\Facades\DB;

class ClearanceStockReversalService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected StockMovementService $stockMovementService,
    ) {}

    public function reverse(ClearanceStock $stock, string $reason = 'Clearance stock reversed to normal inventory'): void
    {
        DB::transaction(function () use ($stock, $reason) {
            $stock = ClearanceStock::query()->whereKey($stock->getKey())->lockForUpdate()->firstOrFail();

            $qtyToRestore = (int) $stock->qty_remaining;

            if ($qtyToRestore < 1) {
                throw new \RuntimeException('This clearance stock has no remaining quantity to reverse.');
            }

            $batch = $stock->batchInventory;

            if ($batch) {
                $batch->qty_remaining += $qtyToRestore;
                $batch->save();
            }

            $this->inventoryService->updateStockLevel(
                branchId: $stock->branch_id,
                departmentId: $stock->department_id,
                itemId: $stock->item_id,
                qtyChange: $qtyToRestore,
                unitCost: $stock->unit_cost,
            );

            $this->stockMovementService->record(
                branchId: $stock->branch_id,
                departmentId: $stock->department_id,
                itemId: $stock->item_id,
                batchInventoryId: $stock->batch_inventory_id,
                recordedBy: auth()->id(),
                movementType: 'clearance_reversal',
                qtyIn: $qtyToRestore,
                qtyOut: 0,
                qtyBefore: null,
                qtyAfter: null,
                unitCost: $stock->unit_cost,
                referenceType: ClearanceStock::class,
                referenceId: $stock->id,
                batchNumber: $stock->batch_number,
                expiryDate: $stock->expiry_date,
                notes: $reason,
            );

            ClearanceAction::create([
                'branch_id' => $stock->branch_id,
                'clearance_stocks_id' => $stock->id,
                'item_id' => $stock->item_id,
                'batch_inventory_id' => $stock->batch_inventory_id,
                'action_type' => 'reverse',
                'qty' => $qtyToRestore,
                'loss_value' => 0,
                'notes' => $reason,
            ]);

            if ($stock->clearanceItem) {
                $hasOtherActiveStock = ClearanceStock::query()
                    ->where('clearance_item_id', $stock->clearance_item_id)
                    ->whereKeyNot($stock->id)
                    ->where('qty_remaining', '>', 0)
                    ->exists();

                if (! $hasOtherActiveStock) {
                    $stock->clearanceItem->update(['approval_status' => 'approved']);
                }
            }

            $stock->delete();
        });
    }
}
