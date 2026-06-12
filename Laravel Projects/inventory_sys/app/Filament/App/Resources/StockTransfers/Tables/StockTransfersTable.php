<?php

namespace App\Filament\App\Resources\StockTransfers\Tables;

use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_number')
                    ->searchable()
                    ->sortable()
                    ->label('Transfer #'),
                TextColumn::make('transfer_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inter_branch' => 'info',
                        'inter_department' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'inter_branch' => 'Inter-Branch',
                        'inter_department' => 'Inter-Dept',
                        default => ucfirst($state),
                    })
                    ->sortable()
                    ->label('Type'),
                TextColumn::make('fromBranch.name')
                    ->sortable()
                    ->label('From Branch'),
                TextColumn::make('fromDepartment.name')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('From Dept'),
                TextColumn::make('toBranch.name')
                    ->sortable()
                    ->label('To Branch'),
                TextColumn::make('toDepartment.name')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('To Dept'),
                TextColumn::make('stock_transfer_lines_count')
                    ->counts('stockTransferLines')
                    ->sortable()
                    ->label('Lines'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'info',
                        'in_transit' => 'primary',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'in_transit' => 'In Transit',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($state),
                    })
                    ->sortable()
                    ->label('Status'),
                TextColumn::make('requestedBy.name')
                    ->sortable()
                    ->label('Requested By'),
                TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->label('Created'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'in_transit' => 'In Transit',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ])
                    ->label('Status'),
                SelectFilter::make('transfer_type')
                    ->options([
                        'inter_branch' => 'Inter-Branch',
                        'inter_department' => 'Inter-Department',
                    ])
                    ->label('Transfer Type'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Transfer for Approval')
                    ->modalDescription('This will submit the transfer request for approval. Continue?')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        try {
                            app(\App\Services\StockTransferService::class)->submit($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Transfer Submitted')
                                ->body("Transfer {$record->transfer_number} submitted for approval.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Transfer')
                    ->modalDescription('Approve this stock transfer request? The transfer can then be dispatched.')
                    ->visible(fn ($record) => $record->status === 'pending_approval')
                    ->action(function ($record) {
                        try {
                            app(\App\Services\StockTransferService::class)->approve($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Transfer Approved')
                                ->body("Transfer {$record->transfer_number} has been approved.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('dispatch')
                    ->label('Dispatch')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Dispatch Transfer')
                    ->modalDescription('This will deduct stock from the source location and mark the transfer as in-transit. This action cannot be undone.')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->action(function ($record) {
                        try {
                            app(\App\Services\StockTransferService::class)->dispatch($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Transfer Dispatched')
                                ->body("Transfer {$record->transfer_number} is now in transit.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error Dispatching')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Receive Transfer')
                    ->modalDescription('Confirm receipt of all items. Stock will be added to the destination branch/department.')
                    ->visible(fn ($record) => $record->status === 'in_transit')
                    ->action(function ($record) {
                        try {
                            // Receive all lines at their dispatched quantity
                            app(\App\Services\StockTransferService::class)->receive($record, []);
                            \Filament\Notifications\Notification::make()
                                ->title('Transfer Received')
                                ->body("Transfer {$record->transfer_number} has been received successfully.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error Receiving')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Transfer')
                    ->modalDescription('Are you sure you want to cancel this transfer? This action cannot be undone.')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'pending_approval', 'approved']))
                    ->action(function ($record) {
                        try {
                            app(\App\Services\StockTransferService::class)->cancel($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Transfer Cancelled')
                                ->body("Transfer {$record->transfer_number} has been cancelled.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No stock transfers yet')
            ->emptyStateDescription('Create a new stock transfer to move items between branches or departments.');
    }
}
