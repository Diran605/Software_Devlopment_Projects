<?php

namespace App\Filament\Admin\Resources\PackagingTypes\Pages;

use App\Filament\Admin\Resources\PackagingTypes\PackagingTypeResource;
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
