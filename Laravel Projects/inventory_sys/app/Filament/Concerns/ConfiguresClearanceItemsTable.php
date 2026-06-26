<?php

namespace App\Filament\Concerns;

use App\Services\ClearanceItemApprovalService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

trait ConfiguresClearanceItemsTable
{
    public static function clearanceItemFilters(): array
    {
        return [
            SelectFilter::make('urgency_status')
                ->options([
                    'Approaching' => 'Approaching',
                    'Urgent' => 'Urgent',
                    'Critical' => 'Critical',
                    'Expired' => 'Expired',
                ])
                ->multiple(),
            SelectFilter::make('approval_status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'declined' => 'Declined',
                    'actioned' => 'Actioned',
                ])
                ->multiple(),
            SelectFilter::make('category')
                ->label('Category')
                ->relationship('item.category', 'name')
                ->searchable()
                ->preload(),
            Filter::make('expiry_date')
                ->label('Expiry Date Range')
                ->form([
                    DatePicker::make('expiry_from')->label('From'),
                    DatePicker::make('expiry_until')->label('Until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['expiry_from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereHas(
                                'batchInventory',
                                fn (Builder $query): Builder => $query->whereDate('expiry_date', '>=', $date)
                            )
                        )
                        ->when(
                            $data['expiry_until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereHas(
                                'batchInventory',
                                fn (Builder $query): Builder => $query->whereDate('expiry_date', '<=', $date)
                            )
                        );
                }),
        ];
    }

    public static function clearanceItemBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('bulkApprove')
                    ->label('Bulk Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()->can('approve.clearance-manager'))
                    ->deselectRecordsAfterCompletion()
                    ->form([
                        Select::make('action_type')
                            ->label('Action Type')
                            ->options([
                                'sell' => 'Discount & Sell',
                                'donate' => 'Donate',
                                'dispose' => 'Dispose',
                            ])
                            ->default('sell')
                            ->required(),
                        TextInput::make('discount_percent')
                            ->label('Discount %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->action(function (Collection $records, array $data, ClearanceItemApprovalService $approvalService): void {
                        $pending = $records->where('approval_status', 'pending');

                        if ($pending->isEmpty()) {
                            Notification::make()
                                ->title('No pending items selected')
                                ->warning()
                                ->send();

                            return;
                        }

                        $approved = 0;
                        $errors = [];

                        foreach ($pending as $item) {
                            try {
                                $item->loadMissing(['batchInventory', 'item', 'rule']);
                                $discount = $data['discount_percent'] ?? $item->rule?->discount ?? 0;

                                $approvalService->approve($item, [
                                    'qty_to_move' => $item->qty_flagged,
                                    'action_type' => $data['action_type'],
                                    'discount_percent' => $discount,
                                    'notes' => $data['notes'] ?? null,
                                ]);
                                $approved++;
                            } catch (\Throwable $e) {
                                $errors[] = "{$item->item?->name}: {$e->getMessage()}";
                            }
                        }

                        if ($approved > 0) {
                            Notification::make()
                                ->title("{$approved} item(s) approved")
                                ->success()
                                ->send();
                        }

                        if ($errors !== []) {
                            Notification::make()
                                ->title('Some items could not be approved')
                                ->body(implode("\n", array_slice($errors, 0, 5)))
                                ->danger()
                                ->send();
                        }
                    }),
            ]),
        ];
    }
}
