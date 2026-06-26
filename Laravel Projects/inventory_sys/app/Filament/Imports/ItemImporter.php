<?php

namespace App\Filament\Imports;

use App\Models\Item;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Number;

class ItemImporter extends Importer
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description'),
            ImportColumn::make('category')
                ->label('Category Name')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('uom')
                ->label('UOM (Name or Abbreviation)')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('packaging_type')
                ->label('Packaging Type')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('unit_cost')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('min_selling_price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('selling_price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('reorder_level')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('reorder_quantity')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('is_packaged')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('branch')
                ->label('Branch')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn () => null),
        ];
    }

    public function resolveRecord(): Item
    {
        $branchId = Filament::getTenant()?->id;

        $branchName = $this->data['branch'] ?? null;
        if (!$branchId && $branchName) {
            $branch = \App\Models\Branch::where('name', $branchName)->first();
            if ($branch) {
                $branchId = $branch->id;
            }
        }

        if (!$branchId) {
            $branchId = auth()->user()?->branch_id;
        }

        $item = null;
        if ($branchId) {
            $item = Item::where('name', $this->data['name'])
                ->where('branch_id', $branchId)
                ->first();
        } else {
            $item = Item::where('name', $this->data['name'])->first();
        }

        if (!$item) {
            $item = new Item();
            $item->branch_id = $branchId;
        }

        // Resolve Category
        $categoryName = $this->data['category'] ?? null;
        if ($categoryName) {
            $category = \App\Models\ItemCategory::where('name', $categoryName)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->first();
            if ($category) {
                $item->category_id = $category->id;
            }
        }

        // Resolve UOM
        $uomName = $this->data['uom'] ?? null;
        if ($uomName) {
            $uom = \App\Models\UnitOfMeasure::where('name', $uomName)
                ->orWhere('abbreviation', $uomName)
                ->first();
            if ($uom) {
                $item->uom_id = $uom->id;
            }
        }

        // Resolve Packaging Type
        $packName = $this->data['packaging_type'] ?? null;
        if ($packName) {
            $pack = \App\Models\PackagingType::where('name', $packName)->first();
            if ($pack) {
                $item->packaging_type_id = $pack->id;
            }
        }

        return $item;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your item import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
