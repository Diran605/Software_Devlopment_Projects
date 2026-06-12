<?php

namespace App\Filament\Admin\Resources\GoodsReceivedNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceivedNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grn_number')
                    ->searchable()
                    ->sortable()
                    ->label('GRN Number'),
                TextColumn::make('branch.name')
                    ->sortable()
                    ->label('Branch'),
                TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Supplier'),
                TextColumn::make('purchaseOrder.po_number')
                    ->placeholder('None')
                    ->sortable()
                    ->label('PO Number'),
                TextColumn::make('receivedBy.name')
                    ->sortable()
                    ->label('Received By'),
                TextColumn::make('received_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->label('Received At'),
                TextColumn::make('total_qty')
                    ->numeric()
                    ->sortable()
                    ->label('Total Qty'),
                TextColumn::make('total_cost')
                    ->money('XAF')
                    ->sortable()
                    ->label('Total Cost'),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Branch'),
                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name', modifyQueryUsing: fn (Builder $query, callable $get) =>
                        $query->when($get('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
                    )
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Filter::make('received_at')
                    ->form([
                        DatePicker::make('received_from')
                            ->label('Received from'),
                        DatePicker::make('received_until')
                            ->label('Received until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['received_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_at', '>=', $date),
                            )
                            ->when(
                                $data['received_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_at', '<=', $date),
                            );
                    }),
                TernaryFilter::make('purchase_order_id')
                    ->label('PO Association')
                    ->placeholder('All')
                    ->trueLabel('Linked to PO')
                    ->falseLabel('No PO Link')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('purchase_order_id'),
                        false: fn ($query) => $query->whereNull('purchase_order_id'),
                    ),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Goods Received Note')
                    ->modalDescription('Are you sure you want to delete this GRN? This action will reverse all associated inventory additions and is irreversible.')
                    ->form([
                        TextInput::make('reason')
                            ->label('Reason for Deletion')
                            ->required()
                            ->minLength(10)
                            ->placeholder('Specify why you are deleting this GRN (min 10 characters).'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            app(\App\Services\GoodsReceiptService::class)->delete($record, $data['reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('GRN Deleted Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error Deleting GRN')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No goods received notes yet')
            ->emptyStateDescription('Record your first goods receipt.');
    }
}
