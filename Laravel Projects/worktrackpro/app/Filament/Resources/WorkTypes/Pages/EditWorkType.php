<?php

namespace App\Filament\Resources\WorkTypes\Pages;

use App\Filament\Resources\WorkTypes\WorkTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkType extends EditRecord
{
    protected static string $resource = WorkTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
