<?php

namespace App\Filament\Admin\Resources\SalesOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->sortable()
                    ->label('Branch'),
                TextColumn::make('department.name')
                    ->placeholder('—')
                    ->sortable()
                    ->label('Department'),
                TextColumn::make('servedBy.name')
                    ->sortable()
                    ->label('Served By'),
                TextColumn::make('customer.name')
                    ->placeholder(fn ($record) => $record->customer_name ?: 'Walk-in Customer')
                    ->sortable()
                    ->label('Customer'),
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sold_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->money('XAF')
                    ->sortable(),
                TextColumn::make('discount_total')
                    ->money('XAF')
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('XAF')
                    ->sortable(),
                TextColumn::make('cogs_total')
                    ->money('XAF')
                    ->sortable(),
                TextColumn::make('gross_profit')
                    ->money('XAF')
                    ->sortable(),
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
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No sales orders yet')
            ->emptyStateDescription('Create your first sale.');
    }
}
