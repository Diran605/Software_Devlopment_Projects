<?php

namespace App\Filament\Admin\Resources\Items\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('uom.name')
                    ->label('UoM')
                    ->sortable(),

                TextColumn::make('selling_price')
                    ->money('XAF')
                    ->sortable(),

                TextColumn::make('unit_cost')
                    ->money('XAF')
                    ->sortable()
                    ->label('Reference Cost Price')
                    ->tooltip('This is a reference price only. Actual acquisition cost is recorded on each Opening Stock entry or GRN.')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reorder_level')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No items yet')
            ->emptyStateDescription('Create your first item to get started.');
    }
}
