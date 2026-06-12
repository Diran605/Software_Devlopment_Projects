<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Branch;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $salesToday = SalesOrder::whereDate('sold_at', today())->sum('grand_total');

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
            Stat::make('Total Sales Today', number_format($salesToday, 0) . ' XAF')
                ->description('Updated live')
                ->color('warning'),
        ];
    }
}
