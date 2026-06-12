<?php

namespace App\Filament\Admin\Resources\DeletionLogs\Pages;

use App\Filament\Admin\Resources\DeletionLogs\DeletionLogResource;
use Filament\Resources\Pages\ListRecords;

class ListDeletionLogs extends ListRecords
{
    protected static string $resource = DeletionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
