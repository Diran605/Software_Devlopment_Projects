<?php

namespace App\Filament\Admin\Resources\ItemCategories\Pages;

use App\Filament\Admin\Resources\ItemCategories\ItemCategoryResource;
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
