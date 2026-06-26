<?php

namespace App\Filament\Imports;

use App\Models\OpeningStockEntry;
use App\Models\OpeningStockLine;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Number;

class OpeningStockImporter extends Importer
{
    protected static ?string $model = OpeningStockLine::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('item')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('batch_number')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('expiry_date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('qty_on_hand')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('unit_cost')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('branch')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('department')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('notes')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn () => null),
        ];
    }

    public function resolveRecord(): OpeningStockLine
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

        if (!$branchId) {
            throw new \Exception('A branch is required to import opening stock.');
        }

        // Resolve Department
        $departmentId = null;
        $deptName = $this->data['department'] ?? null;
        if ($deptName) {
            $dept = \App\Models\Department::where('name', $deptName)
                ->where('branch_id', $branchId)
                ->first();
            if ($dept) {
                $departmentId = $dept->id;
            }
        }

        // Resolve Item
        $itemName = $this->data['item'] ?? null;
        if (!$itemName) {
            throw new \Exception('Item name is required.');
        }
        $item = \App\Models\Item::where('name', $itemName)
            ->where('branch_id', $branchId)
            ->first();
        if (!$item) {
            throw new \Exception("Item '{$itemName}' not found in branch.");
        }

        // Find or create an OpeningStockEntry header for today
        $postedBy = auth()->id() ?? 1;
        $entry = OpeningStockEntry::firstOrCreate([
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'posted_by' => $postedBy,
            'posted_at' => now()->toDateString(),
        ], [
            'entry_number' => app(\App\Services\NumberGeneratorService::class)->generateEntryNumber($branchId),
            'notes' => $this->data['notes'] ?? 'Imported via CSV/Excel.',
        ]);

        $line = new OpeningStockLine();
        $line->opening_stock_entry_id = $entry->id;
        $line->item_id = $item->id;
        $line->is_consumed = false;

        return $line;
    }

    protected function afterSave(): void
    {
        $line = $this->record;
        $entry = $line->openingStockEntry;

        $batchInventoryService = app(\App\Services\BatchInventoryService::class);
        $inventoryService = app(\App\Services\InventoryService::class);
        $stockMovementService = app(\App\Services\StockMovementService::class);

        $batch = $batchInventoryService->createBatch(
            sourceType: OpeningStockLine::class,
            sourceId: $line->id,
            branchId: $entry->branch_id,
            departmentId: $entry->department_id,
            itemId: $line->item_id,
            batchNumber: $line->batch_number,
            expiryDate: $line->expiry_date,
            qtyReceived: $line->qty_on_hand,
            unitCost: $line->unit_cost,
        );

        $inventoryService->updateStockLevel(
            branchId: $entry->branch_id,
            departmentId: $entry->department_id,
            itemId: $line->item_id,
            qtyChange: $line->qty_on_hand,
            unitCost: $line->unit_cost,
        );

        $stockMovementService->record(
            branchId: $entry->branch_id,
            departmentId: $entry->department_id,
            itemId: $line->item_id,
            batchInventoryId: $batch->id,
            recordedBy: $entry->posted_by,
            movementType: 'opening_stock',
            qtyIn: $line->qty_on_hand,
            qtyOut: 0,
            qtyBefore: 0,
            qtyAfter: $line->qty_on_hand,
            unitCost: $line->unit_cost,
            referenceType: OpeningStockEntry::class,
            referenceId: $entry->id,
            batchNumber: $line->batch_number,
            expiryDate: $line->expiry_date,
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your opening stock import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
