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
        $from       = $this->data['date_from'] ?? now()->startOfMonth()->format('Y-m-d');
        $to         = $this->data['date_to']   ?? now()->endOfMonth()->format('Y-m-d');
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
            ->whereDate('moved_at', '<', $from)
            ->groupBy('item_id')
            ->pluck('net_qty', 'item_id');

        // New stock IN during period: goods_receipt + opening_stock recorded during period
        $newStockRows = DB::table('stock_movements')
            ->select('item_id', DB::raw('SUM(qty_in) as qty_in'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->whereIn('movement_type', ['goods_receipt', 'opening_stock'])
            ->whereDate('moved_at', '>=', $from)
            ->whereDate('moved_at', '<=', $to)
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
            ->whereDate('moved_at', '>=', $from)
            ->whereDate('moved_at', '<=', $to)
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        // Build rows
        $rows = [];
        $totalOpeningStock   = 0;
        $totalNewStock       = 0;
        $totalStockAvailable = 0;
        $totalClosingStock   = 0;
        $totalQtySold        = 0;
        $totalRevenue        = 0;
        $totalCost           = 0;
        $totalProfit         = 0;

        foreach ($items as $item) {
            $openingStock   = max(0, (float) ($openingRows[$item->id] ?? 0));
            $newStock       = (float) ($newStockRows[$item->id] ?? 0);
            $totalStock     = $openingStock + $newStock;

            $saleRow        = $soldRows[$item->id] ?? null;
            $qtySold        = $saleRow ? (float) $saleRow->qty_out        : 0;
            $revenue        = $saleRow ? (float) $saleRow->total_revenue  : 0;
            $cost           = $saleRow ? (float) $saleRow->total_cost     : 0;
            $profit         = $revenue - $cost;

            $closingStock   = max(0, $totalStock - $qtySold);

            // Only include items that had ANY activity during the period
            if ($qtySold == 0 && $newStock == 0 && $openingStock == 0) {
                continue;
            }

            $rows[] = (object) [
                'item_name'      => $item->name,
                'category_name'  => $item->category_name ?? '—',
                'selling_price'  => $item->selling_price,
                'unit_cost'      => $item->unit_cost,
                'opening_stock'  => $openingStock,
                'new_stock'      => $newStock,
                'total_stock'    => $totalStock,
                'closing_stock'  => $closingStock,
                'qty_sold'       => $qtySold,
                'revenue'        => $revenue,
                'cost'           => $cost,
                'profit'         => $profit,
                'margin_pct'     => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];

            $totalOpeningStock   += $openingStock;
            $totalNewStock       += $newStock;
            $totalStockAvailable += $totalStock;
            $totalClosingStock   += $closingStock;
            $totalQtySold        += $qtySold;
            $totalRevenue        += $revenue;
            $totalCost           += $cost;
            $totalProfit         += $profit;
        }

        return [
            'rows'                 => collect($rows),
            'date_from'            => $from,
            'date_to'              => $to,
            'total_opening_stock'  => $totalOpeningStock,
            'total_new_stock'      => $totalNewStock,
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
