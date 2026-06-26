<?php

namespace App\Filament\App\Resources\ItemCategories\Pages;

use App\Filament\App\Resources\ItemCategories\ItemCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewItemCategory extends ViewRecord
{
    protected static string $resource = ItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
