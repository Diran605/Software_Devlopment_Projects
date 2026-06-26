<?php

namespace App\Filament\App\Resources\ExpenseCategories\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class ExpenseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No expense categories yet')
            ->emptyStateDescription('Create your first expense category.');
    }
}
