<?php

namespace App\Filament\App\Resources\ClearanceItems\Pages;

use App\Filament\App\Resources\ClearanceItems\ClearanceItemResource;
use App\Models\ClearanceStock;
use App\Models\ClearanceAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewClearanceItem extends ViewRecord
{
    protected static string $resource = ClearanceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn() => $this->record->approval_status === 'pending')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('qty_to_move')
                        ->label('Qty to Move')
                        ->numeric()
                        ->default(fn() => $this->record->qty_flagged)
                        ->required(),
                    Select::make('action_type')
                        ->options([
                            'sell' => 'Sell as Clearance',
                            'donate' => 'Donate',
                            'dispose' => 'Dispose',
                        ])
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $batch = $this->record->batch_inventory;
                        if ($batch) {
                            if ($batch->qty_remaining < $data['qty_to_move']) {
                                throw new \Exception("Insufficient stock in normal batch: remaining {$batch->qty_remaining}, requested {$data['qty_to_move']}.");
                            }
                            $batch->qty_remaining -= $data['qty_to_move'];
                            $batch->save();
                        }

                        // Deduct normal stock level
                        $inventoryService = app(\App\Services\InventoryService::class);
                        $inventoryService->updateStockLevel(
                            $this->record->branch_id,
                            $this->record->item->department_id,
                            $this->record->item_id,
                            -$data['qty_to_move']
                        );

                        // Create Stock Movement
                        $stockMovementService = app(\App\Services\StockMovementService::class);
                        $stockMovementService->record(
                            branchId: $this->record->branch_id,
                            departmentId: $this->record->item->department_id,
                            itemId: $this->record->item_id,
                            batchInventoryId: $this->record->batch_inventory_id,
                            recordedBy: auth()->id(),
                            movementType: 'clearance_out',
                            qtyIn: 0,
                            qtyOut: $data['qty_to_move'],
                            qtyBefore: null,
                            qtyAfter: null,
                            unitCost: $batch?->unit_cost,
                            referenceType: get_class($this->record),
                            referenceId: $this->record->id,
                            batchNumber: $batch?->batch_number,
                            expiryDate: $batch?->expiry_date,
                            notes: $data['notes']
                        );

                        // Update Clearance Item
                        $this->record->update([
                            'approval_status' => 'approved',
                            'action_type' => $data['action_type'],
                            'qty_to_move' => $data['qty_to_move'],
                            'notes' => $data['notes'],
                        ]);

                        // Create Clearance Stock record
                        $originalPrice = $this->record->original_price;
                        $discountPercent = $this->record->rule?->discount ?? 0;
                        $clearancePrice = $originalPrice * (1 - $discountPercent / 100);

                        ClearanceStock::create([
                            'branch_id' => $this->record->branch_id,
                            'department_id' => $this->record->item->department_id,
                            'clearance_item_id' => $this->record->id,
                            'item_id' => $this->record->item_id,
                            'batch_inventory_id' => $this->record->batch_inventory_id,
                            'batch_number' => $batch?->batch_number,
                            'expiry_date' => $batch?->expiry_date,
                            'qty_on_clearance' => $data['qty_to_move'],
                            'qty_remaining' => $data['qty_to_move'],
                            'original_price' => $originalPrice,
                            'clearance_price' => $clearancePrice,
                            'unit_cost' => $batch?->unit_cost,
                        ]);
                    });
                }),
            Action::make('decline')
                ->label('Decline')
                ->color('danger')
                ->visible(fn() => $this->record->approval_status === 'pending')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('notes')
                        ->label('Reason for Decline')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'approval_status' => 'declined',
                        'notes' => $data['notes'],
                    ]);
                }),
            \Filament\Actions\EditAction::make(),
        ];
    }
}
