<?php

namespace App\Filament\Admin\Resources\ClearanceItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ClearanceItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('item.name')
                            ->label('Item'),
                        TextEntry::make('item.category.name')
                            ->label('Category')
                            ->placeholder('—'),
                        TextEntry::make('item.uom.name')
                            ->label('UOM')
                            ->placeholder('—'),
                        TextEntry::make('batchInventory.batch_number')
                            ->label('Batch #')
                            ->placeholder('—'),
                        TextEntry::make('batchInventory.expiry_date')
                            ->label('Expiry Date')
                            ->date('M d, Y')
                            ->placeholder('—'),
                        TextEntry::make('batchInventory.qty_remaining')
                            ->label('Qty Remaining in Batch'),
                        TextEntry::make('batchInventory.unit_cost')
                            ->label('Unit Cost')
                            ->money('xaf'),
                        TextEntry::make('qty_flagged')
                            ->label('Qty Flagged'),
                        TextEntry::make('days_to_expiry')
                            ->label('Days to Expiry'),
                        TextEntry::make('urgency_status')
                            ->label('Urgency Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Approaching' => 'warning',
                                'Urgent' => 'warning',
                                'Critical' => 'danger',
                                'Expired' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('approval_status')
                            ->label('Approval Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'gray',
                                'approved' => 'success',
                                'declined' => 'danger',
                                'actioned' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('original_price')
                            ->label('Original Price')
                            ->money('xaf'),
                        TextEntry::make('clearance_price')
                            ->label('Clearance Price')
                            ->money('xaf'),
                        TextEntry::make('rule.name')
                            ->label('Rule')
                            ->placeholder('—'),
                        TextEntry::make('rule.days_min')
                            ->label('Rule Days Min')
                            ->placeholder('—'),
                        TextEntry::make('rule.days_max')
                            ->label('Rule Days Max')
                            ->placeholder('—'),
                        TextEntry::make('rule.discount')
                            ->label('Suggested Discount')
                            ->suffix('%')
                            ->placeholder('—'),
                        TextEntry::make('action_type')
                            ->label('Action Type')
                            ->placeholder('—'),
                        TextEntry::make('qty_to_move')
                            ->label('Qty to Move')
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
