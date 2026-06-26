<?php

namespace App\Filament\Admin\Resources\PackagingTypes\Pages;

use App\Filament\Admin\Resources\PackagingTypes\PackagingTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPackagingType extends ViewRecord
{
    protected static string $resource = PackagingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
