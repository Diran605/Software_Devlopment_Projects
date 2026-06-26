<?php

namespace App\Services;

use App\Models\ItemStockLevel;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function updateStockLevel($branchId, $departmentId, $itemId, $qtyChange, $unitCost = null): void
    {
        DB::transaction(function () use ($branchId, $departmentId, $itemId, $qtyChange, $unitCost) {
            $stockLevel = ItemStockLevel::firstOrCreate(
                [
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'item_id' => $itemId
                ],
                ['qty_on_hand' => 0, 'qty_reserved' => 0, 'reorder_level' => 0, 'unit_cost' => 0]
            );

            $stockLevel->qty_on_hand += $qtyChange;
            
            if ($qtyChange > 0 && $unitCost !== null) {
                $stockLevel->unit_cost = $unitCost;
            }

            $stockLevel->save();
        });
    }

    public function getStockLevel(int $branchId, ?int $departmentId, int $itemId): int
    {
        return ItemStockLevel::where('branch_id', $branchId)
            ->where('department_id', $departmentId)
            ->where('item_id', $itemId)
            ->value('qty_on_hand') ?? 0;
    }

    public function updateReservedLevel(int $branchId, ?int $departmentId, int $itemId, int $qtyChange): void
    {
        DB::transaction(function () use ($branchId, $departmentId, $itemId, $qtyChange) {
            $stockLevel = ItemStockLevel::firstOrCreate(
                [
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'item_id' => $itemId
                ],
                ['qty_on_hand' => 0, 'qty_reserved' => 0, 'reorder_level' => 0, 'unit_cost' => 0]
            );

            $stockLevel->qty_reserved += $qtyChange;
            $stockLevel->save();
        });
    }

    public function moveStockBetweenDepartments(int $branchId, int $itemId, ?int $oldDepartmentId, ?int $newDepartmentId, int $qty, float $unitCost): void
    {
        DB::transaction(function () use ($branchId, $itemId, $oldDepartmentId, $newDepartmentId, $qty, $unitCost) {
            // Remove from old department
            $this->updateStockLevel($branchId, $oldDepartmentId, $itemId, -$qty, $unitCost);
            
            // Add to new department
            $this->updateStockLevel($branchId, $newDepartmentId, $itemId, $qty, $unitCost);
        });
    }
}
