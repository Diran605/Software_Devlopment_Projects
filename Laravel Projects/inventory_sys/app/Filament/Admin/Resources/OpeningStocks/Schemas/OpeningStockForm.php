<?php

namespace App\Filament\Admin\Resources\OpeningStocks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OpeningStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(4)
                    ->schema([
                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Branch'),
                        DateTimePicker::make('posted_at')
                            ->required()
                            ->default(now()),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->nullable()
                            ->label('Department'),
                        Textarea::make('notes')
                            ->columnSpan(1)
                            ->rows(1),
                    ]),
                
                Repeater::make('openingStockLines')
                    ->relationship('openingStockLines')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                Select::make('item_id')
                                    ->relationship('item', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('batch_number')
                                    ->required(),
                                DatePicker::make('expiry_date')
                                    ->nullable(),
                                TextInput::make('qty_on_hand')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('Qty On Hand'),
                                TextInput::make('unit_cost')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Unit Cost'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->minItems(1),
            ]);
    }
}
