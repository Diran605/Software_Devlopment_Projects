<?php

namespace App\Filament\App\Resources\SalesOrders\Pages;

use App\Filament\App\Resources\SalesOrders\SalesOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOrder extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                        $this->redirect($this->getResource()::getUrl('index'));
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
                ->label('Print Receipt')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->openUrlInNewTab()
                ->url(fn ($record) => route('receipts.sales', ['order' => $record->id])),
        ];
    }
}
