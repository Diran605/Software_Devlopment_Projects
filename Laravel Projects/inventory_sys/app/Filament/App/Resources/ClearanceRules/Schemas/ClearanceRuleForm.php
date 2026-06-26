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
                            ->numeric()
                            ->helperText('Lower bound (e.g. 15 for Approaching band)'),
                        TextInput::make('days_max')
                            ->label('Days to Expiry Max')
                            ->numeric()
                            ->helperText('Upper bound (e.g. 30 for Approaching band)')
                            ->rules([
                                fn (callable $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    $min = $get('days_min');
                                    if ($min !== null && $value !== null && (float) $min > (float) $value) {
                                        $fail('Days min cannot be greater than days max.');
                                    }
                                },
                            ]),
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
