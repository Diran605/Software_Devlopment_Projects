<?php

namespace App\Filament\App\Resources\InventoryCounts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

class InventoryCountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('count_number')
                            ->required()
                            ->default(fn () => 'CNT-' . strtoupper(uniqid())),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        DatePicker::make('count_at')
                            ->required()
                            ->default(now())
                            ->label('Count Date'),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Hidden::make('created_by')
                            ->default(auth()->id()),
                        Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),
                        Hidden::make('status')
                            ->default('draft'),
                    ]),
            ]);
    }
}
