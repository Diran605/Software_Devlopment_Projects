<?php

namespace App\Filament\App\Resources\Disposals\Pages;

use App\Filament\App\Resources\Disposals\DisposalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateDisposal extends CreateRecord
{
    protected static string $resource = DisposalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['disposal_number'] = app(\App\Services\NumberGeneratorService::class)
            ->generateDisposalNumber($data['branch_id']);
        $data['disposed_at'] ??= now();
        $data['created_by'] ??= auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $linesData = $data['lines'] ?? [];
            unset($data['lines']);

            $disposal = \App\Models\Disposal::create($data);

            foreach ($linesData as $line) {
                $disposalLine = $disposal->lines()->create([
                    'item_id' => $line['item_id'],
                    'batch_inventory_id' => $line['batch_inventory_id'],
                    'qty_disposed' => $line['qty_disposed'],
                    'unit_cost' => $line['unit_cost'],
                    'total_value' => $line['total_value'],
                    'notes' => $line['notes'] ?? null,
                ]);

                // Deduct from clearance stock
                $clearanceStock = \App\Models\ClearanceStock::findOrFail($line['clearance_stock_id']);
                if ($clearanceStock->qty_remaining < $line['qty_disposed']) {
                    throw new \Exception("Insufficient quantity in clearance stock: remaining {$clearanceStock->qty_remaining}, requested {$line['qty_disposed']}");
                }
                $clearanceStock->qty_remaining -= $line['qty_disposed'];
                $clearanceStock->save();

                // Post stock movement
                $stockMovementService = app(\App\Services\StockMovementService::class);
                $stockMovementService->record(
                    branchId: $disposal->branch_id,
                    departmentId: $disposal->department_id,
                    itemId: $line['item_id'],
                    batchInventoryId: $line['batch_inventory_id'],
                    recordedBy: auth()->id(),
                    movementType: 'disposal',
                    qtyIn: 0,
                    qtyOut: $line['qty_disposed'],
                    qtyBefore: null,
                    qtyAfter: null,
                    unitCost: $line['unit_cost'],
                    referenceType: \App\Models\DisposalLine::class,
                    referenceId: $disposalLine->id,
                    batchNumber: $clearanceStock->batch_number,
                    expiryDate: $clearanceStock->expiry_date,
                    notes: $line['notes'] ?? "Disposed from clearance stock"
                );

                // Create clearance action
                \App\Models\ClearanceAction::create([
                    'branch_id' => $disposal->branch_id,
                    'clearance_stocks_id' => $clearanceStock->id,
                    'item_id' => $line['item_id'],
                    'batch_inventory_id' => $line['batch_inventory_id'],
                    'action_type' => 'dispose',
                    'qty' => $line['qty_disposed'],
                    'loss_value' => $line['total_value'],
                    'disposal_id' => $disposal->id,
                    'notes' => $line['notes'] ?? "Disposed from clearance stock"
                ]);
            }

            return $disposal;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
