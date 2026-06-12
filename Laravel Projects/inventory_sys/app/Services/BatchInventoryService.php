<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\BatchInventory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BatchInventoryService
{
    public function createBatch($sourceType, $sourceId, $branchId, $departmentId, $itemId, $batchNumber, $expiryDate, $qtyReceived, $unitCost, $receivedAt = null): BatchInventory
    {
        return BatchInventory::create([
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'item_id' => $itemId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'qty_received' => $qtyReceived,
            'qty_remaining' => $qtyReceived,
            'unit_cost' => $unitCost,
            'received_at' => $receivedAt ?? Carbon::now()
        ]);
    }

    public function allocateStock($salesOrderLine): array
    {
        return DB::transaction(function () use ($salesOrderLine) {
            $allocations = [];
            $qtyToAllocate = $salesOrderLine->qty_sold;

            $query = BatchInventory::where('item_id', $salesOrderLine->item_id)
                ->where('branch_id', $salesOrderLine->salesOrder->branch_id)
                ->where('qty_remaining', '>', 0);

            if ($salesOrderLine->batch_inventory_id) {
                $query->where('id', $salesOrderLine->batch_inventory_id);
            } else {
                $query->orderByRaw('expiry_date IS NULL ASC')
                    ->orderBy('expiry_date', 'asc')
                    ->orderBy('received_at', 'asc');
            }

            $batches = $query->lockForUpdate()->get();

            $availableQty = $batches->sum('qty_remaining');

            foreach ($batches as $batch) {
                if ($qtyToAllocate <= 0) break;

                $qtyFromBatch = min($batch->qty_remaining, $qtyToAllocate);
                $allocations[] = [
                    'batch_inventory_id' => $batch->id,
                    'qty_allocated' => $qtyFromBatch,
                    'unit_cost' => $batch->unit_cost
                ];

                $batch->qty_remaining -= $qtyFromBatch;
                $batch->save();
                $qtyToAllocate -= $qtyFromBatch;
            }

            if ($qtyToAllocate > 0) {
                throw new InsufficientStockException($salesOrderLine->item_id, $salesOrderLine->qty_sold, $availableQty);
            }

            return $allocations;
        });
    }

    public function reverseAllocations($salesOrderLine): void
    {
        foreach ($salesOrderLine->salesStockAllocations as $allocation) {
            $batch = BatchInventory::find($allocation->batch_inventory_id);
            $batch->qty_remaining += $allocation->qty_allocated;
            $batch->save();
            $allocation->delete();
        }
    }
}
