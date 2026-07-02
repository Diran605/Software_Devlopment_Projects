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
                if (($lineData['entry_mode'] ?? 'unit') === 'pack') {
                    $packQty = (float) ($lineData['pack_quantity'] ?? 0);
                    $unitsPerPack = (float) ($lineData['units_per_pack'] ?? 1);
                    $lineData['qty_on_hand'] = (int) ($packQty * $unitsPerPack);
                }

                unset($lineData['entry_mode'], $lineData['pack_quantity'], $lineData['units_per_pack'], $lineData['packaging_type_id']);

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
            $batch = $line->batchInventory;

            // How many units have actually been sold/consumed from this batch already
            $soldQty = $batch->qty_received - $batch->qty_remaining;

            // The new qty_remaining cannot go below 0 or below units already sold
            $safeNewQty = max($newQty, $soldQty);
            $newQtyRemaining = $safeNewQty - $soldQty;

            // How much the qty_on_hand (received) is actually changing
            $qtyDiff = $safeNewQty - $line->qty_on_hand;

            // Update batch: received reflects the new total, remaining reflects what's left after sales
            $batch->qty_received = $safeNewQty;
            $batch->qty_remaining = $newQtyRemaining;
            $batch->unit_cost = $newCost;
            $batch->save();

            // Only adjust inventory level by the real change in remaining stock
            $remainingDiff = $newQtyRemaining - ($line->qty_on_hand - $soldQty);
            if ($remainingDiff !== 0) {
                $this->inventoryService->updateStockLevel(
                    branchId: $line->openingStockEntry->branch_id,
                    departmentId: $line->openingStockEntry->department_id,
                    itemId: $line->item_id,
                    qtyChange: $remainingDiff,
                    unitCost: $newCost,
                );
            }

            $line->qty_on_hand = $safeNewQty;
            $line->unit_cost = $newCost;
            $line->edited_at = now();
            $line->edit_count++;
            $line->save();
        });
    }
}
