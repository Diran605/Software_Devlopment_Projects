<?php

namespace App\Filament\App\Resources\StockMovements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class StockMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('moved_at')
                            ->label('Date')
                            ->dateTime(),
                        TextEntry::make('movement_type')
                            ->label('Type')
                            ->badge(),
                        TextEntry::make('item.name')
                            ->label('Item'),
                        TextEntry::make('department.name')
                            ->label('Department')
                            ->placeholder('—'),
                        TextEntry::make('batch_number')
                            ->label('Batch #')
                            ->placeholder('—'),
                        TextEntry::make('expiry_date')
                            ->label('Expiry Date')
                            ->date('M d, Y')
                            ->placeholder('—'),
                        TextEntry::make('qty_in')
                            ->label('Qty In'),
                        TextEntry::make('qty_out')
                            ->label('Qty Out'),
                        TextEntry::make('qty_before')
                            ->label('Qty Before')
                            ->placeholder('—'),
                        TextEntry::make('qty_after')
                            ->label('Qty After')
                            ->placeholder('—'),
                        TextEntry::make('unit_cost')
                            ->label('Unit Cost')
                            ->money('xaf'),
                        TextEntry::make('unit_price')
                            ->label('Unit Price')
                            ->money('xaf')
                            ->placeholder('—'),
                        TextEntry::make('recordedBy.name')
                            ->label('Recorded By'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
