<?php

namespace App\Filament\App\Resources\ClearanceRules\Pages;

use App\Filament\App\Resources\ClearanceRules\ClearanceRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClearanceRules extends ListRecords
{
    protected static string $resource = ClearanceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
