<?php

namespace App\Filament\App\Resources\InventoryCounts\Pages;

use App\Filament\App\Resources\InventoryCounts\InventoryCountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryCounts extends ListRecords
{
    protected static string $resource = InventoryCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
