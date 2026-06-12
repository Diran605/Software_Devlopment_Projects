<?php

namespace App\Filament\Admin\Resources\OpeningStocks\Pages;

use App\Filament\Admin\Resources\OpeningStocks\OpeningStockResource;
use App\Models\OpeningStockEntry;
use App\Services\NumberGeneratorService;
use App\Services\OpeningStockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOpeningStock extends CreateRecord
{
    protected static string $resource = OpeningStockResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $lines = $data['openingStockLines'] ?? [];
        unset($data['openingStockLines']);

        $branchId = $data['branch_id'];
        $data['posted_by'] = auth()->id();
        $data['entry_number'] = app(NumberGeneratorService::class)->generateEntryNumber($branchId);

        $entry = new OpeningStockEntry($data);
        $entry->save();

        app(OpeningStockService::class)->post($entry, $lines);

        return $entry;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
