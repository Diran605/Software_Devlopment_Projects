<?php

namespace App\Filament\Resources\ProjectClients\Pages;

use App\Filament\Resources\ProjectClients\ProjectClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectClient extends CreateRecord
{
    protected static string $resource = ProjectClientResource::class;

    protected static bool $canCreateAnother = false;
}
