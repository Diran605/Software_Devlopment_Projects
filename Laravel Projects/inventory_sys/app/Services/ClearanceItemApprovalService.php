<?php

namespace App\Services;

use App\Models\ClearanceItem;
use App\Models\ClearanceStock;
use Illuminate\Support\Facades\DB;

class ClearanceItemApprovalService
{
    public function approve(ClearanceItem $item, array $data): void
    {
        DB::transaction(function () use ($item, $data) {
            $item->loadMissing(['batchInventory', 'item', 'rule']);

            $batch = $item->batchInventory;
            $qtyToMove = (int) $data['qty_to_move'];

            if ($batch && $batch->qty_remaining < $qtyToMove) {
                throw new \RuntimeException(
                    "Insufficient stock in normal batch: remaining {$batch->qty_remaining}, requested {$qtyToMove}."
                );
            }

            if ($batch) {
                $batch->qty_remaining -= $qtyToMove;
                $batch->save();
            }

            $inventoryService = app(InventoryService::class);
            $inventoryService->updateStockLevel(
                $item->branch_id,
                $item->item->department_id,
                $item->item_id,
                -$qtyToMove
            );

            $stockMovementService = app(StockMovementService::class);
            $stockMovementService->record(
                branchId: $item->branch_id,
                departmentId: $item->item->department_id,
                itemId: $item->item_id,
                batchInventoryId: $item->batch_inventory_id,
                recordedBy: auth()->id(),
                movementType: 'clearance_out',
                qtyIn: 0,
                qtyOut: $qtyToMove,
                qtyBefore: null,
                qtyAfter: null,
                unitCost: $batch?->unit_cost,
                referenceType: ClearanceItem::class,
                referenceId: $item->id,
                batchNumber: $batch?->batch_number,
                expiryDate: $batch?->expiry_date,
                notes: $data['notes'] ?? null
            );

            $originalPrice = (float) $item->original_price;
            $discountPercent = (float) ($data['discount_percent'] ?? $item->rule?->discount ?? 0);
            $clearancePrice = (float) ($data['clearance_price'] ?? ($originalPrice * (1 - $discountPercent / 100)));

            $item->update([
                'approval_status' => 'approved',
                'action_type' => $data['action_type'],
                'qty_to_move' => $qtyToMove,
                'clearance_price' => $clearancePrice,
                'notes' => $data['notes'] ?? $item->notes,
            ]);

            ClearanceStock::create([
                'branch_id' => $item->branch_id,
                'department_id' => $item->item->department_id,
                'clearance_item_id' => $item->id,
                'item_id' => $item->item_id,
                'batch_inventory_id' => $item->batch_inventory_id,
                'batch_number' => $batch?->batch_number,
                'expiry_date' => $batch?->expiry_date,
                'qty_on_clearance' => $qtyToMove,
                'qty_remaining' => $qtyToMove,
                'original_price' => $originalPrice,
                'clearance_price' => $clearancePrice,
                'unit_cost' => $batch?->unit_cost,
            ]);
        });
    }
}
