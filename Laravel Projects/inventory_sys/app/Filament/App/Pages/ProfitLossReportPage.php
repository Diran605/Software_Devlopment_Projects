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

class ProfitLossReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.profit-loss-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Profit & Loss';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => Filament::getTenant()?->id,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to'   => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(Filament::getTenant() ? 2 : 3)
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
        $tenant = Filament::getTenant();
        $tenantId = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);

        $from = $this->data['date_from'] ?? null;
        $to   = $this->data['date_to'] ?? null;

        // Revenue & COGS from sales
        $salesAgg = \App\Models\SalesOrderLine::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->when($tenantId, fn ($q) => $q->where('sales_orders.branch_id', $tenantId))
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
            ->select(
                DB::raw('COALESCE(SUM(sales_order_lines.line_total), 0) as revenue'),
                DB::raw('COALESCE(SUM(sales_order_lines.line_cost), 0) as cogs'),
                DB::raw('COALESCE(SUM(sales_order_lines.gross_profit), 0) as gross_profit'),
                DB::raw('COUNT(DISTINCT sales_orders.id) as order_count')
            )
            ->first();

        $revenue     = (float) ($salesAgg->revenue ?? 0);
        $cogs        = (float) ($salesAgg->cogs ?? 0);
        $grossProfit = (float) ($salesAgg->gross_profit ?? 0);
        $orderCount  = (int)   ($salesAgg->order_count ?? 0);

        // Expenses
        $totalExpenses = (float) \App\Models\Expense::query()
            ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('expense_date', '<=', $to))
            ->sum('amount');

        $netProfit = $grossProfit - $totalExpenses;

        // Expense breakdown by category
        $expenseBreakdown = \App\Models\Expense::query()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->when($tenantId, fn ($q) => $q->where('expenses.branch_id', $tenantId))
            ->when($from, fn ($q) => $q->whereDate('expenses.expense_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('expenses.expense_date', '<=', $to))
            ->select(
                DB::raw('COALESCE(expense_categories.name, "Uncategorized") as category_name'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('COUNT(*) as expense_count')
            )
            ->groupBy('expense_categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Daily revenue trend
        $dailyRevenue = \App\Models\SalesOrderLine::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->when($tenantId, fn ($q) => $q->where('sales_orders.branch_id', $tenantId))
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
            ->select(
                DB::raw('DATE(sales_orders.sold_at) as date'),
                DB::raw('SUM(sales_order_lines.line_total) as amount')
            )
            ->groupBy(DB::raw('DATE(sales_orders.sold_at)'))
            ->orderBy('date')
            ->get();

        return [
            'revenue'           => $revenue,
            'cogs'              => $cogs,
            'gross_profit'      => $grossProfit,
            'gross_margin'      => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0,
            'total_expenses'    => $totalExpenses,
            'net_profit'        => $netProfit,
            'net_margin'        => $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0,
            'order_count'       => $orderCount,
            'expense_breakdown' => $expenseBreakdown,
            'daily_revenue'     => $dailyRevenue,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getData(),
            'pdfParams' => $this->getPdfParams(),
        ];
    }
}
