<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ProvidesReportPdfParams;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Facades\Filament;

class StockValuationReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.stock-valuation-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cube';
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
        return 'Stock Valuation';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id'          => Filament::getTenant()?->id,
            'category_id'        => null,
            'department_id'      => null,
            'include_zero_stock' => false,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $tenantId = Filament::getTenant()?->id;

        return $form
            ->schema([
                Grid::make($tenantId ? 3 : 4)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::orderBy('name')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(! $tenantId),

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

                        Toggle::make('include_zero_stock')
                            ->label('Include Zero Stock')
                            ->default(false)
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
        $tenant   = Filament::getTenant();
        $tenantId = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);

        $categoryId  = $this->data['category_id'] ?? null;
        $deptId      = $this->data['department_id'] ?? null;
        $includeZero = $this->data['include_zero_stock'] ?? false;

        $query = \App\Models\ItemStockLevel::query()
            ->select('item_stock_levels.*')
            ->join('items', 'items.id', '=', 'item_stock_levels.item_id')
            ->when($tenantId,    fn ($q) => $q->where('item_stock_levels.branch_id', $tenantId))
            ->when($categoryId,  fn ($q) => $q->where('items.category_id', $categoryId))
            ->when($deptId,      fn ($q) => $q->where('item_stock_levels.department_id', $deptId))
            ->when(! $deptId,    fn ($q) => $q->whereNotNull('item_stock_levels.department_id'))
            ->when(! $includeZero, fn ($q) => $q->where('item_stock_levels.qty_on_hand', '>', 0))
            ->with(['item', 'item.category', 'item.uom']);

        $items = $query->get();

        foreach ($items as $itemLevel) {
            $itemLevel->batches = \App\Models\BatchInventory::query()
                ->where('item_id', $itemLevel->item_id)
                ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
                ->where('qty_remaining', '>', 0)
                ->when($deptId,   fn ($q) => $q->where('department_id', $deptId))
                ->when(! $deptId, fn ($q) => $q->whereNotNull('department_id'))
                ->get();
        }

        return $items;
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getData(),
            'pdfParams' => $this->getPdfParams(),
        ];
    }
}
