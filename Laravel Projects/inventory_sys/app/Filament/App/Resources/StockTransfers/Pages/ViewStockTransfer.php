<?php

namespace App\Filament\App\Resources\StockTransfers\Pages;

use App\Filament\App\Resources\StockTransfers\StockTransferResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransfer extends ViewRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit for Approval')
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
                        $this->refreshFormData(['status']);
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
                        $this->refreshFormData(['status', 'approved_by']);
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
                        $this->refreshFormData(['status', 'transferred_at']);
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
                        app(\App\Services\StockTransferService::class)->receive($record, []);
                        \Filament\Notifications\Notification::make()
                            ->title('Transfer Received')
                            ->body("Transfer {$record->transfer_number} has been received successfully.")
                            ->success()
                            ->send();
                        $this->refreshFormData(['status', 'received_at']);
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
                ->label('Cancel Transfer')
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
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Stock Transfer')
                ->modalDescription('Are you sure you want to delete this Stock Transfer? This action cannot be undone.')
                ->form([
                    TextInput::make('reason')
                        ->label('Reason for Deletion')
                        ->required()
                        ->minLength(10)
                        ->placeholder('Specify why you are deleting this transfer (min 10 characters).'),
                ])
                ->visible(fn ($record) => in_array($record->status, ['draft', 'cancelled']))
                ->action(function ($record, array $data) {
                    try {
                        app(\App\Services\DeletionLogService::class)->record(
                            deletedBy: auth()->id(),
                            record: $record,
                            reason: $data['reason'],
                            recordNumber: $record->transfer_number,
                        );
                        $record->delete();
                        \Filament\Notifications\Notification::make()
                            ->title('Stock Transfer Deleted')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error Deleting')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
