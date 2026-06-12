<?php

namespace App\Filament\App\Widgets;

use App\Models\Item;
use App\Models\ItemStockLevel;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $tenantId = $tenant ? $tenant->id : null;

        $totalSkus = Item::where('is_active', true)
            ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->count();

        $totalUnits = ItemStockLevel::when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->sum('qty_on_hand');

        $totalValue = ItemStockLevel::when($tenantId, fn ($q) => $q->where('item_stock_levels.branch_id', $tenantId))
            ->join('items', 'items.id', '=', 'item_stock_levels.item_id')
            ->selectRaw('SUM(item_stock_levels.qty_on_hand * items.unit_cost) as total_val')
            ->value('total_val') ?? 0;

        $lowStockCount = ItemStockLevel::when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->count();

        return [
            Stat::make('Total SKUs', $totalSkus),
            Stat::make('Total Units on Hand', number_format($totalUnits)),
            Stat::make('Total Stock Value', number_format($totalValue, 0) . ' XAF'),
            Stat::make('Low Stock Items', $lowStockCount),
        ];
    }
}
