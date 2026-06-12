<?php

namespace App\Filament\App\Widgets;

use App\Models\ItemStockLevel;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Facades\Filament;

class LowStockAlert extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $branchId = $tenant ? $tenant->id : null;

        return $table
            ->query(
                ItemStockLevel::query()
                    ->where('branch_id', $branchId)
                    ->whereColumn('qty_on_hand', '<=', 'reorder_level')
                    ->with(['item', 'item.category'])
                    ->orderBy('qty_on_hand', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('item.sku')
                    ->label('SKU'),
                Tables\Columns\TextColumn::make('item.category.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('qty_on_hand')
                    ->label('Qty On Hand')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->state(fn (ItemStockLevel $record): string => $record->qty_on_hand === 0 ? 'Out of Stock' : 'Low Stock')
                    ->color(fn (string $state): string => $state === 'Out of Stock' ? 'danger' : 'warning'),
            ])
            ->paginated(false)
            ->emptyStateHeading('✅ All items are sufficiently stocked.');
    }
}
