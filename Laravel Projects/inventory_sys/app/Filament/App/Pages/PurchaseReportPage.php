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

class PurchaseReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.app.pages.reports.purchase-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Purchase Report';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id'   => null,
            'date_from'   => now()->startOfMonth()->format('Y-m-d'),
            'date_to'     => now()->endOfMonth()->format('Y-m-d'),
            'supplier_id' => null,
            'status'      => [],
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
                            ->options(\App\Models\Branch::orderBy('name')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(! $tenantId),

                        DatePicker::make('date_from')
                            ->label('Ordered From')
                            ->live(),

                        DatePicker::make('date_to')
                            ->label('Ordered To')
                            ->live(),

                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->options(function () use ($tenantId) {
                                $branchId = $tenantId ?? ($this->data['branch_id'] ?? null);
                                return \App\Models\Supplier::query()
                                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->nullable()
                            ->searchable()
                            ->live(),

                        Select::make('status')
                            ->label('Status')
                            ->multiple()
                            ->options([
                                'draft'             => 'Draft',
                                'issued'            => 'Issued',
                                'partially_received'=> 'Partially Received',
                                'fully_received'    => 'Fully Received',
                                'cancelled'         => 'Cancelled',
                            ])
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

    public function getData()
    {
        $tenant   = Filament::getTenant();
        $tenantId = $tenant ? $tenant->id : ($this->data['branch_id'] ?? null);

        $from       = $this->data['date_from'] ?? null;
        $to         = $this->data['date_to'] ?? null;
        $supplierId = $this->data['supplier_id'] ?? null;
        $statuses   = $this->data['status'] ?? [];

        return \App\Models\PurchaseOrder::query()
            ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->when($from, fn ($q) => $q->whereDate('ordered_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('ordered_at', '<=', $to))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when(!empty($statuses), fn ($q) => $q->whereIn('status', $statuses))
            ->with(['supplier', 'purchaseOrderLines', 'purchaseOrderLines.item'])
            ->orderBy('ordered_at', 'desc')
            ->get();
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getData(),
        ];
    }
}
