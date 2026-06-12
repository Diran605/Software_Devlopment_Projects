<?php

namespace App\Filament\App\Resources\DeletionLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class DeletionLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextInput::make('deletedBy.name')->label('Deleted By')->disabled(),
                        DateTimePicker::make('deleted_at')->label('Deleted Time')->disabled(),
                        TextInput::make('record_number')->label('Reference Number')->disabled(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('record_type')
                            ->formatStateUsing(fn ($state) => class_basename($state))
                            ->label('Record Type')->disabled(),
                        TextInput::make('record_id')->label('Record ID')->disabled(),
                    ]),
                Textarea::make('reason')->label('Reason for Deletion')->disabled()->rows(3),
                Grid::make(2)
                    ->schema([
                        Textarea::make('snapshot')
                            ->label('Deleted Record Snapshot')
                            ->afterStateHydrated(fn ($state, $set) => $state ? $set('snapshot', json_encode($state, JSON_PRETTY_PRINT)) : null)
                            ->disabled()->rows(15),
                        Textarea::make('stock_reversal')
                            ->label('Stock Reversal Details')
                            ->afterStateHydrated(fn ($state, $set) => $state ? $set('stock_reversal', json_encode($state, JSON_PRETTY_PRINT)) : null)
                            ->disabled()->rows(15),
                    ]),
            ]);
    }
}
