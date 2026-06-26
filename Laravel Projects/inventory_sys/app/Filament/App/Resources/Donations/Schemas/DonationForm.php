<?php

namespace App\Filament\App\Resources\Donations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use App\Support\FormatsDates;
use Filament\Facades\Filament;

class DonationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('donation_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on save'),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('recipient')
                            ->label('Recipient Name')
                            ->required(),
                        TextInput::make('recipient_contact')
                            ->label('Recipient Contact')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('recipient_address')
                            ->label('Recipient Address')
                            ->rows(2)
                            ->columnSpanFull(),
                        DatePicker::make('donated_at')
                            ->required()
                            ->default(now()),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                Select::make('clearance_stock_id')
                                    ->label('Clearance Stock Item')
                                    ->options(function () {
                                        $tenantId = Filament::getTenant()?->id;
                                        return \App\Models\ClearanceStock::where('branch_id', $tenantId)
                                            ->where('qty_remaining', '>', 0)
                                            ->with('item')
                                            ->get()
                                            ->mapWithKeys(function ($stock) {
                                                $expiryStr = FormatsDates::formatDate($stock->expiry_date);
                                                return [$stock->id => "{$stock->item->name} | Batch: {$stock->batch_number} | Expiry: {$expiryStr} | Qty Left: {$stock->qty_remaining}"];
                                            });
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $stock = \App\Models\ClearanceStock::find($state);
                                            if ($stock) {
                                                $set('item_id', $stock->item_id);
                                                $set('batch_inventory_id', $stock->batch_inventory_id);
                                                $set('unit_cost', $stock->unit_cost);
                                            }
                                        }
                                    })
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('qty_donated')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $unitCost = $get('unit_cost') ?? 0;
                                        $set('total_value', $state * $unitCost);
                                    })
                                    ->label('Qty Donated'),
                                TextInput::make('unit_cost')
                                    ->numeric()
                                    ->readOnly()
                                    ->label('Unit Cost'),
                                TextInput::make('total_value')
                                    ->numeric()
                                    ->readOnly()
                                    ->label('Total Value'),
                                TextInput::make('notes')
                                    ->label('Line Notes')
                                    ->columnSpan(2),
                                Hidden::make('item_id'),
                                Hidden::make('batch_inventory_id'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),

                        Hidden::make('created_by')
                            ->default(auth()->id()),
                        Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ]),
            ]);
    }
}
