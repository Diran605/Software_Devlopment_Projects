<?php

namespace App\Filament\App\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('reference_number')
                            ->required(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('payee')
                            ->required(),
                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('FCFA ')
                            ->required(),
                        DatePicker::make('expense_date')
                            ->required()
                            ->default(now()),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Hidden::make('created_by')
                            ->default(auth()->id()),
                        Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ]),
            ]);
    }
}
