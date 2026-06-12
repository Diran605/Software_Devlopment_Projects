<?php

namespace App\Filament\Admin\Resources\ClearanceItems\Pages;

use App\Filament\Admin\Resources\ClearanceItems\ClearanceItemResource;
use App\Models\ClearanceStock;
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
                        $this->record->update([
                            'approval_status' => 'approved',
                            'action_type' => $data['action_type'],
                            'qty_to_move' => $data['qty_to_move'],
                            'notes' => $data['notes'],
                        ]);

                        $originalPrice = $this->record->original_price;
                        $discountPercent = $this->record->rule?->discount ?? 0;
                        $clearancePrice = $originalPrice * (1 - $discountPercent / 100);

                        ClearanceStock::create([
                            'branch_id' => $this->record->branch_id,
                            'department_id' => $this->record->item->department_id,
                            'clearance_item_id' => $this->record->id,
                            'item_id' => $this->record->item_id,
                            'batch_inventory_id' => $this->record->batch_inventory_id,
                            'batch_number' => $this->record->batch_inventory->batch_number,
                            'expiry_date' => $this->record->batch_inventory->expiry_date,
                            'qty_on_clearance' => $data['qty_to_move'],
                            'qty_remaining' => $data['qty_to_move'],
                            'original_price' => $originalPrice,
                            'clearance_price' => $clearancePrice,
                            'unit_cost' => $this->record->batch_inventory->unit_cost,
                        ]);

                        $this->record->update(['approval_status' => 'actioned']);
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
