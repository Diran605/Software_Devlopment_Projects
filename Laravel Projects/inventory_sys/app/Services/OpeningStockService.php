<?php

namespace App\Services;

use App\Exceptions\EditBlockedException;
use App\Models\OpeningStockEntry;
use App\Models\OpeningStockLine;
use Illuminate\Support\Facades\DB;

class OpeningStockService
{
    public function __construct(
        protected BatchInventoryService $batchInventoryService,
        protected InventoryService $inventoryService,
        protected StockMovementService $stockMovementService,
    ) {}

    public function post(OpeningStockEntry $entry, array $lines): void
    {
        DB::transaction(function () use ($entry, $lines) {
            foreach ($lines as $lineData) {
                $line = new OpeningStockLine($lineData);
                $entry->openingStockLines()->save($line);

                $batch = $this->batchInventoryService->createBatch(
                    sourceType: OpeningStockLine::class,
                    sourceId: $line->id,
                    branchId: $entry->branch_id,
                    departmentId: $entry->department_id,
                    itemId: $line->item_id,
                    batchNumber: $line->batch_number,
                    expiryDate: $line->expiry_date,
                    qtyReceived: $line->qty_on_hand,
                    unitCost: $line->unit_cost,
                );

                $this->inventoryService->updateStockLevel(
                    branchId: $entry->branch_id,
                    departmentId: $entry->department_id,
                    itemId: $line->item_id,
                    qtyChange: $line->qty_on_hand,
                    unitCost: $line->unit_cost,
                );

                $this->stockMovementService->record(
                    branchId: $entry->branch_id,
                    departmentId: $entry->department_id,
                    itemId: $line->item_id,
                    batchInventoryId: $batch->id,
                    recordedBy: $entry->posted_by,
                    movementType: 'opening_stock',
                    qtyIn: $line->qty_on_hand,
                    qtyOut: 0,
                    qtyBefore: 0,
                    qtyAfter: $line->qty_on_hand,
                    unitCost: $line->unit_cost,
                    referenceType: OpeningStockEntry::class,
                    referenceId: $entry->id,
                    batchNumber: $line->batch_number,
                    expiryDate: $line->expiry_date,
                );
            }
        });
    }

    public function editLine(OpeningStockLine $line, int $newQty, float $newCost): void
    {
        if ($line->is_consumed) {
            throw new EditBlockedException('Cannot edit already consumed opening stock line');
        }

        DB::transaction(function () use ($line, $newQty, $newCost) {
            $qtyDiff = $newQty - $line->qty_on_hand;

            $batch = $line->batchInventory;
            $batch->qty_received += $qtyDiff;
            $batch->qty_remaining += $qtyDiff;
            $batch->unit_cost = $newCost;
            $batch->save();

            $this->inventoryService->updateStockLevel(
                branchId: $line->openingStockEntry->branch_id,
                departmentId: $line->openingStockEntry->department_id,
                itemId: $line->item_id,
                qtyChange: $qtyDiff,
                unitCost: $newCost,
            );

            $line->qty_on_hand = $newQty;
            $line->unit_cost = $newCost;
            $line->edited_at = now();
            $line->edit_count++;
            $line->save();
        });
    }
}
