<?php

namespace App\Filament\App\Resources\StockTransfers\Pages;

use App\Filament\App\Resources\StockTransfers\StockTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $lines = $data['stockTransferLines'] ?? [];
        unset($data['stockTransferLines']);
        
        $data['requested_by'] = auth()->id();

        $transfer = parent::handleRecordCreation($data);

        app(\App\Services\StockTransferService::class)->createRequest($transfer, $lines);

        return $transfer;
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
