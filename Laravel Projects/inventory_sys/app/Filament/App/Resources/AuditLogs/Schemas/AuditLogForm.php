<?php

namespace App\Filament\App\Resources\AuditLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextInput::make('user.name')
                            ->label('User')
                            ->disabled(),
                        TextInput::make('event')
                            ->disabled(),
                        DateTimePicker::make('created_at')
                            ->label('Time')
                            ->disabled(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('auditable_type')
                            ->label('Record Type')
                            ->disabled(),
                        TextInput::make('auditable_id')
                            ->label('Record ID')
                            ->disabled(),
                    ]),
                Grid::make(2)
                    ->schema([
                        Textarea::make('old_values')
                            ->label('Before Changes')
                            ->afterStateHydrated(fn ($state, $set) => $state ? $set('old_values', json_encode($state, JSON_PRETTY_PRINT)) : null)
                            ->disabled()
                            ->rows(15),
                        Textarea::make('new_values')
                            ->label('After Changes')
                            ->afterStateHydrated(fn ($state, $set) => $state ? $set('new_values', json_encode($state, JSON_PRETTY_PRINT)) : null)
                            ->disabled()
                            ->rows(15),
                    ]),
            ]);
    }
}
