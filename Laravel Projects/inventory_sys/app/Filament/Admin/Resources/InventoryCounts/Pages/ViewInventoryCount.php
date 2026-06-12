<?php

namespace App\Filament\Admin\Resources\InventoryCounts\Pages;

use App\Filament\Admin\Resources\InventoryCounts\InventoryCountResource;
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
                        $this->record->lines->each(function ($line) {
                            $variance = $line->qty_counted - $line->qty_system;
                            if ($variance !== 0) {
                                $item = $line->item;
                                $item->increment('qty_on_hand', $variance);
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
            ->query(fn() => $this->record->lines()->with('item', 'batchInventory'))
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable(),
                TextColumn::make('item.sku')
                    ->label('SKU')
                    ->placeholder('—'),
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
                    ->money('usd')
                    ->placeholder('—'),
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
                    ->visible(fn() => in_array($this->record->status, ['draft', 'in_progress', 'pending_approval'])),
                DeleteAction::make()
                    ->visible(fn() => in_array($this->record->status, ['draft', 'in_progress'])),
            ]);
    }
}
