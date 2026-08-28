<?php

namespace App\Filament\App\Widgets;

use App\Models\SalesOrder;
use App\Models\ItemStockLevel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Facades\Filament;
use Carbon\Carbon;

class BranchStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return [];
        }
        $tenantId = $tenant->id;

        // Date logic
        $period = $this->filters['period'] ?? 'today';
        $from = today()->startOfDay();
        $to = today()->endOfDay();

        if ($period === 'week') {
            $from = now()->startOfWeek();
            $to = now()->endOfWeek();
        } elseif ($period === 'month') {
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
        } elseif ($period === 'custom') {
            $from = isset($this->filters['from']) ? Carbon::parse($this->filters['from'])->startOfDay() : today()->startOfDay();
            $to = isset($this->filters['to']) ? Carbon::parse($this->filters['to'])->endOfDay() : today()->endOfDay();
        }

        // Previous period logic
        $diff = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($diff);
        $prevTo = $to->copy()->subDays($diff);

        // Revenue stats
        $currentRevenue = SalesOrder::where('branch_id', $tenantId)
            ->whereBetween('sold_at', [$from, $to])
            ->sum('grand_total');
        $prevRevenue = SalesOrder::where('branch_id', $tenantId)
            ->whereBetween('sold_at', [$prevFrom, $prevTo])
            ->sum('grand_total');

        $totalSalesAllTime = SalesOrder::where('branch_id', $tenantId)
            ->sum('grand_total');

        $revChange = 0;
        if ($prevRevenue > 0) {
            $revChange = (($currentRevenue - $prevRevenue) / $prevRevenue) * 100;
        }

        $revDesc = $revChange >= 0
            ? '+' . number_format($revChange, 1) . '% increase vs last period'
            : number_format($revChange, 1) . '% decrease vs last period';
        $revColor = $revChange >= 0 ? 'success' : 'danger';


        // Items below reorder level
        $lowStockCount = ItemStockLevel::where('branch_id', $tenantId)
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->count();


        return [
            Stat::make('Total Sales (All Time)', number_format($totalSalesAllTime, 0) . ' FCFA')
                ->description('All time accumulated sales')
                ->color('success'),
            Stat::make('Revenue', number_format($currentRevenue, 0) . ' FCFA')
                ->description($revDesc)
                ->descriptionIcon($revChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revColor),
            Stat::make('Items Below Reorder', $lowStockCount)
                ->description('Needs restocking')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),
        ];
    }
}
