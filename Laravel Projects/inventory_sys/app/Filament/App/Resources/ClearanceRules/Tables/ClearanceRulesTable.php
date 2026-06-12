<?php

namespace App\Filament\App\Resources\ClearanceRules\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;

class ClearanceRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Urgency Label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('days_min')
                    ->label('Days Min')
                    ->sortable(),
                TextColumn::make('days_max')
                    ->label('Days Max')
                    ->sortable(),
                TextColumn::make('discount')
                    ->label('Discount %')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('clearance_items_count')
                    ->counts('clearanceItems')
                    ->label('Items'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No clearance rules yet')
            ->emptyStateDescription('Create your first clearance rule.');
    }
}
