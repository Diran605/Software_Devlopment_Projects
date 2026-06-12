<?php

namespace App\Filament\App\Resources\SalesOrders\Pages;

use App\Filament\App\Resources\SalesOrders\SalesOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $lines = $data['salesOrderLines'] ?? [];
            unset($data['salesOrderLines']);
            
            $data['served_by'] = auth()->id();

            $order = parent::handleRecordCreation($data);

            try {
                app(\App\Services\SalesOrderService::class)->create($order, $lines);
            } catch (\App\Exceptions\InsufficientStockException $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Insufficient Stock')
                    ->body($e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();

                throw new \Filament\Support\Exceptions\Halt();
            }

            return $order;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
