<?php

namespace App\Filament\App\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->searchable()
                    ->sortable()
                    ->label('PO Number'),
                TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Supplier'),
                TextColumn::make('createdBy.name')
                    ->sortable()
                    ->label('Created By'),
                TextColumn::make('ordered_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->label('Ordered At'),
                TextColumn::make('expected_delivery_at')
                    ->date('M d, Y')
                    ->sortable()
                    ->label('Expected Delivery'),
                TextColumn::make('total_amount')
                    ->money('XAF')
                    ->sortable()
                    ->label('Total Amount'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'issued' => 'info',
                        'partially_received' => 'warning',
                        'fully_received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'partially_received' => 'Partially received',
                        'fully_received' => 'Fully received',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Filter::make('ordered_at')
                    ->form([
                        DatePicker::make('ordered_from')
                            ->label('Ordered from'),
                        DatePicker::make('ordered_until')
                            ->label('Ordered until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ordered_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date),
                            )
                            ->when(
                                $data['ordered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record && $record->status === 'draft'),
                DeleteAction::make()
                    ->visible(fn ($record) => $record && $record->status === 'draft'),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No purchase orders yet')
            ->emptyStateDescription('Create your first purchase order.');
    }
}
