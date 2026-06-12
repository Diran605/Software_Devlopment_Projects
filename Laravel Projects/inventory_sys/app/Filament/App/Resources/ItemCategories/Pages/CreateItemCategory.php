<?php

namespace App\Filament\App\Resources\ItemCategories\Pages;

use App\Filament\App\Resources\ItemCategories\ItemCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItemCategory extends CreateRecord
{
    protected static string $resource = ItemCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
