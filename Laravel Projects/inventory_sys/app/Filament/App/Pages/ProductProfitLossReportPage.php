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

class ProductProfitLossReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.product-profit-loss-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-scale';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Product P&L';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id'     => Filament::getTenant()?->id,
            'date_from'     => now()->startOfMonth()->format('Y-m-d'),
            'date_to'       => now()->endOfMonth()->format('Y-m-d'),
            'category_id'   => null,
            'filter'        => 'all',
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
                        Select::make('category_id')
                            ->label('Category')
                            ->options(\App\Models\ItemCategory::orderBy('name')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('All Categories'),
                        Select::make('filter')
                            ->label('Show')
                            ->options([
                                'all'      => 'All Products',
                                'profit'   => 'Profitable Only',
                                'loss'     => 'Loss-Making Only',
                                'negative' => 'Negative Margin Only',
                            ])
                            ->default('all')
                            ->live(),
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
        $tenant     = Filament::getTenant();
        $branchId   = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);
        $from       = $this->data['date_from'] ?? null;
        $to         = $this->data['date_to'] ?? null;
        $categoryId = $this->data['category_id'] ?? null;
        $filter     = $this->data['filter'] ?? 'all';
        $departmentId = $this->data['department_id'] ?? null;

        $query = \App\Models\SalesOrderLine::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->when($branchId, fn ($q) => $q->where('sales_orders.branch_id', $branchId))
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
            ->when($categoryId, fn ($q) => $q->where('items.category_id', $categoryId))
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
            )
            ->groupBy('items.id', 'items.name', 'item_categories.name')
            ->orderBy('total_profit', 'desc');

        $rows = $query->get()->map(function ($row) {
            $row->margin_pct = $row->total_revenue > 0
                ? round(($row->total_profit / $row->total_revenue) * 100, 1)
                : 0;
            $row->is_loss = $row->total_profit < 0;
            $row->is_low_margin = !$row->is_loss && $row->margin_pct < 20;
            return $row;
        });

        // Apply profit/loss filter
        $rows = match ($filter) {
            'profit'   => $rows->filter(fn ($r) => $r->total_profit > 0)->values(),
            'loss'     => $rows->filter(fn ($r) => $r->total_profit < 0)->values(),
            'negative' => $rows->filter(fn ($r) => $r->margin_pct < 0)->values(),
            default    => $rows,
        };

        $totalRevenue = $rows->sum('total_revenue');
        $totalCost    = $rows->sum('total_cost');
        $totalProfit  = $rows->sum('total_profit');
        $profitableCount = $rows->where('total_profit', '>', 0)->count();
        $lossCount       = $rows->where('total_profit', '<', 0)->count();

        return [
            'rows'             => $rows,
            'total_revenue'    => $totalRevenue,
            'total_cost'       => $totalCost,
            'total_profit'     => $totalProfit,
            'overall_margin'   => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0,
            'profitable_count' => $profitableCount,
            'loss_count'       => $lossCount,
            'filter'           => $filter,
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
