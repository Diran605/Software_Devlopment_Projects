<?php

namespace App\Filament\App\Resources\Suppliers\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->maxLength(50)
                            ->label('Supplier Code'),
                        TextInput::make('contact_person')
                            ->maxLength(255)
                            ->label('Contact Person'),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('tax_id')
                            ->maxLength(100)
                            ->label('Tax ID'),
                        TextInput::make('payment_terms')
                            ->maxLength(255)
                            ->label('Payment Terms'),
                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
