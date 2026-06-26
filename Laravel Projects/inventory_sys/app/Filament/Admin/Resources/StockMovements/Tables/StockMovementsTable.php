<?php

namespace App\Filament\Admin\Resources\StockMovements\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable()
                    ->label('Branch'),
                TextColumn::make('moved_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('item.name')
                    ->searchable()
                    ->sortable()
                    ->label('Item'),
                TextColumn::make('batch_number')
                    ->searchable()
                    ->label('Batch #'),
                TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->placeholder('N/A')
                    ->label('Expiry'),
                TextColumn::make('movement_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'opening_stock' => 'gray',
                        'goods_receipt' => 'success',
                        'sale' => 'info',
                        'transfer_out' => 'warning',
                        'transfer_in' => 'success',
                        'reversal' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->label('Type'),
                TextColumn::make('qty_in')
                    ->numeric()
                    ->alignRight()
                    ->label('Qty In'),
                TextColumn::make('qty_out')
                    ->numeric()
                    ->alignRight()
                    ->label('Qty Out'),
                TextColumn::make('qty_before')
                    ->numeric()
                    ->alignRight()
                    ->label('Before'),
                TextColumn::make('qty_after')
                    ->numeric()
                    ->alignRight()
                    ->label('After'),
                TextColumn::make('unit_cost')
                    ->numeric()
                    ->alignRight()
                    ->label('Unit Cost'),
                TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
                SelectFilter::make('movement_type')
                    ->options([
                        'opening_stock' => 'Opening Stock',
                        'goods_receipt' => 'Goods Receipt',
                        'sale' => 'Sale',
                        'transfer_out' => 'Transfer Out',
                        'transfer_in' => 'Transfer In',
                        'reversal' => 'Reversal',
                    ])
                    ->multiple()
                    ->label('Movement Type'),
                SelectFilter::make('item_id')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Item'),
                SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department'),
            ])
            ->defaultSort('moved_at', 'desc')
            ->actions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No stock movements yet')
            ->emptyStateDescription('Stock movements will appear here.');
    }
}
