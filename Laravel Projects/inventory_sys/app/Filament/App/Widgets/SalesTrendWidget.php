<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SalesOrder;
use Filament\Facades\Filament;
use Carbon\Carbon;

class SalesTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Sales Trend (Last 30 Days)';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $tenantId = Filament::getTenant()?->id;

        // Sales for the last 30 days
        $sales = SalesOrder::query()
            ->when($tenantId, fn($q) => $q->where('branch_id', $tenantId))
            ->where('sold_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(sold_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            $data[] = (float) ($sales[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Daily Sales (FCFA)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
