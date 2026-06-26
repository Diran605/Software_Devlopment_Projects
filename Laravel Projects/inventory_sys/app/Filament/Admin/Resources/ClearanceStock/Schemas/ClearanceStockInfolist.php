<?php

namespace App\Filament\Admin\Resources\ClearanceStock\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ClearanceStockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('item.name')
                            ->label('Item'),
                        TextEntry::make('batch_number')
                            ->label('Batch #')
                            ->placeholder('—'),
                        TextEntry::make('expiry_date')
                            ->label('Expiry Date')
                            ->date('M d, Y')
                            ->placeholder('—'),
                        TextEntry::make('qty_on_clearance')
                            ->label('Qty On Clearance'),
                        TextEntry::make('qty_remaining')
                            ->label('Qty Remaining'),
                        TextEntry::make('original_price')
                            ->label('Original Price')
                            ->money('xaf'),
                        TextEntry::make('clearance_price')
                            ->label('Clearance Price')
                            ->money('xaf'),
                        TextEntry::make('unit_cost')
                            ->label('Unit Cost')
                            ->money('xaf'),
                        TextEntry::make('department.name')
                            ->label('Department')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
