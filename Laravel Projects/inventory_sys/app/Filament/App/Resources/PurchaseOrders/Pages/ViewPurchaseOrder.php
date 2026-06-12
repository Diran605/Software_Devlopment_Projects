<?php

namespace App\Filament\App\Resources\PurchaseOrders\Pages;

use App\Filament\App\Resources\GoodsReceivedNotes\GoodsReceivedNoteResource;
use App\Filament\App\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn ($record) => $record && $record->status === 'draft' && auth()->user()->can('approve.purchase-orders'))
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update([
                        'status' => 'issued',
                        'approved_by' => auth()->id(),
                        'ordered_at' => now(),
                    ]);
                    \Filament\Notifications\Notification::make()
                        ->title('Purchase Order Approved')
                        ->success()
                        ->send();
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn ($record) => $record && in_array($record->status, ['draft', 'issued']) && auth()->user()->can('cancel.purchase-orders'))
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => 'cancelled']);
                    \Filament\Notifications\Notification::make()
                        ->title('Purchase Order Cancelled')
                        ->success()
                        ->send();
                }),

            Action::make('receive_stock')
                ->label('Receive Stock')
                ->color('primary')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn ($record) => $record && in_array($record->status, ['issued', 'partially_received']) && auth()->user()->can('create.goods-received-notes'))
                ->url(fn ($record) => GoodsReceivedNoteResource::getUrl('create', ['purchase_order_id' => $record->id])),
        ];
    }
}
