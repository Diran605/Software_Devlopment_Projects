<?php

namespace App\Filament\Resources\WorkTypes\Pages;

use App\Filament\Resources\WorkTypes\WorkTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkType extends CreateRecord
{
    protected static string $resource = WorkTypeResource::class;

    protected static bool $canCreateAnother = false;
}
