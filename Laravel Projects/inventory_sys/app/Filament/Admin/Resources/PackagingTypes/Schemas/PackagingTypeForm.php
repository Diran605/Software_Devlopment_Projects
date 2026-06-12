<?php

namespace App\Filament\Admin\Resources\PackagingTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PackagingTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Branch'),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('base_uom_id')
                    ->relationship('baseUom', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->label('Base Unit of Measure')
                    ->helperText('The base unit this packaging is measured in (e.g. Litres, Pieces).'),

                TextInput::make('units_per_pack')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required()
                    ->label('Units Per Pack')
                    ->helperText('How many base units are in one pack.'),

                Textarea::make('description')
                    ->rows(3)
                    ->nullable(),
            ]);
    }
}
