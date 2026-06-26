<?php

namespace App\Filament\App\Resources\StockMovements\Pages;

use App\Filament\App\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['item', 'department', 'recordedBy', 'batchInventory']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getInfolistContentComponent()]);
    }
}
