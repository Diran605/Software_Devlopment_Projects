<?php

namespace App\Filament\Admin\Resources\UnitOfMeasures\Pages;

use App\Filament\Admin\Resources\UnitOfMeasures\UnitOfMeasureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitOfMeasure extends CreateRecord
{
    protected static string $resource = UnitOfMeasureResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
