<?php

namespace App\Filament\App\Resources\InventoryCounts\Pages;

use App\Filament\App\Resources\InventoryCounts\InventoryCountResource;
use App\Models\BatchInventory;
use App\Models\Item;
use App\Models\ItemStockLevel;
use App\Services\NumberGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateInventoryCount extends CreateRecord
{
    protected static string $resource = InventoryCountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['count_number'] = app(NumberGeneratorService::class)->generateCountNumber($data['branch_id']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $branchId = $this->record->branch_id;
        $departmentId = $this->record->department_id;

        DB::transaction(function () use ($branchId, $departmentId) {
            // Get all items with stock levels in this branch/department
            $stockLevels = ItemStockLevel::where('branch_id', $branchId)
                ->where('qty_on_hand', '>', 0);

            if ($departmentId) {
                $stockLevels = $stockLevels->where('department_id', $departmentId);
            }

            $stockLevels = $stockLevels->with('item')->get();

            // Create count lines for each stock level
            foreach ($stockLevels as $stockLevel) {
                // Try to find the primary batch for this item
                $batch = BatchInventory::where('branch_id', $branchId)
                    ->where('item_id', $stockLevel->item_id)
                    ->where('qty_remaining', '>', 0)
                    ->orderBy('received_at', 'asc') // FIFO
                    ->first();

                $this->record->lines()->create([
                    'item_id' => $stockLevel->item_id,
                    'batch_inventory_id' => $batch?->id,
                    'qty_system' => $stockLevel->qty_on_hand,
                    'unit_cost' => $stockLevel->unit_cost,
                    'selling_price' => $stockLevel->item->selling_price,
                ]);
            }

            $this->record->update(['status' => 'in_progress']);
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
