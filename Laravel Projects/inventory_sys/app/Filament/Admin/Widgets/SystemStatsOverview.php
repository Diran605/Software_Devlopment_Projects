<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Branch;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class SystemStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int | array | null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
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

        $branchId = $this->filters['branch_id'] ?? null;

        $sales = SalesOrder::whereBetween('sold_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('grand_total');
        
        $profit = \App\Models\SalesOrderLine::join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->whereBetween('sales_orders.sold_at', [$from, $to])
            ->whereNull('sales_orders.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sales_orders.branch_id', $branchId))
            ->sum('sales_order_lines.gross_profit');

        $purchases = \App\Models\PurchaseOrder::whereBetween('ordered_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');

        return [
            Stat::make('Total Branches', Branch::count())
                ->description('Active branches')
                ->color('primary'),
            Stat::make('Total Users', User::count())
                ->description('System accounts')
                ->color('success'),
            Stat::make('Total Items', Item::count())
                ->description('Product catalog')
                ->color('info'),
            Stat::make('Total Sales', number_format($sales, 0) . ' XAF')
                ->description('Filtered by period')
                ->color('warning'),
            Stat::make('Gross Profit', number_format($profit, 0) . ' XAF')
                ->description('Filtered by period')
                ->color('success'),
            Stat::make('Total Purchases', number_format($purchases, 0) . ' XAF')
                ->description('Filtered by period')
                ->color('info'),
        ];
    }
}

