<?php

namespace App\Filament\Admin\Resources\ClearanceItems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ClearanceItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('qty_to_move')
                            ->label('Qty to Move')
                            ->numeric()
                            ->required(),
                        Select::make('action_type')
                            ->label('Action Type')
                            ->options([
                                'sell' => 'Sell as Clearance',
                                'donate' => 'Donate',
                                'dispose' => 'Dispose',
                            ])
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
