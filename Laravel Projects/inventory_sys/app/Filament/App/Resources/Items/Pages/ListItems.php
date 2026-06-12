<?php

namespace App\Filament\App\Resources\Items\Pages;

use App\Filament\App\Resources\Items\ItemResource;
use App\Filament\Imports\ItemImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Actions\XlsxAndCsvImportAction;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            XlsxAndCsvImportAction::make()
                ->importer(ItemImporter::class)
                ->label('Import Items'),

            Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('import-templates.items'))
                ->openUrlInNewTab(false),
        ];
    }
}
