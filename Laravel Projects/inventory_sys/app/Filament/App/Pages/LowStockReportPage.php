<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ProvidesReportPdfParams;
use App\Models\ItemStockLevel;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class LowStockReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.low-stock-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-exclamation-triangle';
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
        return 'Low Stock Report';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => Filament::getTenant()?->id,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(1)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(! Filament::getTenant()),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function getReportData()
    {
        $branchId = Filament::getTenant()?->id ?? ($this->data['branch_id'] ?? null);

        return ItemStockLevel::query()
            ->join('items', 'items.id', '=', 'item_stock_levels.item_id')
            ->where('item_stock_levels.branch_id', $branchId)
            ->whereNotNull('item_stock_levels.department_id')
            ->where('items.reorder_level', '>', 0)
            ->whereColumn('item_stock_levels.qty_on_hand', '<=', 'items.reorder_level')
            ->select('item_stock_levels.*')
            ->with(['item', 'item.category'])
            ->orderBy('item_stock_levels.qty_on_hand')
            ->get();
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getReportData(),
            'pdfParams' => array_filter([
                'branch_id' => $this->data['branch_id'] ?? Filament::getTenant()?->id,
            ]),
        ];
    }
}
