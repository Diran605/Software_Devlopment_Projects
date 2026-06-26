<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use App\Filament\Concerns\ProvidesReportPdfParams;
use App\Models\BatchInventory;
use App\Models\ItemCategory;

class ExpiryReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.expiry-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Expiry Report';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => Filament::getTenant()?->id,
            'days_threshold' => 90,
            'category_id' => null,
            'urgency_band' => 'all',
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
                        TextInput::make('days_threshold')
                            ->label('Days to Expiry Threshold')
                            ->numeric()
                            ->default(90)
                            ->live()
                            ->required(),
                        Select::make('category_id')
                            ->label('Item Category')
                            ->options(ItemCategory::pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('urgency_band')
                            ->label('Urgency Band')
                            ->options([
                                'all' => 'All Bands',
                                'expired' => 'Expired (<= 0 days)',
                                'critical' => 'Critical (1 - 6 days)',
                                'urgent' => 'Urgent (7 - 14 days)',
                                'approaching' => 'Approaching (15 - 30 days)',
                                'safe' => 'Safe (> 30 days)',
                            ])
                            ->live()
                            ->required(),
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

        $daysThreshold = intval($this->data['days_threshold'] ?? 90);
        $categoryId = $this->data['category_id'] ?? null;
        $urgencyBand = $this->data['urgency_band'] ?? 'all';

        $query = BatchInventory::query()
            ->with(['item.category'])
            ->whereNotNull('expiry_date')
            ->where('qty_remaining', '>', 0)
            ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->whereHas('item', fn ($qi) => $qi->where('category_id', $categoryId));
            });

        // Calculate days to expiry and filter
        $batches = $query->get()->map(function ($batch) {
            $daysToExpiry = now()->diffInDays($batch->expiry_date, false);
            $batch->days_to_expiry = $daysToExpiry;

            if ($daysToExpiry <= 0) {
                $batch->urgency_band = 'expired';
                $batch->urgency_label = 'Expired';
                $batch->urgency_color = 'danger';
            } elseif ($daysToExpiry <= 6) {
                $batch->urgency_band = 'critical';
                $batch->urgency_label = 'Critical';
                $batch->urgency_color = 'danger';
            } elseif ($daysToExpiry <= 14) {
                $batch->urgency_band = 'urgent';
                $batch->urgency_label = 'Urgent';
                $batch->urgency_color = 'warning';
            } elseif ($daysToExpiry <= 30) {
                $batch->urgency_band = 'approaching';
                $batch->urgency_label = 'Approaching';
                $batch->urgency_color = 'info';
            } else {
                $batch->urgency_band = 'safe';
                $batch->urgency_label = 'Safe';
                $batch->urgency_color = 'success';
            }

            $batch->total_cost = $batch->qty_remaining * $batch->unit_cost;

            return $batch;
        });

        // Filter by days threshold and urgency band
        return $batches->filter(function ($batch) use ($daysThreshold, $urgencyBand) {
            // Filter by maximum days threshold
            if ($batch->days_to_expiry > $daysThreshold) {
                return false;
            }

            // Filter by urgency band
            if ($urgencyBand !== 'all' && $batch->urgency_band !== $urgencyBand) {
                return false;
            }

            return true;
        })->sortBy('days_to_expiry');
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getData(),
            'pdfParams' => $this->getPdfParams(),
        ];
    }
}
