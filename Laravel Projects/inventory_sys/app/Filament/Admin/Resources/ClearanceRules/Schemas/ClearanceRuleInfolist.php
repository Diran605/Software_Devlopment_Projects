<?php

namespace App\Filament\Admin\Resources\ClearanceRules\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ClearanceRuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('branch.name')
                            ->label('Branch'),
                        TextEntry::make('name')
                            ->label('Urgency Label'),
                        TextEntry::make('days_min')
                            ->label('Days to Expiry Min'),
                        TextEntry::make('days_max')
                            ->label('Days to Expiry Max'),
                        TextEntry::make('discount')
                            ->label('Discount %')
                            ->suffix('%'),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        TextEntry::make('clearanceItems')
                            ->label('Flagged Items')
                            ->state(fn ($record) => $record->clearanceItems()->count()),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ]),
            ]);
    }
}
