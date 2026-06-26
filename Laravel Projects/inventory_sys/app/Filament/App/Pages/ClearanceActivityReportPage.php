<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ProvidesReportPdfParams;
use App\Models\ClearanceAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ClearanceActivityReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.clearance-activity-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-tag';
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
        return 'Clearance Activity Report';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => Filament::getTenant()?->id,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'action_type' => null,
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
                            ->visible(! Filament::getTenant()),
                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->live(),
                        DatePicker::make('date_to')
                            ->label('To Date')
                            ->live(),
                        Select::make('action_type')
                            ->label('Action Type')
                            ->options([
                                'sell' => 'Sale',
                                'donate' => 'Donation',
                                'dispose' => 'Disposal',
                                'reverse' => 'Reversal',
                            ])
                            ->nullable()
                            ->live(),
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

        return ClearanceAction::query()
            ->where('branch_id', $branchId)
            ->when($this->data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($this->data['action_type'] ?? null, fn ($q, $v) => $q->where('action_type', $v))
            ->with(['item', 'clearanceStock', 'salesOrder', 'donation', 'disposal'])
            ->orderByDesc('created_at')
            ->get();
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getReportData(),
            'pdfParams' => $this->getPdfParams(),
        ];
    }
}
