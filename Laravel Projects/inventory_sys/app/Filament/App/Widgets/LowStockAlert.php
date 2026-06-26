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
            ->heading('📉 Low Stock Alert')
            ->query(
                ItemStockLevel::query()
                    ->join('items', 'items.id', '=', 'item_stock_levels.item_id')
                    ->when($branchId, fn ($query) => $query->where('item_stock_levels.branch_id', $branchId))
                    ->whereNotNull('item_stock_levels.department_id')
                    ->where('items.reorder_level', '>', 0)
                    ->whereColumn('item_stock_levels.qty_on_hand', '<=', 'items.reorder_level')
                    ->select('item_stock_levels.*')
                    ->with(['item', 'item.category'])
                    ->orderBy('item_stock_levels.qty_on_hand', 'asc')
                    ->limit(10)
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
                Tables\Columns\TextColumn::make('item.reorder_level')
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
