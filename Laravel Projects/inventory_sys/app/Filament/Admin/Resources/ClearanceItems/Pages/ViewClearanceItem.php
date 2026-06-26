<?php

namespace App\Filament\Admin\Resources\ClearanceItems\Pages;

use App\Filament\Admin\Resources\ClearanceItems\ClearanceItemResource;
use App\Services\ClearanceItemApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewClearanceItem extends ViewRecord
{
    protected static string $resource = ClearanceItemResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['item.category', 'item.uom', 'batchInventory', 'rule']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getInfolistContentComponent(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $defaultDiscount = fn (): float => (float) ($this->record->rule?->discount ?? 0);
        $defaultClearancePrice = fn (): float => (float) $this->record->original_price * (1 - $defaultDiscount() / 100);

        return [
            Action::make('approve')
                ->label('Approve & Set Action')
                ->color('success')
                ->visible(fn () => $this->record->approval_status === 'pending' && auth()->user()->can('approve', $this->record))
                ->form([
                    TextInput::make('qty_to_move')
                        ->label('Qty to Move to Clearance')
                        ->numeric()
                        ->default(fn () => $this->record->qty_flagged)
                        ->required(),
                    TextInput::make('discount_percent')
                        ->label('Discount %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default($defaultDiscount)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $original = (float) $this->record->original_price;
                            $set('clearance_price', round($original * (1 - ((float) $state / 100)), 2));
                        }),
                    TextInput::make('clearance_price')
                        ->label('Clearance Price')
                        ->numeric()
                        ->minValue(0)
                        ->default($defaultClearancePrice)
                        ->required(),
                    Select::make('action_type')
                        ->label('Action Type')
                        ->options([
                            'sell' => 'Discount & Sell',
                            'donate' => 'Donate',
                            'dispose' => 'Dispose',
                        ])
                        ->default('sell')
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data, ClearanceItemApprovalService $approvalService) {
                    $approvalService->approve($this->record, $data);
                    $this->record->refresh();
                }),
            Action::make('decline')
                ->label('Decline')
                ->color('danger')
                ->visible(fn () => $this->record->approval_status === 'pending' && auth()->user()->can('edit', $this->record))
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
            \Filament\Actions\EditAction::make()
                ->visible(fn () => $this->record->approval_status === 'pending'),
        ];
    }
}
