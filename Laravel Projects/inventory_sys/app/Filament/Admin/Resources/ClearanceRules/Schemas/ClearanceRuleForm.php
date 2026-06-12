<?php

namespace App\Filament\Admin\Resources\ClearanceRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClearanceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('branch_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('days_min')
                    ->label('Days to Expiry Min')
                    ->numeric(),
                TextInput::make('days_max')
                    ->label('Days to Expiry Max')
                    ->numeric(),
                TextInput::make('discount')
                    ->label('Discount %')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
