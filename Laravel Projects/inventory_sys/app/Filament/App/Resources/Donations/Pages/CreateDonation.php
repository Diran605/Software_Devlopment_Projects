<?php

namespace App\Filament\App\Resources\Donations\Pages;

use App\Filament\App\Resources\Donations\DonationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateDonation extends CreateRecord
{
    protected static string $resource = DonationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['donation_number'] = app(\App\Services\NumberGeneratorService::class)
            ->generateDonationNumber($data['branch_id']);
        $data['donated_at'] ??= now();
        $data['created_by'] ??= auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $linesData = $data['lines'] ?? [];
            unset($data['lines']);

            $donation = \App\Models\Donation::create($data);

            foreach ($linesData as $line) {
                $donationLine = $donation->lines()->create([
                    'item_id' => $line['item_id'],
                    'batch_inventory_id' => $line['batch_inventory_id'],
                    'qty_donated' => $line['qty_donated'],
                    'unit_cost' => $line['unit_cost'],
                    'total_value' => $line['total_value'],
                    'notes' => $line['notes'] ?? null,
                ]);

                // Deduct from clearance stock
                $clearanceStock = \App\Models\ClearanceStock::findOrFail($line['clearance_stock_id']);
                if ($clearanceStock->qty_remaining < $line['qty_donated']) {
                    throw new \Exception("Insufficient quantity in clearance stock: remaining {$clearanceStock->qty_remaining}, requested {$line['qty_donated']}");
                }
                $clearanceStock->qty_remaining -= $line['qty_donated'];
                $clearanceStock->save();

                // Post stock movement
                $stockMovementService = app(\App\Services\StockMovementService::class);
                $stockMovementService->record(
                    branchId: $donation->branch_id,
                    departmentId: $donation->department_id,
                    itemId: $line['item_id'],
                    batchInventoryId: $line['batch_inventory_id'],
                    recordedBy: auth()->id(),
                    movementType: 'donation',
                    qtyIn: 0,
                    qtyOut: $line['qty_donated'],
                    qtyBefore: null,
                    qtyAfter: null,
                    unitCost: $line['unit_cost'],
                    referenceType: \App\Models\DonationLine::class,
                    referenceId: $donationLine->id,
                    batchNumber: $clearanceStock->batch_number,
                    expiryDate: $clearanceStock->expiry_date,
                    notes: $line['notes'] ?? "Donated from clearance stock"
                );

                // Create clearance action
                \App\Models\ClearanceAction::create([
                    'branch_id' => $donation->branch_id,
                    'clearance_stocks_id' => $clearanceStock->id,
                    'item_id' => $line['item_id'],
                    'batch_inventory_id' => $line['batch_inventory_id'],
                    'action_type' => 'donate',
                    'qty' => $line['qty_donated'],
                    'loss_value' => $line['total_value'],
                    'donation_id' => $donation->id,
                    'notes' => $line['notes'] ?? "Donated from clearance stock"
                ]);
            }

            return $donation;
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
