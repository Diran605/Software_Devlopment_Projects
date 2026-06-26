<?php

namespace App\Filament\App\Resources\Expenses\Pages;

use App\Filament\App\Resources\Expenses\ExpenseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
