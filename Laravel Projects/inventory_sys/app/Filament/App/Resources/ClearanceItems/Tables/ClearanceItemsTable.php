<?php

namespace App\Filament\App\Resources\ClearanceItems\Tables;

use App\Filament\Concerns\ConfiguresClearanceItemsTable;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClearanceItemsTable
{
    use ConfiguresClearanceItemsTable;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable(),
                TextColumn::make('item.category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('batchInventory.batch_number')
                    ->label('Batch #')
                    ->searchable(),
                TextColumn::make('batchInventory.expiry_date')
                    ->label('Expiry Date')
                    ->date(),
                TextColumn::make('days_to_expiry')
                    ->label('Days to Expiry')
                    ->sortable(),
                TextColumn::make('qty_flagged')
                    ->label('Qty Flagged'),
                BadgeColumn::make('urgency_status')
                    ->colors([
                        'warning' => fn ($state) => $state === 'Approaching',
                        'orange' => fn ($state) => $state === 'Urgent',
                        'danger' => fn ($state) => $state === 'Critical',
                        'gray' => fn ($state) => $state === 'Expired',
                    ]),
                BadgeColumn::make('approval_status')
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'approved',
                        'danger' => 'declined',
                        'info' => 'actioned',
                    ]),
                TextColumn::make('rule.discount')
                    ->label('Suggested Discount')
                    ->suffix('%'),
                TextColumn::make('clearance_price')
                    ->money('xaf')
                    ->label('Clearance Price'),
            ])
            ->filters(self::clearanceItemFilters())
            ->actions([
                ViewAction::make(),
            ])
            ->toolbarActions(self::clearanceItemBulkActions())
            ->emptyStateHeading('No flagged items yet');
    }
}
