<?php

namespace App\Filament\App\Resources\SalesOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->label('Order Number'),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn ($record) => $record->customer_name ?: 'Walk-in Customer')
                    ->label('Customer'),
                TextColumn::make('sales_order_lines_count')
                    ->counts('salesOrderLines')
                    ->sortable()
                    ->label('Items Count'),
                TextColumn::make('grand_total')
                    ->money('XAF')
                    ->sortable()
                    ->label('Grand Total'),
                TextColumn::make('gross_profit')
                    ->money('XAF')
                    ->sortable()
                    ->label('Gross Profit'),
                TextColumn::make('amount_tendered')
                    ->money('XAF')
                    ->sortable()
                    ->label('Amount Tendered'),
                TextColumn::make('servedBy.name')
                    ->sortable()
                    ->label('Served By'),
                TextColumn::make('sold_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->label('Sold At'),
            ])
            ->defaultSort('sold_at', 'desc')
            ->filters([
                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Customer'),
                SelectFilter::make('served_by')
                    ->relationship('servedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Served By'),
                Filter::make('sold_at')
                    ->form([
                        DatePicker::make('sold_from')
                            ->label('Sold from'),
                        DatePicker::make('sold_until')
                            ->label('Sold until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sold_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sold_at', '>=', $date),
                            )
                            ->when(
                                $data['sold_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sold_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Sales Order')
                    ->modalDescription('Are you sure you want to delete this Sales Order? All allocated stock will be returned to inventory and this action cannot be undone.')
                    ->form([
                        TextInput::make('reason')
                            ->label('Reason for Deletion')
                            ->required()
                            ->minLength(10)
                            ->placeholder('Specify why you are deleting this Sales Order (min 10 characters).'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            app(\App\Services\SalesOrderService::class)->delete($record, $data['reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Sales Order Deleted')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error Deleting Sales Order')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->openUrlInNewTab()
                    ->url(fn ($record) => route('receipts.sales', ['order' => $record->id])),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No sales orders yet')
            ->emptyStateDescription('Create your first sale.');
    }
}
