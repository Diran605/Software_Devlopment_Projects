<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\ClearanceAction;
use App\Models\ClearanceStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        protected BatchInventoryService $batchInventoryService,
        protected InventoryService $inventoryService,
        protected StockMovementService $stockMovementService,
    ) {}

    public function create(SalesOrder $order, array $lines): void
    {
        DB::transaction(function () use ($order, $lines) {
            $subtotal = 0;
            $discountTotal = 0;
            $cogsTotal = 0;
            $hasClearance = false;

            foreach ($lines as $lineData) {
                if (! empty($lineData['clearance_stock_id'])) {
                    $hasClearance = true;
                    $line = $this->createClearanceLine($order, $lineData);
                } else {
                    $line = $this->createRegularLine($order, $lineData);
                }

                $subtotal += $line->line_total;
                $cogsTotal += $line->line_cost;
            }

            $order->subtotal = $subtotal;
            $order->discount_total = $discountTotal;
            $order->grand_total = $subtotal - $discountTotal;
            $order->cogs_total = $cogsTotal;
            $order->gross_profit = $order->grand_total - $cogsTotal;
            $order->is_clearance = $hasClearance;
            $order->save();
        });
    }

    protected function createRegularLine(SalesOrder $order, array $lineData): SalesOrderLine
    {
        $qtySold = $lineData['qty_sold'];
        $unitPrice = $lineData['unit_price'];

        $line = new SalesOrderLine($lineData);
        $line->line_total = $qtySold * $unitPrice;
        $order->salesOrderLines()->save($line);

        $allocations = $this->batchInventoryService->allocateStock($line);

        $lineCost = 0;
        foreach ($allocations as $alloc) {
            $lineCost += $alloc['qty_allocated'] * $alloc['unit_cost'];
            $line->salesStockAllocations()->create([
                'batch_inventory_id' => $alloc['batch_inventory_id'],
                'qty_allocated' => $alloc['qty_allocated'],
                'unit_cost' => $alloc['unit_cost'],
            ]);
        }

        $line->unit_cost = $allocations[0]['unit_cost'] ?? 0;
        $line->line_cost = $lineCost;
        $line->gross_profit = $line->line_total - $lineCost;
        $line->is_low_margin = $line->gross_profit < ($line->line_total * 0.2);
        $line->is_negative_margin = $line->gross_profit < 0;
        $line->save();

        $this->inventoryService->updateStockLevel(
            branchId: $order->branch_id,
            departmentId: $order->department_id,
            itemId: $line->item_id,
            qtyChange: -$qtySold,
        );

        $this->stockMovementService->record(
            branchId: $order->branch_id,
            departmentId: $order->department_id,
            itemId: $line->item_id,
            batchInventoryId: $allocations[0]['batch_inventory_id'],
            recordedBy: $order->served_by,
            movementType: 'sale',
            qtyIn: 0,
            qtyOut: $qtySold,
            qtyBefore: null,
            qtyAfter: null,
            unitCost: $line->unit_cost,
            unitPrice: $unitPrice,
            referenceType: SalesOrder::class,
            referenceId: $order->id,
        );

        return $line;
    }

    protected function createClearanceLine(SalesOrder $order, array $lineData): SalesOrderLine
    {
        $clearanceStock = ClearanceStock::query()
            ->whereKey($lineData['clearance_stock_id'])
            ->lockForUpdate()
            ->firstOrFail();

        $qtySold = (int) $lineData['qty_sold'];
        $unitPrice = (float) $lineData['unit_price'];

        if ($clearanceStock->qty_remaining < $qtySold) {
            throw new InsufficientStockException(
                $clearanceStock->item_id,
                $qtySold,
                $clearanceStock->qty_remaining
            );
        }

        $lineData['item_id'] = $clearanceStock->item_id;
        $lineData['batch_inventory_id'] = $clearanceStock->batch_inventory_id;

        $line = new SalesOrderLine($lineData);
        $line->line_total = $qtySold * $unitPrice;
        $line->unit_cost = $clearanceStock->unit_cost;
        $line->line_cost = $qtySold * $clearanceStock->unit_cost;
        $line->gross_profit = $line->line_total - $line->line_cost;
        $line->is_low_margin = $line->gross_profit < ($line->line_total * 0.2);
        $line->is_negative_margin = $line->gross_profit < 0;
        $order->salesOrderLines()->save($line);

        $lossValue = ($clearanceStock->original_price - $clearanceStock->clearance_price) * $qtySold;

        ClearanceAction::create([
            'branch_id' => $order->branch_id,
            'clearance_stocks_id' => $clearanceStock->id,
            'item_id' => $clearanceStock->item_id,
            'batch_inventory_id' => $clearanceStock->batch_inventory_id,
            'action_type' => 'sell',
            'qty' => $qtySold,
            'loss_value' => $lossValue,
            'sales_order_id' => $order->id,
        ]);

        $clearanceStock->decrement('qty_remaining', $qtySold);

        if ($clearanceStock->fresh()->qty_remaining === 0) {
            $clearanceStock->clearanceItem?->update(['approval_status' => 'actioned']);
        }

        $this->stockMovementService->record(
            branchId: $order->branch_id,
            departmentId: $order->department_id,
            itemId: $line->item_id,
            batchInventoryId: $clearanceStock->batch_inventory_id,
            recordedBy: $order->served_by,
            movementType: 'clearance_sale',
            qtyIn: 0,
            qtyOut: $qtySold,
            qtyBefore: null,
            qtyAfter: null,
            unitCost: $line->unit_cost,
            unitPrice: $unitPrice,
            referenceType: SalesOrder::class,
            referenceId: $order->id,
            batchNumber: $clearanceStock->batch_number,
            expiryDate: $clearanceStock->expiry_date,
            notes: 'Clearance sale via sales order',
        );

        return $line;
    }

    public function editLine(SalesOrderLine $line, int $newQty, float $newUnitPrice): void
    {
        DB::transaction(function () use ($line, $newQty, $newUnitPrice) {
            $order = $line->salesOrder;
            $oldQty = $line->qty_sold;

            // If only price changed, no stock allocations are affected
            if ($oldQty === $newQty) {
                $line->unit_price = $newUnitPrice;
                $line->line_total = $newQty * $newUnitPrice;
                $line->gross_profit = $line->line_total - $line->line_cost;
                $line->is_low_margin = $line->gross_profit < ($line->line_total * 0.2);
                $line->is_negative_margin = $line->gross_profit < 0;
                $line->save();

                $this->recalculateOrderTotals($order);
                return;
            }

            // If qty changed:
            // 1. Reverse current allocations
            $this->batchInventoryService->reverseAllocations($line);

            // 2. Put old stock back to inventory levels
            $this->inventoryService->updateStockLevel(
                branchId: $order->branch_id,
                departmentId: $order->department_id,
                itemId: $line->item_id,
                qtyChange: $oldQty
            );

            // 3. Post reversal stock movement
            $this->stockMovementService->record(
                branchId: $order->branch_id,
                departmentId: $order->department_id,
                itemId: $line->item_id,
                batchInventoryId: null,
                recordedBy: auth()->id() ?? $order->served_by,
                movementType: 'reversal',
                qtyIn: $oldQty,
                qtyOut: 0,
                qtyBefore: null,
                qtyAfter: null,
                unitCost: $line->unit_cost,
                referenceType: SalesOrder::class,
                referenceId: $order->id,
                notes: "Line edit reversal"
            );

            // 4. Update the line sold qty & price
            $line->qty_sold = $newQty;
            $line->unit_price = $newUnitPrice;
            $line->line_total = $newQty * $newUnitPrice;
            $line->save();

            // 5. Run allocateStock
            $allocations = $this->batchInventoryService->allocateStock($line);

            // 6. Create the allocations records
            $lineCost = 0;
            foreach ($allocations as $alloc) {
                $lineCost += $alloc['qty_allocated'] * $alloc['unit_cost'];
                $line->salesStockAllocations()->create([
                    'batch_inventory_id' => $alloc['batch_inventory_id'],
                    'qty_allocated' => $alloc['qty_allocated'],
                    'unit_cost' => $alloc['unit_cost'],
                ]);
            }

            $line->unit_cost = $allocations[0]['unit_cost'] ?? 0;
            $line->line_cost = $lineCost;
            $line->gross_profit = $line->line_total - $lineCost;
            $line->is_low_margin = $line->gross_profit < ($line->line_total * 0.2);
            $line->is_negative_margin = $line->gross_profit < 0;
            $line->save();

            // 7. Deduct new stock from inventory levels
            $this->inventoryService->updateStockLevel(
                branchId: $order->branch_id,
                departmentId: $order->department_id,
                itemId: $line->item_id,
                qtyChange: -$newQty
            );

            // 8. Record new movement
            $this->stockMovementService->record(
                branchId: $order->branch_id,
                departmentId: $order->department_id,
                itemId: $line->item_id,
                batchInventoryId: $allocations[0]['batch_inventory_id'],
                recordedBy: auth()->id() ?? $order->served_by,
                movementType: 'sale',
                qtyIn: 0,
                qtyOut: $newQty,
                qtyBefore: null,
                qtyAfter: null,
                unitCost: $line->unit_cost,
                unitPrice: $newUnitPrice,
                referenceType: SalesOrder::class,
                referenceId: $order->id
            );

            // 9. Recalculate order totals
            $this->recalculateOrderTotals($order);
        });
    }

    public function deleteLine(SalesOrderLine $line): void
    {
        DB::transaction(function () use ($line) {
            $order = $line->salesOrder;
            $qty = $line->qty_sold;

            // 1. Reverse allocations
            $this->batchInventoryService->reverseAllocations($line);

            // 2. Put stock back to levels
            $this->inventoryService->updateStockLevel(
                branchId: $order->branch_id,
                departmentId: $order->department_id,
                itemId: $line->item_id,
                qtyChange: $qty
            );

            // 3. Record reversal movement
            $this->stockMovementService->record(
                branchId: $order->branch_id,
                departmentId: $order->department_id,
                itemId: $line->item_id,
                batchInventoryId: null,
                recordedBy: auth()->id() ?? $order->served_by,
                movementType: 'reversal',
                qtyIn: $qty,
                qtyOut: 0,
                qtyBefore: null,
                qtyAfter: null,
                unitCost: $line->unit_cost,
                referenceType: SalesOrder::class,
                referenceId: $order->id,
                notes: "Line deletion reversal"
            );

            // 4. Soft-delete line
            $line->delete();

            // 5. Recalculate order totals
            $this->recalculateOrderTotals($order);
        });
    }

    public function delete(SalesOrder $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            $stockReversal = [];

            // 1. Loop through and delete all lines
            foreach ($order->salesOrderLines as $line) {
                // Keep track for DeletionLog
                $stockReversal[] = [
                    'item_id' => $line->item_id,
                    'qty' => $line->qty_sold,
                    'unit_price' => $line->unit_price,
                ];

                // Reverse allocations and put stock back
                $this->batchInventoryService->reverseAllocations($line);

                $this->inventoryService->updateStockLevel(
                    branchId: $order->branch_id,
                    departmentId: $order->department_id,
                    itemId: $line->item_id,
                    qtyChange: $line->qty_sold
                );

                // Record movement reversal
                $this->stockMovementService->record(
                    branchId: $order->branch_id,
                    departmentId: $order->department_id,
                    itemId: $line->item_id,
                    batchInventoryId: null,
                    recordedBy: auth()->id() ?? $order->served_by,
                    movementType: 'reversal',
                    qtyIn: $line->qty_sold,
                    qtyOut: 0,
                    qtyBefore: null,
                    qtyAfter: null,
                    unitCost: $line->unit_cost,
                    referenceType: SalesOrder::class,
                    referenceId: $order->id,
                    notes: "Sales Order deletion reversal"
                );

                $line->delete();
            }

            // 2. Record deletion log
            $deletionLogService = app(\App\Services\DeletionLogService::class);
            $deletionLogService->record(
                deletedBy: auth()->id() ?? $order->served_by,
                record: $order,
                reason: $reason,
                stockReversal: $stockReversal,
                recordNumber: $order->order_number
            );

            // 3. Soft-delete sales order
            $order->delete();
        });
    }

    protected function recalculateOrderTotals(SalesOrder $order): void
    {
        $subtotal = $order->salesOrderLines()->sum('line_total');
        $cogsTotal = $order->salesOrderLines()->sum('line_cost');

        $order->subtotal = $subtotal;
        $order->grand_total = $subtotal - $order->discount_total;
        $order->cogs_total = $cogsTotal;
        $order->gross_profit = $order->grand_total - $cogsTotal;
        $order->save();
    }
}
