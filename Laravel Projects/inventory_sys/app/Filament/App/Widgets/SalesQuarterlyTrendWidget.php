<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SalesOrder;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class SalesQuarterlyTrendWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Financial Sales Trend (Monthly)';

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '340px';

    protected function getData(): array
    {
        $tenantId = Filament::getTenant()?->id ?? ($this->filters['branch_id'] ?? null);

        // Determine the year to display from dashboard filters
        $period = $this->filters['period'] ?? 'month';
        $year   = (int) now()->format('Y');

        if ($period === 'custom') {
            $customFrom = $this->filters['from'] ?? null;
            if ($customFrom) {
                $year = (int) Carbon::parse($customFrom)->format('Y');
            }
        }

        $months = [
            1  => 'Jan', 2  => 'Feb', 3  => 'Mar',
            4  => 'Apr', 5  => 'May', 6  => 'Jun',
            7  => 'Jul', 8  => 'Aug', 9  => 'Sep',
            10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        $labels  = [];
        $revenue = [];
        $profit  = [];

        foreach ($months as $monthNum => $label) {
            $labels[] = "{$label} {$year}";

            $qRevenue = SalesOrder::query()
                ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
                ->whereYear('sold_at', $year)
                ->whereMonth('sold_at', $monthNum)
                ->sum('grand_total');

            $qProfit = SalesOrder::query()
                ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
                ->whereYear('sold_at', $year)
                ->whereMonth('sold_at', $monthNum)
                ->sum('gross_profit');

            $revenue[] = (float) $qRevenue;
            $profit[]  = (float) $qProfit;
        }

        return [
            'datasets' => [
                [
                    'label'                => "Revenue {$year} (FCFA)",
                    'data'                 => $revenue,
                    'backgroundColor'      => 'rgba(20, 184, 166, 0.15)',
                    'borderColor'          => 'rgba(13, 148, 136, 1)',
                    'borderWidth'          => 2.5,
                    'fill'                 => true,
                    'tension'              => 0.4,
                    'pointBackgroundColor' => 'rgba(13, 148, 136, 1)',
                    'pointRadius'          => 5,
                    'pointHoverRadius'     => 8,
                ],
                [
                    'label'                => "Gross Profit {$year} (FCFA)",
                    'data'                 => $profit,
                    'backgroundColor'      => 'rgba(6, 182, 212, 0.10)',
                    'borderColor'          => 'rgba(8, 145, 178, 1)',
                    'borderWidth'          => 2,
                    'fill'                 => true,
                    'tension'              => 0.4,
                    'pointBackgroundColor' => 'rgba(8, 145, 178, 1)',
                    'pointRadius'          => 5,
                    'pointHoverRadius'     => 8,
                    'borderDash'           => [6, 3],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins'             => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                    'labels'   => [
                        'color'   => 'rgba(255,255,255,0.75)',
                        'padding' => 20,
                        'font'    => ['size' => 12],
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid'  => ['color' => 'rgba(255,255,255,0.06)'],
                    'ticks' => [
                        'color' => 'rgba(255,255,255,0.7)',
                        'font'  => ['size' => 13, 'weight' => '700'],
                    ],
                ],
                'y' => [
                    'grid'        => ['color' => 'rgba(255,255,255,0.06)'],
                    'ticks'       => ['color' => 'rgba(255,255,255,0.6)'],
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
