<?php

namespace App\Filament\Admin\Resources\InventoryCounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;

class InventoryCountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->required(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
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
                        Hidden::make('status')
                            ->default('draft'),
                    ]),
            ]);
    }
}
