<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Models\BatchInventory;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        protected BatchInventoryService $batchInventoryService,
        protected InventoryService $inventoryService,
        protected StockMovementService $stockMovementService,
    ) {}

    public function createRequest(StockTransfer $transfer, array $lines): void
    {
        DB::transaction(function () use ($transfer, $lines) {
            foreach ($lines as $lineData) {
                // If a batch is specified, check its qty remaining
                if (!empty($lineData['batch_inventory_id'])) {
                    $batch = BatchInventory::find($lineData['batch_inventory_id']);
                    if (!$batch || $batch->qty_remaining < $lineData['qty_requested']) {
                        throw new InsufficientStockException(
                            $lineData['item_id'],
                            $lineData['qty_requested'],
                            $batch ? $batch->qty_remaining : 0
                        );
                    }
                    $lineData['batch_number'] = $batch->batch_number;
                    $lineData['expiry_date'] = $batch->expiry_date;
                    $lineData['unit_cost'] = $batch->unit_cost;
                } else {
                    $availableQty = $this->inventoryService->getStockLevel(
                        branchId: $transfer->from_branch_id,
                        departmentId: $transfer->from_department_id,
                        itemId: $lineData['item_id']
                    );

                    if ($availableQty < $lineData['qty_requested']) {
                        throw new InsufficientStockException($lineData['item_id'], $lineData['qty_requested'], $availableQty);
                    }
                }

                $line = new StockTransferLine($lineData);
                $transfer->stockTransferLines()->save($line);
            }

            $transfer->status = 'draft';
            $transfer->save();
        });
    }

    public function submit(StockTransfer $transfer): void
    {
        $transfer->status = 'pending_approval';
        $transfer->save();
    }

    public function approve(StockTransfer $transfer): void
    {
        $transfer->status = 'approved';
        $transfer->approved_by = auth()->id();
        $transfer->save();
    }

    public function dispatch(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            foreach ($transfer->stockTransferLines as $line) {
                $qty = $line->qty_requested;
                $line->qty_transferred = $qty;

                // 1. Decrement source BatchInventory.qty_remaining
                if ($line->batch_inventory_id) {
                    $batch = BatchInventory::find($line->batch_inventory_id);
                    if ($batch) {
                        if ($batch->qty_remaining < $qty) {
                            throw new InsufficientStockException($line->item_id, $qty, $batch->qty_remaining);
                        }
                        $batch->qty_remaining -= $qty;
                        $batch->save();
                    }
                }

                // 2. Decrement source stock level
                $this->inventoryService->updateStockLevel(
                    branchId: $transfer->from_branch_id,
                    departmentId: $transfer->from_department_id,
                    itemId: $line->item_id,
                    qtyChange: -$qty
                );

                // 3. Increment destination reserved level
                $this->inventoryService->updateReservedLevel(
                    branchId: $transfer->to_branch_id,
                    departmentId: $transfer->to_department_id,
                    itemId: $line->item_id,
                    qtyChange: $qty
                );

                // 4. Record stock movement at source
                $this->stockMovementService->record(
                    branchId: $transfer->from_branch_id,
                    departmentId: $transfer->from_department_id,
                    itemId: $line->item_id,
                    batchInventoryId: $line->batch_inventory_id,
                    recordedBy: auth()->id() ?? $transfer->requested_by,
                    movementType: 'transfer_out',
                    qtyIn: 0,
                    qtyOut: $qty,
                    qtyBefore: null,
                    qtyAfter: null,
                    unitCost: $line->unit_cost,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                    batchNumber: $line->batch_number,
                    expiryDate: $line->expiry_date,
                    notes: "Stock Transfer Out to Branch {$transfer->to_branch_id}"
                );

                $line->save();
            }

            $transfer->status = 'in_transit';
            $transfer->transferred_at = now();
            $transfer->save();
        });
    }

    public function receive(StockTransfer $transfer, array $lineQuantities): void
    {
        DB::transaction(function () use ($transfer, $lineQuantities) {
            foreach ($transfer->stockTransferLines as $line) {
                // Find received qty matching this line
                $qtyReceived = $lineQuantities[$line->id] ?? $line->qty_transferred;

                $line->qty_received = $qtyReceived;
                $line->received_at = now();
                $line->save();

                // 1. Create new BatchInventory at destination
                $destBatch = $this->batchInventoryService->createBatch(
                    sourceType: StockTransferLine::class,
                    sourceId: $line->id,
                    branchId: $transfer->to_branch_id,
                    departmentId: $transfer->to_department_id,
                    itemId: $line->item_id,
                    batchNumber: $line->batch_number ?? ('TRF-' . $transfer->transfer_number),
                    expiryDate: $line->expiry_date,
                    qtyReceived: $qtyReceived,
                    unitCost: $line->unit_cost ?? 0
                );

                // 2. Increment destination stock level
                $this->inventoryService->updateStockLevel(
                    branchId: $transfer->to_branch_id,
                    departmentId: $transfer->to_department_id,
                    itemId: $line->item_id,
                    qtyChange: $qtyReceived,
                    unitCost: $line->unit_cost
                );

                // 3. Decrement destination reserved stock level
                $this->inventoryService->updateReservedLevel(
                    branchId: $transfer->to_branch_id,
                    departmentId: $transfer->to_department_id,
                    itemId: $line->item_id,
                    qtyChange: -$line->qty_transferred
                );

                // 4. Record stock movement at destination
                $this->stockMovementService->record(
                    branchId: $transfer->to_branch_id,
                    departmentId: $transfer->to_department_id,
                    itemId: $line->item_id,
                    batchInventoryId: $destBatch->id,
                    recordedBy: auth()->id() ?? $transfer->requested_by,
                    movementType: 'transfer_in',
                    qtyIn: $qtyReceived,
                    qtyOut: 0,
                    qtyBefore: null,
                    qtyAfter: null,
                    unitCost: $line->unit_cost,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                    batchNumber: $line->batch_number,
                    expiryDate: $line->expiry_date,
                    notes: "Stock Transfer In from Branch {$transfer->from_branch_id}"
                );
            }

            $transfer->status = 'received';
            $transfer->received_at = now();
            $transfer->save();
        });
    }

    public function cancel(StockTransfer $transfer): void
    {
        $transfer->status = 'cancelled';
        $transfer->save();
    }
}
