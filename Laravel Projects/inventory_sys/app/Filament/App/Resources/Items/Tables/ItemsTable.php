<?php

namespace App\Filament\App\Resources\Items\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->sortable()
                    ->label('Category'),
                TextColumn::make('uom.name')
                    ->sortable()
                    ->label('UOM'),
                TextColumn::make('selling_price')
                    ->money('XAF')
                    ->sortable()
                    ->label('Selling Price'),
                TextColumn::make('unit_cost')
                    ->money('XAF')
                    ->sortable()
                    ->label('Reference Cost Price')
                    ->tooltip('This is a reference price only. Actual acquisition cost is recorded on each Opening Stock entry or GRN.'),
                TextColumn::make('stock')
                    ->label('Stock Level')
                    ->state(function (\App\Models\Item $record) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if (!$tenant) {
                            return 0;
                        }
                        return $record->itemStockLevels()
                            ->where('branch_id', $tenant->id)
                            ->sum('qty_on_hand');
                    })
                    ->badge()
                    ->color(fn ($state, \App\Models\Item $record) => $state <= $record->reorder_level ? 'danger' : 'success'),
                TextColumn::make('reorder_level')
                    ->numeric()
                    ->sortable()
                    ->label('Reorder Lvl'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->defaultSort('name')
            ->actions([
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
