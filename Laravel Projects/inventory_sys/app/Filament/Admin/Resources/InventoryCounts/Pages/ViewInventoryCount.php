<?php

namespace App\Filament\Admin\Resources\InventoryCounts\Pages;

use App\Filament\Admin\Resources\InventoryCounts\InventoryCountResource;
use App\Filament\Concerns\HasInventoryCountView;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ViewInventoryCount extends ViewRecord implements HasTable
{
    use HasInventoryCountView;
    use InteractsWithTable {
        HasInventoryCountView::table insteadof InteractsWithTable;
    }

    protected static string $resource = InventoryCountResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getInventoryCountHeaderActions();
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->initializeInventoryCountGroupExpansion();
    }
}
