<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

class SalesReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.app.pages.reports.sales-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Sales Report';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => null,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'group_by' => 'date',
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(Filament::getTenant() ? 3 : 4)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::pluck('name', 'id'))
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
                        Select::make('group_by')
                            ->label('Group By')
                            ->options([
                                'date' => 'Date',
                                'item' => 'Item',
                                'customer' => 'Customer',
                                'cashier' => 'Cashier/User',
                            ])
                            ->required()
                            ->live(),
                    ])
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function getData()
    {
        $tenant = Filament::getTenant();
        $tenantId = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);

        $from = $this->data['date_from'] ?? null;
        $to = $this->data['date_to'] ?? null;
        $groupBy = $this->data['group_by'] ?? 'date';

        $query = \App\Models\SalesOrderLine::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->when($tenantId, fn ($q) => $q->where('sales_orders.branch_id', $tenantId))
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to));

        if ($groupBy === 'date') {
            return $query
                ->select(
                    DB::raw('DATE(sales_orders.sold_at) as label'),
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy(DB::raw('DATE(sales_orders.sold_at)'))
                ->orderBy('label', 'desc')
                ->get();
        } elseif ($groupBy === 'item') {
            return $query
                ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
                ->select(
                    'items.name as label',
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy('sales_order_lines.item_id', 'items.name')
                ->orderBy('total_revenue', 'desc')
                ->get();
        } elseif ($groupBy === 'customer') {
            return $query
                ->leftJoin('customers', 'customers.id', '=', 'sales_orders.customer_id')
                ->select(
                    DB::raw('COALESCE(customers.name, sales_orders.customer_name, "Walk-in Customer") as label'),
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy(DB::raw('COALESCE(customers.name, sales_orders.customer_name, "Walk-in Customer")'))
                ->orderBy('total_revenue', 'desc')
                ->get();
        } elseif ($groupBy === 'cashier') {
            return $query
                ->join('users', 'users.id', '=', 'sales_orders.served_by')
                ->select(
                    'users.name as label',
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy('sales_orders.served_by', 'users.name')
                ->orderBy('total_revenue', 'desc')
                ->get();
        }

        return collect();
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getData(),
        ];
    }
}
