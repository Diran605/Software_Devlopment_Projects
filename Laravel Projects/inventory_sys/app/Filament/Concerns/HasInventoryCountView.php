<?php

namespace App\Filament\Concerns;

use App\Models\BatchInventory;
use App\Models\InventoryCountLine;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasInventoryCountView
{
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Section::make('Count Progress')
                    ->schema([
                        View::make('filament.inventory-counts.summary')
                            ->viewData(fn (): array => [
                                'summary' => $this->record->countSummary(),
                                'variance' => $this->record->varianceSummary(),
                                'showVariance' => $this->record->status === 'pending_approval',
                            ]),
                    ]),
                Section::make('Count Lines')
                    ->extraAttributes(['class' => 'inventory-count-lines-table'])
                    ->schema([
                        View::make('filament.inventory-counts.group-scripts'),
                        EmbeddedTable::make(),
                    ]),
            ]);
    }

    protected function getInventoryCountHeaderActions(): array
    {
        $varianceCount = fn (): int => $this->record->lines()->where('qty_variance', '!=', 0)->count();

        return [
            Action::make('print')
                ->label('Print Report')
                ->icon(Heroicon::Printer)
                ->url(fn () => route('reports.inventory-count.pdf', $this->record))
                ->openUrlInNewTab(),
            Action::make('submit')
                ->label('Submit for Approval')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'pending_approval']);
                    $this->refresh();
                }),
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_approval' && auth()->user()->can('approve', $this->record))
                ->modalHeading('Approve inventory count')
                ->modalDescription(fn () => "This will allow posting adjustments for {$varianceCount()} line(s) with variances. Stock levels are not updated until you post.")
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
                ->visible(fn () => $this->record->status === 'pending_approval')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $timestamp = now()->format('Y-m-d H:i');
                    $reason = "[Rejected {$timestamp}]: {$data['rejection_reason']}";
                    $notes = trim(collect([$this->record->notes, $reason])->filter()->implode("\n\n"));

                    $this->record->update([
                        'status' => 'in_progress',
                        'notes' => $notes,
                    ]);
                    $this->refresh();
                }),
            Action::make('post')
                ->label('Post')
                ->color('success')
                ->visible(fn () => $this->record->status === 'approved' && auth()->user()->can('post', $this->record))
                ->modalHeading('Post inventory count')
                ->modalDescription(fn () => "This will update stock levels for {$varianceCount()} line(s) with variances. This cannot be undone.")
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

                            $inventoryService->updateStockLevel(
                                $this->record->branch_id,
                                $this->record->department_id,
                                $line->item_id,
                                $variance,
                                $line->unit_cost
                            );

                            if ($variance !== 0) {
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
                ->visible(fn () => in_array($this->record->status, ['draft', 'in_progress']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    $this->redirect(static::$resource::getUrl('index'));
                }),
        ];
    }

    protected function initializeInventoryCountGroupExpansion(): void
    {
        $titles = $this->record
            ->itemGroupMeta()
            ->filter(fn (object $meta): bool => $meta->has_variance || $meta->has_pending)
            ->pluck('title')
            ->values()
            ->all();

        if ($titles === []) {
            return;
        }

        $encoded = json_encode($titles);

        $this->js("setTimeout(() => { if (window.expandInventoryCountGroups) window.expandInventoryCountGroups({$encoded}, true); }, 300);");
    }

    public function table(Table $table): Table
    {
        $groupMeta = fn () => $this->record
            ->itemGroupMeta()
            ->keyBy('item_id');

        $allGroupTitles = fn (): array => $this->record
            ->itemGroupMeta()
            ->pluck('title')
            ->values()
            ->all();

        return $table
            ->query(fn () => $this->record->lines()->with(['item.category', 'item.uom', 'batchInventory']))
            ->defaultGroup('item_id')
            ->groupingSettingsHidden()
            ->collapsedGroupsByDefault()
            ->groups([
                Group::make('item_id')
                    ->label('Item')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(function (InventoryCountLine $record) use ($groupMeta): string {
                        $meta = $groupMeta()[$record->item_id] ?? null;

                        return $meta->title ?? ($record->item?->name ?? 'Unknown Item');
                    })
                    ->getDescriptionFromRecordUsing(function (InventoryCountLine $record) use ($groupMeta): ?string {
                        $meta = $groupMeta()[$record->item_id] ?? null;

                        return $meta->description ?? null;
                    }),
            ])
            ->headerActions([
                Action::make('expandAllGroups')
                    ->label('Expand All')
                    ->link()
                    ->action(function () use ($allGroupTitles): void {
                        $encoded = json_encode($allGroupTitles());
                        $this->js("window.expandInventoryCountGroups({$encoded}, true);");
                    }),
                Action::make('collapseAllGroups')
                    ->label('Collapse All')
                    ->link()
                    ->action(function (): void {
                        $this->js('window.expandInventoryCountGroups([], false);');
                    }),
            ])
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('item.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('item.category.name')
                    ->label('Category')
                    ->placeholder('—'),
                TextColumn::make('item.uom.abbreviation')
                    ->label('UOM')
                    ->placeholder('—'),
                TextColumn::make('batchInventory.batch_number')
                    ->label('Batch #')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('batchInventory.expiry_date')
                    ->label('Expiry Date')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->color(fn ($record) => $record->batchInventory?->expiry_date?->isPast() ? 'danger' : ($record->batchInventory?->expiry_date && $record->batchInventory->expiry_date->diffInDays(now()) <= 30 ? 'warning' : 'gray'))
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
                    ->placeholder('—')
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),
                TextColumn::make('unit_cost')
                    ->label('Cost Price')
                    ->money('xaf'),
                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->money('xaf'),
                TextColumn::make('variance_value')
                    ->label('Variance Value')
                    ->money('xaf')
                    ->placeholder('—')
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('item.category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('line_status')
                    ->label('Line Status')
                    ->options([
                        'match' => 'Matched',
                        'shortage' => 'Shortage',
                        'surplus' => 'Surplus',
                        'pending' => 'Pending',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return match ($value) {
                            'match' => $query->whereNotNull('qty_counted')->where('qty_variance', 0),
                            'shortage' => $query->where('qty_variance', '<', 0),
                            'surplus' => $query->where('qty_variance', '>', 0),
                            'pending' => $query->whereNull('qty_counted'),
                            default => $query,
                        };
                    }),
                Filter::make('variance_only')
                    ->label('Variances only')
                    ->query(fn (Builder $query) => $query->where('qty_variance', '!=', 0))
                    ->toggle(),
                Filter::make('uncounted_only')
                    ->label('Uncounted only')
                    ->query(fn (Builder $query) => $query->whereNull('qty_counted'))
                    ->toggle(),
            ])
            ->emptyStateHeading('No count lines')
            ->emptyStateDescription('Lines are generated automatically from current batch stock when the count is created.')
            ->actions([
                Action::make('editLine')
                    ->label('Edit')
                    ->icon(Heroicon::PencilSquare)
                    ->modalHeading('Edit count line')
                    ->fillForm(fn (InventoryCountLine $record): array => [
                        'qty_counted' => $record->qty_counted,
                        'unit_cost' => $record->unit_cost,
                        'selling_price' => $record->selling_price,
                        'expiry_date' => $record->batchInventory?->expiry_date,
                        'notes' => $record->notes,
                    ])
                    ->form([
                        DatePicker::make('expiry_date')
                            ->label('Expiry Date')
                            ->nullable()
                            ->native(false)
                            ->disabled(fn () => $this->record->status === 'posted'),
                        TextInput::make('qty_counted')
                            ->required(fn () => ! in_array($this->record->status, ['pending_approval', 'approved', 'posted']))
                            ->numeric()
                            ->minValue(0)
                            ->label('Actual Qty')
                            ->disabled(fn () => in_array($this->record->status, ['pending_approval', 'approved', 'posted'])),
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
                            ->disabled(fn () => in_array($this->record->status, ['pending_approval', 'approved', 'posted'])),
                    ])
                    ->action(function (InventoryCountLine $record, array $data): void {
                        $expiryDate = $data['expiry_date'] ?? null;
                        unset($data['expiry_date']);

                        $qtyCounted = $data['qty_counted'] ?? $record->qty_counted;
                        if ($qtyCounted !== null) {
                            $data['qty_counted'] = $qtyCounted;
                            $data['qty_variance'] = $qtyCounted - $record->qty_system;
                            $data['variance_value'] = $data['qty_variance'] * $data['unit_cost'];
                        } else {
                            unset($data['qty_counted'], $data['qty_variance'], $data['variance_value']);
                        }

                        $record->update($data);

                        if ($record->batch_inventory_id) {
                            BatchInventory::query()
                                ->whereKey($record->batch_inventory_id)
                                ->update(['expiry_date' => $expiryDate]);
                        }
                    })
                    ->successNotificationTitle('Count line saved')
                    ->visible(fn () => in_array($this->record->status, ['draft', 'in_progress', 'pending_approval', 'approved'])),
                DeleteAction::make()
                    ->visible(fn () => in_array($this->record->status, ['draft', 'in_progress'])),
            ]);
    }
}
