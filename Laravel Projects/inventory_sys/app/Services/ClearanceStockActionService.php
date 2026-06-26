<?php

namespace App\Services;

use App\Models\ClearanceAction;
use App\Models\ClearanceStock;
use App\Models\Disposal;
use App\Models\DisposalLine;
use App\Models\Donation;
use App\Models\DonationLine;
use Illuminate\Support\Facades\DB;

class ClearanceStockActionService
{
    public function sell(ClearanceStock $stock, array $data): ClearanceAction
    {
        return DB::transaction(function () use ($stock, $data) {
            $qty = (int) $data['qty'];
            $this->assertQtyAvailable($stock, $qty);

            $lossValue = ($stock->original_price - $stock->clearance_price) * $qty;

            $action = ClearanceAction::create([
                'branch_id' => $stock->branch_id,
                'clearance_stocks_id' => $stock->id,
                'item_id' => $stock->item_id,
                'batch_inventory_id' => $stock->batch_inventory_id,
                'action_type' => 'sell',
                'qty' => $qty,
                'loss_value' => $lossValue,
                'notes' => $data['notes'] ?? null,
            ]);

            $stock->decrement('qty_remaining', $qty);
            $this->syncClearanceItemStatus($stock);

            return $action;
        });
    }

    public function donate(ClearanceStock $stock, array $data): Donation
    {
        return DB::transaction(function () use ($stock, $data) {
            $qty = (int) $data['qty'];
            $this->assertQtyAvailable($stock, $qty);

            $donation = Donation::create([
                'branch_id' => $stock->branch_id,
                'department_id' => $stock->department_id,
                'created_by' => auth()->id(),
                'donation_number' => app(NumberGeneratorService::class)->generateDonationNumber($stock->branch_id),
                'recipient' => $data['recipient'],
                'recipient_contact' => $data['recipient_contact'] ?? null,
                'recipient_address' => $data['recipient_address'] ?? null,
                'donated_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $donationLine = $donation->lines()->create([
                'item_id' => $stock->item_id,
                'batch_inventory_id' => $stock->batch_inventory_id,
                'qty_donated' => $qty,
                'unit_cost' => $stock->unit_cost,
                'total_value' => $stock->unit_cost * $qty,
                'notes' => $data['notes'] ?? null,
            ]);

            $stock->decrement('qty_remaining', $qty);

            app(StockMovementService::class)->record(
                branchId: $stock->branch_id,
                departmentId: $stock->department_id,
                itemId: $stock->item_id,
                batchInventoryId: $stock->batch_inventory_id,
                recordedBy: auth()->id(),
                movementType: 'donation',
                qtyIn: 0,
                qtyOut: $qty,
                qtyBefore: null,
                qtyAfter: null,
                unitCost: $stock->unit_cost,
                referenceType: DonationLine::class,
                referenceId: $donationLine->id,
                batchNumber: $stock->batch_number,
                expiryDate: $stock->expiry_date,
                notes: $data['notes'] ?? 'Donated from clearance stock'
            );

            ClearanceAction::create([
                'branch_id' => $stock->branch_id,
                'clearance_stocks_id' => $stock->id,
                'item_id' => $stock->item_id,
                'batch_inventory_id' => $stock->batch_inventory_id,
                'action_type' => 'donate',
                'qty' => $qty,
                'loss_value' => $donationLine->total_value,
                'donation_id' => $donation->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncClearanceItemStatus($stock);

            return $donation;
        });
    }

    public function dispose(ClearanceStock $stock, array $data): Disposal
    {
        return DB::transaction(function () use ($stock, $data) {
            $qty = (int) $data['qty'];
            $this->assertQtyAvailable($stock, $qty);

            $disposal = Disposal::create([
                'branch_id' => $stock->branch_id,
                'department_id' => $stock->department_id,
                'created_by' => auth()->id(),
                'disposal_number' => app(NumberGeneratorService::class)->generateDisposalNumber($stock->branch_id),
                'reason' => $this->mapDisposalReason($data['reason']),
                'disposal_method' => $data['disposal_method'] ?? null,
                'disposed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $disposalLine = $disposal->lines()->create([
                'item_id' => $stock->item_id,
                'batch_inventory_id' => $stock->batch_inventory_id,
                'qty_disposed' => $qty,
                'unit_cost' => $stock->unit_cost,
                'total_value' => $stock->unit_cost * $qty,
                'notes' => $data['notes'] ?? null,
            ]);

            $stock->decrement('qty_remaining', $qty);

            app(StockMovementService::class)->record(
                branchId: $stock->branch_id,
                departmentId: $stock->department_id,
                itemId: $stock->item_id,
                batchInventoryId: $stock->batch_inventory_id,
                recordedBy: auth()->id(),
                movementType: 'disposal',
                qtyIn: 0,
                qtyOut: $qty,
                qtyBefore: null,
                qtyAfter: null,
                unitCost: $stock->unit_cost,
                referenceType: DisposalLine::class,
                referenceId: $disposalLine->id,
                batchNumber: $stock->batch_number,
                expiryDate: $stock->expiry_date,
                notes: $data['notes'] ?? 'Disposed from clearance stock'
            );

            ClearanceAction::create([
                'branch_id' => $stock->branch_id,
                'clearance_stocks_id' => $stock->id,
                'item_id' => $stock->item_id,
                'batch_inventory_id' => $stock->batch_inventory_id,
                'action_type' => 'dispose',
                'qty' => $qty,
                'loss_value' => $disposalLine->total_value,
                'disposal_id' => $disposal->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncClearanceItemStatus($stock);

            return $disposal;
        });
    }

    private function assertQtyAvailable(ClearanceStock $stock, int $qty): void
    {
        if ($qty < 1) {
            throw new \RuntimeException('Quantity must be at least 1.');
        }

        if ($stock->qty_remaining < $qty) {
            throw new \RuntimeException("Insufficient clearance stock: remaining {$stock->qty_remaining}, requested {$qty}.");
        }
    }

    private function mapDisposalReason(string $reason): string
    {
        return match ($reason) {
            'damaged' => 'damage',
            'quality' => 'obsolescence',
            default => $reason,
        };
    }

    private function syncClearanceItemStatus(ClearanceStock $stock): void
    {
        $stock->refresh();

        if ($stock->qty_remaining > 0) {
            return;
        }

        $stock->clearanceItem?->update(['approval_status' => 'actioned']);
    }
}
