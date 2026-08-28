<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SalesOrder;
use Filament\Facades\Filament;

class SalesTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Sales Trend (Last 30 Days)';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $tenantId = Filament::getTenant()?->id;

        $sales = SalesOrder::query()
            ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->where('sold_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(sold_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            $data[]   = (float) ($sales[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Daily Sales (FCFA)',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(20, 184, 166, 0.15)',
                    'borderColor'     => 'rgba(13, 148, 136, 1)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointBackgroundColor' => 'rgba(13, 148, 136, 1)',
                    'pointRadius'     => 3,
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
