<?php

namespace App\Filament\Admin\Resources\Items\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->required(),

                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('uom_id')
                            ->relationship('uom', 'name')
                            ->searchable()
                            ->preload(),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('unit_cost')
                            ->label('Reference Cost Price')
                            ->helperText('This is a reference price only. Actual acquisition cost is recorded on each Opening Stock entry or GRN.')
                            ->numeric()
                            ->prefix('FCFA '),

                        TextInput::make('min_selling_price')
                            ->numeric()
                            ->prefix('FCFA '),

                        TextInput::make('selling_price')
                            ->numeric()
                            ->prefix('FCFA '),

                        TextInput::make('reorder_level')
                            ->numeric(),

                        TextInput::make('reorder_quantity')
                            ->numeric(),

                        Toggle::make('is_packaged')
                            ->live()
                            ->default(false),

                        Select::make('packaging_type_id')
                            ->relationship('packagingType', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('is_packaged')),

                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }
}
