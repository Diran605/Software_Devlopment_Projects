<?php

namespace App\Filament\Admin\Resources\ItemCategories\Pages;

use App\Filament\Admin\Resources\ItemCategories\ItemCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItemCategories extends ListRecords
{
    protected static string $resource = ItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
