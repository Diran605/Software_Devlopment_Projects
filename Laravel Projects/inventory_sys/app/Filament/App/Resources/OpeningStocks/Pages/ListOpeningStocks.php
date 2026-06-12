<?php

namespace App\Filament\App\Resources\OpeningStocks\Pages;

use App\Filament\App\Resources\OpeningStocks\OpeningStockResource;
use App\Filament\Imports\OpeningStockImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Actions\XlsxAndCsvImportAction;
use Filament\Resources\Pages\ListRecords;

class ListOpeningStocks extends ListRecords
{
    protected static string $resource = OpeningStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            XlsxAndCsvImportAction::make()
                ->importer(OpeningStockImporter::class)
                ->label('Import Opening Stock'),

            Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('import-templates.opening-stock'))
                ->openUrlInNewTab(false),
        ];
    }
}
