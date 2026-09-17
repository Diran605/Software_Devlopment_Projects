<?php

namespace App\Filament\Exports;

use App\Models\Item;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use Filament\Actions\Exports\Enums\ExportFormat;


class ItemExporter extends Exporter
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('branch.name')->label('Branch'),
            ExportColumn::make('category.name')->label('Category'),
            ExportColumn::make('uom.name')->label('UOM'),
            ExportColumn::make('packagingType.name')->label('Packaging Type'),
            ExportColumn::make('name'),
            ExportColumn::make('description'),
            ExportColumn::make('unit_cost'),
            ExportColumn::make('min_selling_price'),
            ExportColumn::make('selling_price'),
            ExportColumn::make('reorder_level'),
            ExportColumn::make('reorder_quantity'),
            ExportColumn::make('is_packaged'),
            ExportColumn::make('packaging_notes'),
            ExportColumn::make('is_active'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your item export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
            ExportFormat::Xlsx,
        ];
    }
}