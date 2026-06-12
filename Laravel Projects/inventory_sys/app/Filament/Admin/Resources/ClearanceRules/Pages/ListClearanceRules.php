<?php

namespace App\Filament\Admin\Resources\ClearanceRules\Pages;

use App\Filament\Admin\Resources\ClearanceRules\ClearanceRuleResource;
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
