<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->required(),

                TextInput::make('phone')
                    ->tel(),

                TextInput::make('email')
                    ->email(),

                Textarea::make('address')
                    ->rows(3),

                Textarea::make('notes')
                    ->rows(3),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
