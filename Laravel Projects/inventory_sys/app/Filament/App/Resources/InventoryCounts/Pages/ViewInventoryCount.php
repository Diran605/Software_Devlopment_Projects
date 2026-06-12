<?php

namespace App\Filament\App\Resources\InventoryCounts\Pages;

use App\Filament\App\Resources\InventoryCounts\InventoryCountResource;
use App\Models\InventoryCountLine;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ViewInventoryCount extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = InventoryCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit for Approval')
                ->color('warning')
                ->visible(fn() => $this->record->status === 'in_progress')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'pending_approval']);
                    $this->refresh();
                }),
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn() => $this->record->status === 'pending_approval')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                    $this->refresh();
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->visible(fn() => $this->record->status === 'pending_approval')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'in_progress',
                    ]);
                    $this->refresh();
                }),
            Action::make('post')
                ->label('Post')
                ->color('success')
                ->visible(fn() => $this->record->status === 'approved')
                ->requiresConfirmation()
                ->action(function () {
                    DB::transaction(function () {
                        $inventoryService = app(\App\Services\InventoryService::class);
                        $stockMovementService = app(\App\Services\StockMovementService::class);

                        $this->record->lines->each(function ($line) use ($inventoryService, $stockMovementService) {
                            $variance = $line->qty_counted - $line->qty_system;

                            $batch = $line->batchInventory;
                            if ($batch) {
                                $batch->qty_remaining = $line->qty_counted;
                                if ($line->unit_cost != $batch->unit_cost) {
                                    $batch->unit_cost = $line->unit_cost;

                                    // Manually update ItemStockLevel cost price too!
                                    \App\Models\ItemStockLevel::where('branch_id', $this->record->branch_id)
                                        ->where('department_id', $this->record->department_id)
                                        ->where('item_id', $line->item_id)
                                        ->update(['unit_cost' => $line->unit_cost]);
                                }
                                $batch->save();
                            }

                            if ($line->selling_price !== null && $line->selling_price != $line->item->selling_price) {
                                $item = $line->item;
                                $item->selling_price = $line->selling_price;
                                $item->save();
                            }

                            // Update stock level
                            $inventoryService->updateStockLevel(
                                $this->record->branch_id,
                                $this->record->department_id,
                                $line->item_id,
                                $variance,
                                $line->unit_cost
                            );

                            if ($variance !== 0) {
                                // Record stock movement
                                $qtyIn = $variance > 0 ? $variance : 0;
                                $qtyOut = $variance < 0 ? abs($variance) : 0;

                                $stockMovementService->record(
                                    branchId: $this->record->branch_id,
                                    departmentId: $this->record->department_id,
                                    itemId: $line->item_id,
                                    batchInventoryId: $line->batch_inventory_id,
                                    recordedBy: auth()->id(),
                                    movementType: 'count_adjustment',
                                    qtyIn: $qtyIn,
                                    qtyOut: $qtyOut,
                                    qtyBefore: $line->qty_system,
                                    qtyAfter: $line->qty_counted,
                                    unitCost: $line->unit_cost,
                                    unitPrice: $line->selling_price,
                                    referenceType: get_class($line),
                                    referenceId: $line->id,
                                    batchNumber: $batch?->batch_number,
                                    expiryDate: $batch?->expiry_date,
                                    notes: $line->notes
                                );
                            }
                        });

                        $this->record->update([
                            'status' => 'posted',
                            'posted_by' => auth()->id(),
                            'posted_at' => now(),
                        ]);
                    });

                    $this->refresh();
                }),
            Action::make('cancel')
                ->label('Cancel')
                ->color('danger')
                ->visible(fn() => in_array($this->record->status, ['draft', 'in_progress']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    $this->redirect(InventoryCountResource::getUrl('index'));
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => $this->record->lines())
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable(),
                TextColumn::make('item.sku')
                    ->label('SKU'),
                TextColumn::make('batchInventory.batch_number')
                    ->label('Batch #')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('batchInventory.expiry_date')
                    ->label('Expiry Date')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->color(fn($record) => $record->batchInventory?->expiry_date?->isPast() ? 'danger' : ($record->batchInventory?->expiry_date && $record->batchInventory->expiry_date->diffInDays(now()) <= 30 ? 'warning' : 'gray'))
                    ->sortable(),
                TextColumn::make('qty_system')
                    ->label('System Qty')
                    ->sortable(),
                TextColumn::make('qty_counted')
                    ->label('Counted Qty')
                    ->placeholder('Pending')
                    ->sortable(),
                TextColumn::make('qty_variance')
                    ->label('Variance')
                    ->sortable()
                    ->color(fn($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),
                TextColumn::make('unit_cost')
                    ->label('Cost Price')
                    ->money('usd'),
                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->money('usd'),
                TextColumn::make('variance_value')
                    ->label('Variance Value')
                    ->money('usd')
                    ->color(fn($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('qty_counted')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label('Actual Qty')
                            ->disabled(fn() => in_array($this->record->status, ['pending_approval', 'approved'])),
                        TextInput::make('unit_cost')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label('Cost Price'),
                        TextInput::make('selling_price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label('Selling Price'),
                        Textarea::make('notes')
                            ->rows(2)
                            ->nullable()
                            ->disabled(fn() => in_array($this->record->status, ['pending_approval', 'approved'])),
                    ])
                    ->using(function (InventoryCountLine $record, array $data) {
                        $data['qty_variance'] = $data['qty_counted'] - $record->qty_system;
                        $data['variance_value'] = $data['qty_variance'] * $data['unit_cost'];
                        $record->update($data);
                    })
                    ->visible(fn() => in_array($this->record->status, ['draft', 'in_progress', 'pending_approval', 'approved'])),
                DeleteAction::make()
                    ->visible(fn() => in_array($this->record->status, ['draft', 'in_progress'])),
            ]);
    }
}
