<?php

namespace App\Filament\App\Resources\ClearanceStock\Pages;

use App\Filament\App\Resources\ClearanceStock\ClearanceStockResource;
use App\Models\ClearanceAction;
use App\Models\Disposal;
use App\Models\Donation;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewClearanceStock extends ViewRecord
{
    protected static string $resource = ClearanceStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sell')
                ->label('Sell (Clearance)')
                ->color('success')
                ->visible(fn() => $this->record->qty_remaining > 0)
                ->form([
                    TextInput::make('qty')
                        ->label('Qty to Sell')
                        ->numeric()
                        ->default(fn() => $this->record->qty_remaining)
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn() => $this->record->qty_remaining),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        // Calculate loss value
                        $lossValue = ($this->record->original_price - $this->record->clearance_price) * $data['qty'];

                        // Create Clearance Action
                        ClearanceAction::create([
                            'branch_id' => $this->record->branch_id,
                            'clearance_stocks_id' => $this->record->id,
                            'item_id' => $this->record->item_id,
                            'batch_inventory_id' => $this->record->batch_inventory_id,
                            'action_type' => 'sell',
                            'qty' => $data['qty'],
                            'loss_value' => $lossValue,
                            'notes' => $data['notes'],
                        ]);

                        // Update Clearance Stock remaining
                        $this->record->decrement('qty_remaining', $data['qty']);
                    });
                }),
            Action::make('donate')
                ->label('Donate')
                ->color('info')
                ->visible(fn() => $this->record->qty_remaining > 0)
                ->form([
                    TextInput::make('qty')
                        ->label('Qty to Donate')
                        ->numeric()
                        ->default(fn() => $this->record->qty_remaining)
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn() => $this->record->qty_remaining),
                    TextInput::make('recipient')
                        ->label('Recipient')
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        // Calculate loss value (full unit cost since we're donating)
                        $lossValue = $this->record->unit_cost * $data['qty'];

                        // Create Donation record
                        $donation = Donation::create([
                            'branch_id' => $this->record->branch_id,
                            'department_id' => $this->record->department_id,
                            'item_id' => $this->record->item_id,
                            'quantity' => $data['qty'],
                            'donation_date' => now(),
                            'recipient' => $data['recipient'],
                            'notes' => $data['notes'],
                            'created_by' => auth()->id(),
                        ]);

                        // Create Clearance Action
                        ClearanceAction::create([
                            'branch_id' => $this->record->branch_id,
                            'clearance_stocks_id' => $this->record->id,
                            'item_id' => $this->record->item_id,
                            'batch_inventory_id' => $this->record->batch_inventory_id,
                            'action_type' => 'donate',
                            'qty' => $data['qty'],
                            'loss_value' => $lossValue,
                            'donation_id' => $donation->id,
                            'notes' => $data['notes'],
                        ]);

                        // Update Clearance Stock remaining
                        $this->record->decrement('qty_remaining', $data['qty']);
                    });
                }),
            Action::make('dispose')
                ->label('Dispose')
                ->color('danger')
                ->visible(fn() => $this->record->qty_remaining > 0)
                ->form([
                    TextInput::make('qty')
                        ->label('Qty to Dispose')
                        ->numeric()
                        ->default(fn() => $this->record->qty_remaining)
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn() => $this->record->qty_remaining),
                    Select::make('reason')
                        ->label('Disposal Reason')
                        ->options([
                            'expired' => 'Expired',
                            'damaged' => 'Damaged',
                            'quality' => 'Quality Issue',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        // Calculate loss value (full unit cost since we're disposing)
                        $lossValue = $this->record->unit_cost * $data['qty'];

                        // Create Disposal record
                        $disposal = Disposal::create([
                            'branch_id' => $this->record->branch_id,
                            'department_id' => $this->record->department_id,
                            'item_id' => $this->record->item_id,
                            'quantity' => $data['qty'],
                            'disposal_date' => now(),
                            'reason' => $data['reason'],
                            'notes' => $data['notes'],
                            'created_by' => auth()->id(),
                        ]);

                        // Create Clearance Action
                        ClearanceAction::create([
                            'branch_id' => $this->record->branch_id,
                            'clearance_stocks_id' => $this->record->id,
                            'item_id' => $this->record->item_id,
                            'batch_inventory_id' => $this->record->batch_inventory_id,
                            'action_type' => 'dispose',
                            'qty' => $data['qty'],
                            'loss_value' => $lossValue,
                            'disposal_id' => $disposal->id,
                            'notes' => $data['notes'],
                        ]);

                        // Update Clearance Stock remaining
                        $this->record->decrement('qty_remaining', $data['qty']);
                    });
                }),
        ];
    }
}
