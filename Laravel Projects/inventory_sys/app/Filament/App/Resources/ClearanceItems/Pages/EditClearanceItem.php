<?php

namespace App\Filament\App\Resources\ClearanceItems\Pages;

use App\Filament\App\Resources\ClearanceItems\ClearanceItemResource;
use Filament\Resources\Pages\EditRecord;

class EditClearanceItem extends EditRecord
{
    protected static string $resource = ClearanceItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
