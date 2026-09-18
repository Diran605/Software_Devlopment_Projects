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

class DetailedSalesReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.detailed-sales-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Detailed Sales Report';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id'     => Filament::getTenant()?->id,
            'date_from'     => now()->startOfMonth()->format('Y-m-d'),
            'date_to'       => now()->endOfMonth()->format('Y-m-d'),
            'category_id'   => null,
            'department_id' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(Filament::getTenant() ? 4 : 5)
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

    protected function getReportData(): array
    {
        $tenant     = Filament::getTenant();
        $branchId   = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);
        
        $rawFrom    = !empty($this->data['date_from']) ? $this->data['date_from'] : now()->startOfMonth()->format('Y-m-d');
        $rawTo      = !empty($this->data['date_to'])   ? $this->data['date_to']   : now()->endOfMonth()->format('Y-m-d');
        
        $from       = \Carbon\Carbon::parse($rawFrom)->startOfDay();
        $to         = \Carbon\Carbon::parse($rawTo)->endOfDay();
        
        $categoryId = $this->data['category_id'] ?? null;
        $departmentId = $this->data['department_id'] ?? null;

        // Base item query
        $itemsQuery = DB::table('items')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->select('items.id', 'items.name', 'items.unit_cost', 'items.selling_price',
                     'item_categories.name as category_name')
            ->where('items.is_active', true)
            ->when($categoryId, fn($q) => $q->where('items.category_id', $categoryId))
            ->orderBy('items.name');

        $items = $itemsQuery->get();

        // Build movement data in bulk (one query per metric for performance)
        // Opening stock: qty_after from the last movement BEFORE the period for each item
        $openingRows = DB::table('stock_movements')
            ->select('item_id', DB::raw('SUM(qty_in) - SUM(qty_out) as net_qty'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->where('moved_at', '<', $from)
            ->groupBy('item_id')
            ->pluck('net_qty', 'item_id');

        // New stock IN during period: goods_receipt + opening_stock recorded during period
        $newStockRows = DB::table('stock_movements')
            ->select('item_id', DB::raw('SUM(qty_in) as qty_in'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->whereIn('movement_type', ['goods_receipt', 'opening_stock'])
            ->where('moved_at', '>=', $from)
            ->where('moved_at', '<=', $to)
            ->groupBy('item_id')
            ->pluck('qty_in', 'item_id');

        // Qty sold during period: sale + clearance_sale
        $soldRows = DB::table('stock_movements')
            ->select('item_id',
                     DB::raw('SUM(qty_out) as qty_out'),
                     DB::raw('SUM(qty_out * unit_cost) as total_cost'),
                     DB::raw('SUM(qty_out * unit_price) as total_revenue'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->whereIn('movement_type', ['sale', 'clearance_sale'])
            ->where('moved_at', '>=', $from)
            ->where('moved_at', '<=', $to)
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        // Other Adjustments (Counts, Disposals, Transfers)
        $adjRows = DB::table('stock_movements')
            ->select('item_id', DB::raw('SUM(qty_in) - SUM(qty_out) as net_adj'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->whereNotIn('movement_type', ['goods_receipt', 'opening_stock', 'sale', 'clearance_sale'])
            ->where('moved_at', '>=', $from)
            ->where('moved_at', '<=', $to)
            ->groupBy('item_id')
            ->pluck('net_adj', 'item_id');

        // Build rows
        $rows = [];
        $totalOpeningStock   = 0;
        $totalNewStock       = 0;
        $totalAdjStock       = 0;
        $totalStockAvailable = 0;
        $totalClosingStock   = 0;
        $totalQtySold        = 0;
        $totalRevenue        = 0;
        $totalCost           = 0;
        $totalProfit         = 0;

        foreach ($items as $item) {
            $opening = $openingRows[$item->id] ?? 0;
            $newIn   = $newStockRows[$item->id] ?? 0;
            $adj     = $adjRows[$item->id] ?? 0;
            $available = $opening + $newIn + $adj;
            
            $saleRow = $soldRows[$item->id] ?? null;
            $qtySold = $saleRow ? $saleRow->qty_out : 0;
            $revenue = $saleRow ? $saleRow->total_revenue : 0;
            $cost    = $saleRow ? $saleRow->total_cost : 0;
            
            $closing = $available - $qtySold;

            // Skip items with zero activity in this period if they also have zero stock
            if ($opening == 0 && $newIn == 0 && $qtySold == 0 && $closing == 0 && $adj == 0) {
                continue;
            }

            $profit = $revenue - $cost;
            $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

            $rows[] = (object)[
                'item_name'     => $item->name,
                'category_name' => $item->category_name ?? '—',
                'opening_stock' => $opening,
                'new_stock'     => $newIn,
                'adjustments'   => $adj,
                'total_stock'   => $available,
                'qty_sold'      => $qtySold,
                'closing_stock' => $closing,
                'unit_cost'     => $item->unit_cost,
                'selling_price' => $item->selling_price,
                'revenue'       => $revenue,
                'cost'          => $cost,
                'profit'        => $profit,
                'margin_pct'    => $margin,
            ];

            $totalOpeningStock   += $opening;
            $totalNewStock       += $newIn;
            $totalAdjStock       += $adj;
            $totalStockAvailable += $available;
            $totalClosingStock   += $closing;
            $totalQtySold        += $qtySold;
            $totalRevenue        += $revenue;
            $totalCost           += $cost;
            $totalProfit         += $profit;
        }

        return [
            'rows'                 => collect($rows),
            'date_from'            => $from->format('Y-m-d'),
            'date_to'              => $to->format('Y-m-d'),
            'total_opening_stock'  => $totalOpeningStock,
            'total_new_stock'      => $totalNewStock,
            'total_adj_stock'      => $totalAdjStock,
            'total_stock'          => $totalStockAvailable,
            'total_closing_stock'  => $totalClosingStock,
            'total_qty_sold'       => $totalQtySold,
            'total_revenue'        => $totalRevenue,
            'total_cost'           => $totalCost,
            'total_profit'         => $totalProfit,
            'overall_margin'       => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getReportData(),
            'pdfParams'  => $this->getPdfParams(),
        ];
    }
}
