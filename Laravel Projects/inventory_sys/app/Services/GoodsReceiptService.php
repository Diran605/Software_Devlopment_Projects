<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\GrnLineItem;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function __construct(
        protected BatchInventoryService $batchInventoryService,
        protected InventoryService $inventoryService,
        protected StockMovementService $stockMovementService,
    ) {}

    public function receive(GoodsReceivedNote $grn, array $lines): void
    {
        DB::transaction(function () use ($grn, $lines) {
            $totalQty = 0;
            $totalCost = 0;

            foreach ($lines as $lineData) {
                $qtyReceived = $lineData['qty_received'];
                $unitCost = $lineData['unit_cost'];

                if (isset($lineData['entry_mode']) && $lineData['entry_mode'] === 'pack') {
                    $qtyReceived = $lineData['pack_quantity'] * $lineData['units_per_pack'];
                }

                $line = new GrnLineItem($lineData);
                $line->qty_received = $qtyReceived;
                $line->line_total = $qtyReceived * $unitCost;
                $grn->grnLineItems()->save($line);

                $batch = $this->batchInventoryService->createBatch(
                    sourceType: GrnLineItem::class,
                    sourceId: $line->id,
                    branchId: $grn->branch_id,
                    departmentId: $grn->department_id,
                    itemId: $line->item_id,
                    batchNumber: $line->batch_number,
                    expiryDate: $line->expiry_date,
                    qtyReceived: $qtyReceived,
                    unitCost: $unitCost,
                );

                $this->inventoryService->updateStockLevel(
                    branchId: $grn->branch_id,
                    departmentId: $grn->department_id,
                    itemId: $line->item_id,
                    qtyChange: $qtyReceived,
                    unitCost: $unitCost,
                );

                $this->stockMovementService->record(
                    branchId: $grn->branch_id,
                    departmentId: $grn->department_id,
                    itemId: $line->item_id,
                    batchInventoryId: $batch->id,
                    recordedBy: $grn->received_by,
                    movementType: 'goods_receipt',
                    qtyIn: $qtyReceived,
                    qtyOut: 0,
                    qtyBefore: 0,
                    qtyAfter: $qtyReceived,
                    unitCost: $unitCost,
                    referenceType: GoodsReceivedNote::class,
                    referenceId: $grn->id,
                    batchNumber: $line->batch_number,
                    expiryDate: $line->expiry_date,
                );

                $totalQty += $qtyReceived;
                $totalCost += $line->line_total;

                if ($grn->purchase_order_id) {
                    $poLine = $grn->purchaseOrder->purchaseOrderLines()->where('item_id', $line->item_id)->first();
                    if ($poLine) {
                        $poLine->qty_received += $qtyReceived;
                        $poLine->save();
                    }
                }
            }

            $grn->total_qty = $totalQty;
            $grn->total_cost = $totalCost;
            $grn->save();

            if ($grn->purchase_order_id) {
                $this->updatePurchaseOrderStatus($grn->purchaseOrder);
            }
        });
    }

    protected function updatePurchaseOrderStatus(PurchaseOrder $po): void
    {
        $totalOrdered = $po->purchaseOrderLines->sum('qty_ordered');
        $totalReceived = $po->purchaseOrderLines->sum('qty_received');

        if ($totalReceived == 0) {
            $status = $po->status;
        } elseif ($totalReceived < $totalOrdered) {
            $status = 'partially_received';
        } else {
            $status = 'fully_received';
        }

        $po->status = $status;
        $po->save();
    }

    public function delete(GoodsReceivedNote $grn, string $reason): void
    {
        DB::transaction(function () use ($grn, $reason) {
            $stockReversal = [];

            // Verify that the batch has not been consumed yet
            foreach ($grn->grnLineItems as $line) {
                $batch = $line->batchInventory;
                if ($batch) {
                    if ($batch->qty_remaining < $line->qty_received) {
                        throw new \App\Exceptions\BatchConsumedException($batch->batch_number);
                    }
                }
            }

            // Perform reversals
            foreach ($grn->grnLineItems as $line) {
                $batch = $line->batchInventory;

                // 1. Reverse stock level updates
                $this->inventoryService->updateStockLevel(
                    branchId: $grn->branch_id,
                    departmentId: $grn->department_id,
                    itemId: $line->item_id,
                    qtyChange: -$line->qty_received,
                );

                // 2. Post reversal stock movements
                $this->stockMovementService->record(
                    branchId: $grn->branch_id,
                    departmentId: $grn->department_id,
                    itemId: $line->item_id,
                    batchInventoryId: $batch ? $batch->id : null,
                    recordedBy: auth()->id() ?? $grn->received_by,
                    movementType: 'reversal',
                    qtyIn: 0,
                    qtyOut: $line->qty_received,
                    qtyBefore: null,
                    qtyAfter: null,
                    unitCost: $line->unit_cost,
                    referenceType: GoodsReceivedNote::class,
                    referenceId: $grn->id,
                    batchNumber: $line->batch_number,
                    expiryDate: $line->expiry_date,
                    notes: "GRN Deletion Reversal: {$reason}"
                );

                // Keep track for DeletionLog
                $stockReversal[] = [
                    'item_id' => $line->item_id,
                    'qty' => $line->qty_received,
                    'batch_number' => $line->batch_number,
                ];

                // 3. Soft-delete batch
                if ($batch) {
                    $batch->delete();
                }

                // 4. Update PO line status if PO existed
                if ($grn->purchase_order_id) {
                    $poLine = $grn->purchaseOrder->purchaseOrderLines()->where('item_id', $line->item_id)->first();
                    if ($poLine) {
                        $poLine->qty_received = max(0, $poLine->qty_received - $line->qty_received);
                        $poLine->save();
                    }
                }

                // 5. Soft-delete line item
                $line->delete();
            }

            // Revert PO status if applicable
            if ($grn->purchase_order_id) {
                $this->updatePurchaseOrderStatus($grn->purchaseOrder);
            }

            // 6. Record DeletionLog via DeletionLogService
            $deletionLogService = app(\App\Services\DeletionLogService::class);
            $deletionLogService->record(
                deletedBy: auth()->id() ?? $grn->received_by,
                record: $grn,
                reason: $reason,
                stockReversal: $stockReversal,
                recordNumber: $grn->grn_number
            );

            // 7. Soft-delete GRN
            $grn->delete();
        });
    }
}
