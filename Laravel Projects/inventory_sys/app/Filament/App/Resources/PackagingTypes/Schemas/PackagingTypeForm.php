<?php

namespace App\Filament\App\Resources\PackagingTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

class PackagingTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('base_uom_id')
                    ->options(\App\Models\UnitOfMeasure::orderBy('name')->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
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

                Hidden::make('branch_id')
                    ->default(fn () => Filament::getTenant()?->id),
            ]);
    }
}
