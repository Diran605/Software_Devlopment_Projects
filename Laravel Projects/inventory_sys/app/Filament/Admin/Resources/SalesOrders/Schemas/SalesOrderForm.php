<?php

namespace App\Filament\Admin\Resources\SalesOrders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->required()
                            ->live()
                            ->label('Branch'),
                        Select::make('customer_id')
                            ->relationship('customer', 'name', modifyQueryUsing: fn (Builder $query, callable $get) =>
                                $query->when($get('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
                            )
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->label('Customer'),
                        TextInput::make('customer_name')
                            ->label('Walk-in Customer Name')
                            ->visible(fn (callable $get) => !$get('customer_id'))
                            ->required(fn (callable $get) => !$get('customer_id')),
                        DateTimePicker::make('sold_at')
                            ->default(now())
                            ->required()
                            ->label('Sold At'),
                        Select::make('department_id')
                            ->relationship('department', 'name', modifyQueryUsing: fn (Builder $query, callable $get) =>
                                $query->when($get('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
                            )
                            ->nullable()
                            ->label('Department'),
                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                Repeater::make('salesOrderLines')
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                Hidden::make('id'),
                                Select::make('item_id')
                                    ->options(function (callable $get) {
                                        $branchId = $get('../../branch_id');
                                        return \App\Models\Item::query()
                                            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->label('Item')
                                    ->disabled(fn (callable $get) => filled($get('id')))
                                    ->helperText(function (callable $get) {
                                        $itemId = $get('item_id');
                                        $branchId = $get('../../branch_id');
                                        if ($itemId && $branchId) {
                                            $stock = \App\Models\ItemStockLevel::where('item_id', $itemId)
                                                ->where('branch_id', $branchId)
                                                ->value('qty_on_hand') ?? 0;
                                            return "Qty on hand: {$stock}";
                                        }
                                        return null;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                             $item = \App\Models\Item::find($state);
                                             if ($item) {
                                                 $set('unit_price', $item->selling_price);
                                             }
                                         }
                                         $set('batch_inventory_id', null);
                                     }),
                                Select::make('batch_inventory_id')
                                    ->label('Batch (Optional)')
                                    ->options(function (callable $get) {
                                        $itemId = $get('item_id');
                                        if (!$itemId) return [];
                                        
                                        $branchId = $get('../../branch_id');
                                        return \App\Models\BatchInventory::where('item_id', $itemId)
                                            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                            ->where('qty_remaining', '>', 0)
                                            ->get()
                                            ->mapWithKeys(function ($batch) {
                                                $expiry = $batch->expiry_date ? " | Exp: " . $batch->expiry_date->format('Y-m-d') : '';
                                                return [$batch->id => "Batch: {$batch->batch_number}{$expiry} (Qty: {$batch->qty_remaining})"];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live()
                                    ->disabled(fn (callable $get) => filled($get('id'))),
                                Toggle::make('entry_mode')
                                    ->label('Pack Mode')
                                    ->formatStateUsing(fn ($state) => $state === 'pack')
                                    ->dehydrateStateUsing(fn ($state) => $state ? 'pack' : 'unit')
                                    ->default(false)
                                    ->live(),
                                Select::make('packaging_type_id')
                                    ->options(\App\Models\PackagingType::orderBy('name')->pluck('name', 'id'))
                                    ->nullable()
                                    ->searchable()
                                    ->label('Packaging Type')
                                    ->visible(fn (callable $get) => $get('entry_mode')),
                                TextInput::make('pack_quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->visible(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $units = floatval($get('units_per_pack') ?? 1);
                                        $qty = floatval($state ?? 0) * $units;
                                        $set('qty_sold', $qty);
                                        
                                        $price = floatval($get('unit_price') ?? 0);
                                        $lineTotal = $qty * $price;
                                        $set('line_total', $lineTotal);

                                        if ($itemId = $get('item_id')) {
                                            $item = \App\Models\Item::find($itemId);
                                            if ($item) {
                                                $grossProfit = $lineTotal - ($qty * floatval($item->unit_cost));
                                                $set('gross_profit', $grossProfit);
                                                $set('margin_status', $grossProfit < 0 ? 'negative' : ($grossProfit < ($lineTotal * 0.2) ? 'low' : 'normal'));
                                            }
                                        }
                                    }),
                                TextInput::make('units_per_pack')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->visible(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $packs = floatval($get('pack_quantity') ?? 0);
                                        $qty = $packs * floatval($state ?? 1);
                                        $set('qty_sold', $qty);

                                        $price = floatval($get('unit_price') ?? 0);
                                        $lineTotal = $qty * $price;
                                        $set('line_total', $lineTotal);

                                        if ($itemId = $get('item_id')) {
                                            $item = \App\Models\Item::find($itemId);
                                            if ($item) {
                                                $grossProfit = $lineTotal - ($qty * floatval($item->unit_cost));
                                                $set('gross_profit', $grossProfit);
                                                $set('margin_status', $grossProfit < 0 ? 'negative' : ($grossProfit < ($lineTotal * 0.2) ? 'low' : 'normal'));
                                            }
                                        }
                                    }),
                                TextInput::make('qty_sold')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->live()
                                    ->label('Qty Sold')
                                    ->readOnly(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = floatval($state ?? 0);
                                        $price = floatval($get('unit_price') ?? 0);
                                        $lineTotal = $qty * $price;
                                        $set('line_total', $lineTotal);

                                        if ($itemId = $get('item_id')) {
                                            $item = \App\Models\Item::find($itemId);
                                            if ($item) {
                                                $grossProfit = $lineTotal - ($qty * floatval($item->unit_cost));
                                                $set('gross_profit', $grossProfit);
                                                $set('margin_status', $grossProfit < 0 ? 'negative' : ($grossProfit < ($lineTotal * 0.2) ? 'low' : 'normal'));
                                            }
                                        }
                                    }),
                                TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('FCFA ')
                                    ->label('Unit Price')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $price = floatval($state ?? 0);
                                        $qty = floatval($get('qty_sold') ?? 0);
                                        $lineTotal = $qty * $price;
                                        $set('line_total', $lineTotal);

                                        if ($itemId = $get('item_id')) {
                                            $item = \App\Models\Item::find($itemId);
                                            if ($item) {
                                                $grossProfit = $lineTotal - ($qty * floatval($item->unit_cost));
                                                $set('gross_profit', $grossProfit);
                                                $set('margin_status', $grossProfit < 0 ? 'negative' : ($grossProfit < ($lineTotal * 0.2) ? 'low' : 'normal'));
                                            }
                                        }
                                    }),
                                TextInput::make('line_total')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('FCFA ')
                                    ->default(0.00)
                                    ->label('Line Total'),
                                Hidden::make('gross_profit')
                                    ->default(0.00),
                                Hidden::make('margin_status')
                                    ->default('normal'),
                                Placeholder::make('margin_warning')
                                    ->label('Margin Warning')
                                    ->content(function (callable $get) {
                                        $status = $get('margin_status');
                                        if ($status === 'negative') {
                                            return new \Illuminate\Support\HtmlString('<span style="color: #ef4444; font-weight: bold;">🔴 Negative Margin</span>');
                                        } elseif ($status === 'low') {
                                            return new \Illuminate\Support\HtmlString('<span style="color: #f59e0b; font-weight: bold;">🟡 Low Margin (<20%)</span>');
                                        }
                                        return new \Illuminate\Support\HtmlString('<span style="color: #10b981;">🟢 Healthy Margin</span>');
                                    }),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $lines = $get('salesOrderLines') ?? [];
                        $subtotal = 0;
                        foreach ($lines as $line) {
                            $subtotal += floatval($line['line_total'] ?? 0);
                        }
                        $set('subtotal', $subtotal);
                        $discount = floatval($get('discount_total') ?? 0);
                        $set('grand_total', $subtotal - $discount);
                    }),

                Grid::make(4)
                    ->schema([
                        TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->prefix('FCFA ')
                            ->default(0.00)
                            ->label('Subtotal'),
                        TextInput::make('discount_total')
                            ->required()
                            ->numeric()
                            ->prefix('FCFA ')
                            ->default(0.00)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subtotal = floatval($get('subtotal') ?? 0);
                                $discount = floatval($state ?? 0);
                                $set('grand_total', $subtotal - $discount);
                            })
                            ->label('Discount'),
                        TextInput::make('grand_total')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->prefix('FCFA ')
                            ->default(0.00)
                            ->label('Grand Total'),
                        TextInput::make('order_number')
                            ->required()
                            ->default(fn () => 'SO-' . strtoupper(uniqid()))
                            ->label('Order Number'),
                        Hidden::make('served_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }
}
