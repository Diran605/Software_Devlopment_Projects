<?php

namespace App\Filament\Admin\Resources\ClearanceItems\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ClearanceItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name'),
                TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable(),
                TextColumn::make('item.category.name')
                    ->label('Category'),
                TextColumn::make('batch_inventory.batch_number')
                    ->label('Batch #'),
                TextColumn::make('batch_inventory.expiry_date')
                    ->label('Expiry Date')
                    ->date(),
                TextColumn::make('days_to_expiry')
                    ->label('Days to Expiry'),
                TextColumn::make('qty_flagged')
                    ->label('Qty Flagged'),
                BadgeColumn::make('urgency_status'),
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
                    ->money('usd'),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No flagged items yet');
    }
}
