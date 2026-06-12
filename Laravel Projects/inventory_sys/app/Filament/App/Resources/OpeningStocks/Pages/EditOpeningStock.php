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
        $data['openingStockLines'] = $this->getRecord()->openingStockLines->toArray();
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

        $record->update($data);

        $existingLines = $record->openingStockLines->keyBy('id');
        $openingStockService = app(OpeningStockService::class);

        foreach ($newLines as $lineData) {
            if (isset($lineData['id']) && $existingLine = $existingLines->get($lineData['id'])) {
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
