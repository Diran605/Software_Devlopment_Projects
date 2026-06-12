<?php

namespace App\Filament\App\Resources\UnitOfMeasures\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

class UnitOfMeasureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('abbreviation')
                            ->required()
                            ->maxLength(10),
                        Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ]),
            ]);
    }
}
