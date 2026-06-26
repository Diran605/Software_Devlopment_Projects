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
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ToggleButtons::make('period')
                                    ->label('Select Period')
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
                                    ]),
                                DatePicker::make('from')
                                    ->label('From Date')
                                    ->visible(fn ($get) => $get('period') === 'custom')
                                    ->required(fn ($get) => $get('period') === 'custom'),
                                DatePicker::make('to')
                                    ->label('To Date')
                                    ->visible(fn ($get) => $get('period') === 'custom')
                                    ->required(fn ($get) => $get('period') === 'custom'),
                            ]),
                    ]),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\App\Widgets\BranchStatsOverview::class,
            \App\Filament\App\Widgets\StockSummaryWidget::class,
            \App\Filament\App\Widgets\SalesTrendWidget::class,
            \App\Filament\App\Widgets\LowStockAlert::class,
            \App\Filament\App\Widgets\NearExpiryAlert::class,
            \App\Filament\App\Widgets\TopSellingItemsWidget::class,
            \App\Filament\App\Widgets\RecentSalesTable::class,
        ];
    }
}
