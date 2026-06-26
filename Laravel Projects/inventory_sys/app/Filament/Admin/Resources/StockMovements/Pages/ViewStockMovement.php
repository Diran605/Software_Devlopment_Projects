<?php

namespace App\Filament\Admin\Resources\StockMovements\Pages;

use App\Filament\Admin\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['branch', 'item', 'department', 'recordedBy', 'batchInventory']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getInfolistContentComponent()]);
    }
}
