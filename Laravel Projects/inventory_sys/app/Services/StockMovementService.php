<?php

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Support\Carbon;

class StockMovementService
{
    public function record($branchId, $departmentId, $itemId, $batchInventoryId, $recordedBy, $movementType, $qtyIn, $qtyOut, $qtyBefore = null, $qtyAfter = null, $unitCost = null, $unitPrice = null, $referenceType = null, $referenceId = null, $batchNumber = null, $expiryDate = null, $notes = null): StockMovement
    {
        if ($qtyAfter === null) {
            $qtyAfter = \App\Models\ItemStockLevel::where('branch_id', $branchId)
                ->where('department_id', $departmentId)
                ->where('item_id', $itemId)
                ->value('qty_on_hand') ?? 0;
        }

        if ($qtyBefore === null) {
            $qtyBefore = $qtyAfter - $qtyIn + $qtyOut;
        }

        return StockMovement::create([
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'item_id' => $itemId,
            'batch_inventory_id' => $batchInventoryId,
            'recorded_by' => $recordedBy,
            'movement_type' => $movementType,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'qty_before' => $qtyBefore,
            'qty_after' => $qtyAfter,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'notes' => $notes,
            'moved_at' => Carbon::now()
        ]);
    }
}
