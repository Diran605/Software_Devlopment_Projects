<?php

namespace App\Filament\App\Resources\ClearanceItems\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ClearanceItemsTable
{
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
                TextColumn::make('batch_inventory.batch_number')
                    ->label('Batch #'),
                TextColumn::make('batch_inventory.expiry_date')
                    ->label('Expiry Date')
                    ->date(),
                TextColumn::make('days_to_expiry')
                    ->label('Days to Expiry')
                    ->sortable(),
                TextColumn::make('qty_flagged')
                    ->label('Qty Flagged'),
                BadgeColumn::make('urgency_status')
                    ->colors([
                        'warning' => fn($state) => $state === 'Approaching',
                        'orange' => fn($state) => $state === 'Urgent',
                        'danger' => fn($state) => $state === 'Critical',
                        'gray' => fn($state) => $state === 'Expired',
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
                    ->money('usd')
                    ->label('Clearance Price'),
            ])
            ->filters([
                SelectFilter::make('urgency_status')
                    ->options([
                        'Approaching' => 'Approaching',
                        'Urgent' => 'Urgent',
                        'Critical' => 'Critical',
                        'Expired' => 'Expired',
                    ])
                    ->multiple(),
                SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                        'actioned' => 'Actioned',
                    ])
                    ->multiple(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No flagged items yet');
    }
}
