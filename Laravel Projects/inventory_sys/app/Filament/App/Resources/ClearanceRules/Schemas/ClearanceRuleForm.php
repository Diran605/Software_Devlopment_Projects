<?php

namespace App\Filament\App\Resources\ClearanceRules\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

class ClearanceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('days_min')
                            ->label('Days to Expiry Min')
                            ->numeric(),
                        TextInput::make('days_max')
                            ->label('Days to Expiry Max')
                            ->numeric(),
                        TextInput::make('discount')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        Toggle::make('is_active')
                            ->default(true),
                        Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ]),
            ]);
    }
}
