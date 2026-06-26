<?php

namespace App\Filament\App\Resources\OpeningStocks\Pages;

use App\Filament\App\Resources\OpeningStocks\OpeningStockResource;
use App\Services\OpeningStockService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOpeningStock extends EditRecord
{
    protected static string $resource = OpeningStockResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['openingStockLines'] = $this->getRecord()->openingStockLines->map(function ($line) {
            return array_merge($line->toArray(), ['id' => $line->id]); // Explicitly add id to make sure it's there
        })->toArray();
        return $data;
    }

    protected function beforeSave(): void
    {
        if ($this->getRecord()->openingStockLines()->where('is_consumed', true)->exists()) {
            Notification::make()
                ->danger()
                ->title('Edit Blocked')
                ->body('This entry contains consumed lines and cannot be edited.')
                ->send();

            $this->halt();
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $newLines = $data['openingStockLines'] ?? [];
        unset($data['openingStockLines']);

        // Get old department before updating
        $oldDeptId = $record->department_id;
        $newDeptId = $data['department_id'] ?? null;
        $departmentChanged = $oldDeptId !== $newDeptId;

        $record->update($data);

        $existingLines = $record->openingStockLines->keyBy('id');
        $openingStockService = app(OpeningStockService::class);
        $inventoryService = app(\App\Services\InventoryService::class);

        foreach ($newLines as $index => $lineData) {
            // Now we have id in lineData!
            if (isset($lineData['id']) && $existingLine = $existingLines->get($lineData['id'])) {
                // Update basic fields (batch_number, expiry_date, etc.)
                $basicFields = collect($lineData)->only(['batch_number', 'expiry_date']);
                
                if ($basicFields->isNotEmpty()) {
                    $existingLine->update($basicFields->toArray());
                    
                    // Also update the corresponding batch inventory
                    if ($existingLine->batchInventory) {
                        $existingLine->batchInventory->update($basicFields->toArray());
                    }
                }

                // Handle department change if needed
                if ($departmentChanged) {
                    // Update OpeningStockLine's department_id
                    $existingLine->update(['department_id' => $newDeptId]);
                    
                    // Update BatchInventory's department_id
                    if ($existingLine->batchInventory) {
                        $existingLine->batchInventory->update(['department_id' => $newDeptId]);
                    }
                    
                    // Move stock from old department to new department
                    $inventoryService->moveStockBetweenDepartments(
                        branchId: $record->branch_id,
                        itemId: $existingLine->item_id,
                        oldDepartmentId: $oldDeptId,
                        newDepartmentId: $newDeptId,
                        qty: $existingLine->qty_on_hand,
                        unitCost: $existingLine->unit_cost
                    );
                }

                // Then handle qty/cost changes via the service
                if ($existingLine->qty_on_hand != $lineData['qty_on_hand'] || $existingLine->unit_cost != $lineData['unit_cost']) {
                    $openingStockService->editLine($existingLine, $lineData['qty_on_hand'], $lineData['unit_cost']);
                }
            }
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
