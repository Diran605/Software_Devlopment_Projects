<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ProvidesReportPdfParams;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

class TrendingProductsReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.trending-products-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-fire';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Trending Products';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id'     => Filament::getTenant()?->id,
            'date_from'     => now()->startOfMonth()->format('Y-m-d'),
            'date_to'       => now()->endOfMonth()->format('Y-m-d'),
            'sort_by'       => 'revenue',
            'limit_preset'  => 20,
            'limit_custom'  => 100,
            'department_id' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(Filament::getTenant() ? 5 : 6)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::orderBy('name')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(!Filament::getTenant()),
                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->live(),
                        DatePicker::make('date_to')
                            ->label('To Date')
                            ->live(),
                        Select::make('sort_by')
                            ->label('Rank By')
                            ->options([
                                'revenue'  => 'Revenue (Highest)',
                                'qty'      => 'Qty Sold (Highest)',
                                'profit'   => 'Profit (Highest)',
                            ])
                            ->default('revenue')
                            ->live(),
                        Select::make('limit_preset')
                            ->label('Show Top')
                            ->options([
                                10 => 'Top 10',
                                20 => 'Top 20',
                                50 => 'Top 50',
                                'all' => 'All Products',
                                'custom' => 'Custom...',
                            ])
                            ->default(20)
                            ->live(),
                        \Filament\Forms\Components\TextInput::make('limit_custom')
                            ->label('Custom Limit')
                            ->numeric()
                            ->minValue(1)
                            ->default(100)
                            ->live(debounce: 500)
                            ->visible(fn (callable $get) => $get('limit_preset') === 'custom'),
                        Select::make('department_id')
                            ->label('Department')
                            ->options(function () {
                                $branchId = Filament::getTenant()?->id ?? ($this->data['branch_id'] ?? null);
                                return \App\Models\Department::query()
                                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live(),
                    ])
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function getData(): array
    {
        $tenant   = Filament::getTenant();
        $branchId = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);
        $from     = $this->data['date_from'] ?? null;
        $to       = $this->data['date_to'] ?? null;
        $sortBy   = $this->data['sort_by'] ?? 'revenue';
        $departmentId = $this->data['department_id'] ?? null;
        $limitPreset = $this->data['limit_preset'] ?? 20;
        if ($limitPreset === 'all') {
            $limit = 1000000;
        } elseif ($limitPreset === 'custom') {
            $limit = (int) ($this->data['limit_custom'] ?? 100);
        } else {
            $limit = (int) $limitPreset;
        }

        $orderCol = match ($sortBy) {
            'qty'    => 'total_qty',
            'profit' => 'total_profit',
            'orders' => 'order_count',
            default  => 'total_revenue',
        };

        $products = \App\Models\SalesOrderLine::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->when($branchId, fn ($q) => $q->where('sales_orders.branch_id', $branchId))
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
            ->when($departmentId, fn ($q) => $q->where('sales_orders.department_id', $departmentId))
            ->whereNull('sales_orders.deleted_at')
            ->whereNull('sales_order_lines.deleted_at')
            ->select(
                'items.id as item_id',
                'items.name as item_name',
                'item_categories.name as category_name',
                DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                DB::raw('SUM(sales_order_lines.line_cost) as total_cost'),
                DB::raw('SUM(sales_order_lines.gross_profit) as total_profit'),
                DB::raw('AVG(sales_order_lines.unit_price) as avg_price'),
            )
            ->groupBy('items.id', 'items.name', 'item_categories.name')
            ->orderBy($orderCol, 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($row, $idx) {
                $row->rank = $idx + 1;
                $row->margin_pct = $row->total_revenue > 0
                    ? round(($row->total_profit / $row->total_revenue) * 100, 1)
                    : 0;
                return $row;
            });

        // Grand totals for share calculation
        $grandRevenue = $products->sum('total_revenue') ?: 1;

        $products = $products->map(function ($row) use ($grandRevenue) {
            $row->revenue_share = round(($row->total_revenue / $grandRevenue) * 100, 1);
            return $row;
        });

        return [
            'products'      => $products,
            'sort_by'       => $sortBy,
            'grand_revenue' => $products->sum('total_revenue'),
            'grand_qty'     => $products->sum('total_qty'),
            'grand_profit'  => $products->sum('total_profit'),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getData(),
            'pdfParams'  => $this->getPdfParams(),
        ];
    }
}
