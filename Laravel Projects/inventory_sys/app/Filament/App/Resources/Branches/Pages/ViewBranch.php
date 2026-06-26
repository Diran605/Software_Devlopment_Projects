<?php

namespace App\Filament\App\Resources\Branches\Pages;

use App\Filament\App\Resources\Branches\BranchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
