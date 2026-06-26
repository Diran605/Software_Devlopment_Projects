<?php

namespace App\Filament\App\Resources\OpeningStocks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OpeningStockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('entry_number')
                            ->label('Entry Number'),
                        TextEntry::make('department.name')
                            ->label('Department')
                            ->placeholder('All departments'),
                        TextEntry::make('postedBy.name')
                            ->label('Posted By'),
                        TextEntry::make('posted_at')
                            ->label('Posted At')
                            ->dateTime(),
                        TextEntry::make('lines_count')
                            ->label('Total Lines')
                            ->state(fn ($record) => $record->openingStockLines->count()),
                        TextEntry::make('total_qty')
                            ->label('Total Quantity')
                            ->state(fn ($record) => $record->openingStockLines->sum('qty_on_hand')),
                        TextEntry::make('total_value')
                            ->label('Total Value')
                            ->money('xaf')
                            ->state(fn ($record) => $record->openingStockLines->sum(
                                fn ($line) => $line->qty_on_hand * $line->unit_cost
                            )),
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ]),
            ]);
    }
}
