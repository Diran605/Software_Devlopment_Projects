<?php

namespace App\Filament\App\Widgets;

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


        $totalValue = ItemStockLevel::when($tenantId, fn ($q) => $q->where('item_stock_levels.branch_id', $tenantId))
            ->join('items', 'items.id', '=', 'item_stock_levels.item_id')
            ->selectRaw('SUM(item_stock_levels.qty_on_hand * items.unit_cost) as total_val')
            ->value('total_val') ?? 0;

        $lowStockCount = ItemStockLevel::when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->count();

        return [
            Stat::make('Total Stock Value', number_format($totalValue, 0) . ' XAF')
                ->description('Current inventory value at cost')
                ->color('primary'),
            Stat::make('Low Stock Items', $lowStockCount)
                ->description('Items at or below reorder level')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),
        ];
    }
}
