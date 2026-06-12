<?php

namespace App\Filament\Admin\Resources\SalesOrders\Pages;

use App\Filament\Admin\Resources\SalesOrders\SalesOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $lines = $data['salesOrderLines'] ?? [];
        unset($data['salesOrderLines']);
        
        $data['served_by'] = auth()->id();

        $order = parent::handleRecordCreation($data);

        app(\App\Services\SalesOrderService::class)->create($order, $lines);

        return $order;
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
