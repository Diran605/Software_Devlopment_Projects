<?php

namespace App\Filament\App\Resources\Branches\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('code')
                            ->required(),
                        TextInput::make('phone')
                            ->tel(),
                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }
}
