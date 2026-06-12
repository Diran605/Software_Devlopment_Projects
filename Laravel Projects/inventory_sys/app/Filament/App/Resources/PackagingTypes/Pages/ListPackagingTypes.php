<?php

namespace App\Filament\App\Resources\PackagingTypes\Pages;

use App\Filament\App\Resources\PackagingTypes\PackagingTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackagingTypes extends ListRecords
{
    protected static string $resource = PackagingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
