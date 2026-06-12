<?php

namespace App\Filament\App\Widgets;

use App\Models\SalesOrderLine;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TopSellingItemsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected string $view = 'filament.app.widgets.top-selling-items';

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $branchId = $tenant ? $tenant->id : null;

        // Date logic from filters
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

        // Build and execute the aggregated query
        $items = SalesOrderLine::query()
            ->select([
                'sales_order_lines.item_id',
                'items.name as item_name',
                'item_categories.name as category_name',
                DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
            ])
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->where('sales_orders.branch_id', $branchId)
            ->whereBetween('sales_orders.sold_at', [$from, $to])
            ->whereNull('sales_order_lines.deleted_at')
            ->groupBy('sales_order_lines.item_id', 'items.name', 'item_categories.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        return [
            'items' => $items,
        ];
    }
}
