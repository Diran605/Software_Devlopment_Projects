<?php

namespace App\Filament\App\Resources\PackagingTypes\Pages;

use App\Filament\App\Resources\PackagingTypes\PackagingTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePackagingType extends CreateRecord
{
    protected static string $resource = PackagingTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
