<?php

namespace App\Filament\App\Resources\ExpenseCategories\Pages;

use App\Filament\App\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExpenseCategory extends ViewRecord
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
