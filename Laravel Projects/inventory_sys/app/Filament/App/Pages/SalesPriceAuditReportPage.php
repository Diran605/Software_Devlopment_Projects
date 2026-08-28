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

class SalesPriceAuditReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.sales-price-audit-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-magnifying-glass-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 12;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Sales Price Audit';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id'     => Filament::getTenant()?->id,
            'date_from'     => now()->startOfMonth()->format('Y-m-d'),
            'date_to'       => now()->format('Y-m-d'),
            'department_id' => null,
            'category_id'   => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $tenantId = Filament::getTenant()?->id;

        return $form
            ->schema([
                Grid::make($tenantId ? 4 : 5)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(! $tenantId),

                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->live(),

                        DatePicker::make('date_to')
                            ->label('To Date')
                            ->live(),

                        Select::make('department_id')
                            ->label('Department')
                            ->options(function () use ($tenantId) {
                                $branchId = $tenantId ?? ($this->data['branch_id'] ?? null);
                                return \App\Models\Department::query()
                                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->nullable()
                            ->searchable()
                            ->live(),

                        Select::make('category_id')
                            ->label('Category')
                            ->options(function () use ($tenantId) {
                                $branchId = $tenantId ?? ($this->data['branch_id'] ?? null);
                                return \App\Models\ItemCategory::query()
                                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->nullable()
                            ->searchable()
                            ->live(),
                    ])
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function getData(): \Illuminate\Support\Collection
    {
        $tenant   = Filament::getTenant();
        $branchId = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);
        $from     = $this->data['date_from'] ?? null;
        $to       = $this->data['date_to'] ?? null;
        $deptId   = $this->data['department_id'] ?? null;
        $catId    = $this->data['category_id'] ?? null;

        // Main product summary (one row per product)
        $products = DB::table('sales_order_lines')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->whereNull('sales_orders.deleted_at')
            ->whereNull('sales_order_lines.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sales_orders.branch_id', $branchId))
            ->when($deptId,   fn ($q) => $q->where('sales_orders.department_id', $deptId))
            ->when($catId,    fn ($q) => $q->where('items.category_id', $catId))
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
            ->select([
                'sales_order_lines.item_id',
                'items.name as item_name',
                'item_categories.name as category_name',
                'items.selling_price as standard_price',
                DB::raw('SUM(sales_order_lines.qty_sold) as total_qty_sold'),
                DB::raw('SUM(sales_order_lines.line_total) as actual_revenue'),
                DB::raw('ROUND(SUM(sales_order_lines.qty_sold * items.selling_price), 2) as expected_revenue'),
            ])
            ->groupBy(
                'sales_order_lines.item_id',
                'items.name',
                'item_categories.name',
                'items.selling_price'
            )
            ->orderBy('items.name')
            ->get();

        foreach ($products as $product) {
            $currentQty = DB::table('item_stock_levels')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->sum('qty_on_hand');

            // Net movements AFTER the end date — roll back to end-of-period qty
            $netAfterEnd = (float) DB::table('stock_movements')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($to,   fn ($q) => $q->whereDate('moved_at', '>', $to))
                ->sum(DB::raw('CAST(qty_in AS SIGNED) - CAST(qty_out AS SIGNED)'));

            $qtyAtEnd = $currentQty - $netAfterEnd;

            // Net movements WITHIN the range — roll back further to start-of-period qty
            $netInRange = (float) DB::table('stock_movements')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($from, fn ($q) => $q->whereDate('moved_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('moved_at', '<=', $to))
                ->sum(DB::raw('CAST(qty_in AS SIGNED) - CAST(qty_out AS SIGNED)'));

            $inwardsDuringPeriod = (float) DB::table('stock_movements')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($from, fn ($q) => $q->whereDate('moved_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('moved_at', '<=', $to))
                ->sum('qty_in');

            $startStock = max(0, round($qtyAtEnd - $netInRange));
            
            $product->qty_available = $startStock + round($inwardsDuringPeriod);
            $product->qty_remaining = max(0, round($qtyAtEnd));
            $product->discrepancy   = round($product->actual_revenue - $product->expected_revenue, 2);

            // Transaction-level drill-down
            $product->transactions = DB::table('sales_order_lines')
                ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
                ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
                ->leftJoin('users', 'users.id', '=', 'sales_orders.served_by')
                ->whereNull('sales_orders.deleted_at')
                ->whereNull('sales_order_lines.deleted_at')
                ->where('sales_order_lines.item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('sales_orders.branch_id', $branchId))
                ->when($deptId,   fn ($q) => $q->where('sales_orders.department_id', $deptId))
                ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
                ->select([
                    'sales_orders.sold_at',
                    'sales_orders.order_number',
                    'users.name as cashier',
                    'sales_order_lines.qty_sold',
                    'sales_order_lines.unit_price as used_price',
                    'items.selling_price as standard_price',
                    'sales_order_lines.line_total as actual_line_total',
                    DB::raw('ROUND(sales_order_lines.qty_sold * items.selling_price, 2) as expected_line_total'),
                    DB::raw('ROUND(sales_order_lines.line_total - (sales_order_lines.qty_sold * items.selling_price), 2) as line_discrepancy'),
                ])
                ->orderBy('sales_orders.sold_at', 'desc')
                ->get();
        }

        return $products;
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getData(),
            'pdfParams'  => $this->getPdfParams(),
            'dateFrom'   => $this->data['date_from'] ?? null,
            'dateTo'     => $this->data['date_to'] ?? null,
        ];
    }
}
