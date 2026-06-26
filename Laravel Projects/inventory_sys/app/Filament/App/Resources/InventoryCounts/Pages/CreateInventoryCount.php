<?php

namespace App\Filament\App\Resources\InventoryCounts\Pages;

use App\Filament\App\Resources\InventoryCounts\InventoryCountResource;
use App\Models\BatchInventory;
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
            $batches = BatchInventory::query()
                ->where('branch_id', $branchId)
                ->where('qty_remaining', '>', 0);

            if ($departmentId) {
                $batches->where('department_id', $departmentId);
            }

            $batches = $batches->with('item')->orderBy('expiry_date')->get();

            foreach ($batches as $batch) {
                $this->record->lines()->create([
                    'item_id' => $batch->item_id,
                    'batch_inventory_id' => $batch->id,
                    'qty_system' => $batch->qty_remaining,
                    'unit_cost' => $batch->unit_cost,
                    'selling_price' => $batch->item->selling_price,
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
