<?php

namespace App\Filament\Admin\Resources\DeletionLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeletionLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deleted_at')->dateTime()->sortable()->label('Deleted Time'),
                TextColumn::make('branch.name')->searchable()->sortable()->label('Branch'),
                TextColumn::make('deletedBy.name')->searchable()->sortable()->label('Deleted By'),
                TextColumn::make('record_type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable()->label('Record Type'),
                TextColumn::make('record_id')->sortable()->label('Record ID'),
                TextColumn::make('record_number')->searchable()->sortable()->label('Reference #'),
                TextColumn::make('reason')->limit(50)->searchable()->label('Reason'),
            ])
            ->filters([
                SelectFilter::make('branch_id')->relationship('branch', 'name')->label('Branch'),
                SelectFilter::make('record_type')
                    ->options([
                        'App\Models\GoodsReceivedNote' => 'Goods Received Note',
                        'App\Models\SalesOrder' => 'Sales Order',
                        'App\Models\StockTransfer' => 'Stock Transfer',
                        'App\Models\PurchaseOrder' => 'Purchase Order',
                    ]),
                SelectFilter::make('deleted_by')->relationship('deletedBy', 'name')->label('User')->searchable()->preload(),
            ])
            ->defaultSort('deleted_at', 'desc')
            ->actions([ViewAction::make()])
            ->toolbarActions([])
            ->emptyStateHeading('No deletion logs yet')
            ->emptyStateDescription('Deleted records will appear here.');
    }
}
