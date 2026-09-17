<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use App\Models\Branch;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'sm' => 3, 'xl' => 6])
                    ->schema([
                        Select::make('branch_id')
                            ->label('Filter by Branch')
                            ->options(Branch::pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->live()
                            ->columnSpan(['default' => 1, 'sm' => 1, 'xl' => 1]),
                        ToggleButtons::make('period')
                            ->label('Select Period')
                            ->inline()
                            ->options([
                                'today' => 'Today',
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'custom' => 'Custom Range',
                            ])
                            ->default('today')
                            ->live()
                            ->colors([
                                'today' => 'primary',
                                'week' => 'success',
                                'month' => 'info',
                                'custom' => 'warning',
                            ])
                            ->columnSpan(['default' => 1, 'sm' => 2, 'xl' => 3]),
                        DatePicker::make('from')
                            ->label('From Date')
                            ->visible(fn ($get) => $get('period') === 'custom')
                            ->required(fn ($get) => $get('period') === 'custom')
                            ->columnSpan(['default' => 1, 'sm' => 1, 'xl' => 1]),
                        DatePicker::make('to')
                            ->label('To Date')
                            ->visible(fn ($get) => $get('period') === 'custom')
                            ->required(fn ($get) => $get('period') === 'custom')
                            ->columnSpan(['default' => 1, 'sm' => 1, 'xl' => 1]),
                    ]),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\SystemStatsOverview::class,
            \App\Filament\App\Widgets\SalesTrendWidget::class,
            \App\Filament\App\Widgets\SalesQuarterlyTrendWidget::class,
            \App\Filament\App\Widgets\TopSellingItemsWidget::class,
            \App\Filament\Admin\Widgets\BranchPerformanceTable::class,
            \App\Filament\Admin\Widgets\LowStockAlert::class,
            \App\Filament\Admin\Widgets\NearExpiryAlert::class,
            \App\Filament\Admin\Widgets\RecentAuditActivityTable::class,
        ];
    }
}

