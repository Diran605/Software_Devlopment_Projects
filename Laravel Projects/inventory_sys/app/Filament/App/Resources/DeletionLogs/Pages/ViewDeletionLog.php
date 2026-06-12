<?php

namespace App\Filament\App\Resources\DeletionLogs\Pages;

use App\Filament\App\Resources\DeletionLogs\DeletionLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDeletionLog extends ViewRecord
{
    protected static string $resource = DeletionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
