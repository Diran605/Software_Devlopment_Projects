<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 4])
                    ->schema([
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
                            ->columnSpan(['default' => 1, 'sm' => 2, 'xl' => 2]),
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
            \App\Filament\App\Widgets\BranchStatsOverview::class,
            \App\Filament\App\Widgets\StockSummaryWidget::class,
            \App\Filament\App\Widgets\SalesTrendWidget::class,
            \App\Filament\App\Widgets\SalesQuarterlyTrendWidget::class,
            \App\Filament\App\Widgets\NearExpiryAlert::class,
            \App\Filament\App\Widgets\TopSellingItemsWidget::class,
        ];
    }
}
