<?php

namespace App\Filament\App\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Supplier'),
                        DatePicker::make('expected_delivery_at')
                            ->label('Expected Delivery Date')
                            ->required(),
                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                Repeater::make('purchaseOrderLines')
                    ->relationship('purchaseOrderLines')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                Select::make('item_id')
                                    ->relationship('item', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('Item')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $item = \App\Models\Item::find($state);
                                            if ($item) {
                                                $set('unit_cost', $item->unit_cost);
                                            }
                                        }
                                    }),
                                TextInput::make('qty_ordered')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->label('Qty Ordered')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = floatval($state ?? 0);
                                        $cost = floatval($get('unit_cost') ?? 0);
                                        $set('line_total', $qty * $cost);
                                    }),
                                TextInput::make('unit_cost')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('FCFA ')
                                    ->label('Unit Cost')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $cost = floatval($state ?? 0);
                                        $qty = floatval($get('qty_ordered') ?? 0);
                                        $set('line_total', $qty * $cost);
                                    }),
                                TextInput::make('line_total')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('FCFA ')
                                    ->default(0.00)
                                    ->label('Line Total'),
                                TextInput::make('notes')
                                    ->label('Notes')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $lines = $get('purchaseOrderLines') ?? [];
                        $total = 0;
                        foreach ($lines as $line) {
                            $qty = floatval($line['qty_ordered'] ?? 0);
                            $cost = floatval($line['unit_cost'] ?? 0);
                            $total += $qty * $cost;
                        }
                        $set('total_amount', $total);
                    }),

                Grid::make(3)
                    ->schema([
                        TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->prefix('FCFA ')
                            ->default(0.00)
                            ->label('Total Amount'),
                        TextInput::make('po_number')
                            ->required()
                            ->default(fn () => 'PO-' . strtoupper(uniqid()))
                            ->label('PO Number'),
                        Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }
}
