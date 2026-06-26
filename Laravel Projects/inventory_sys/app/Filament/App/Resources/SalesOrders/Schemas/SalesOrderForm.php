<?php

namespace App\Filament\App\Resources\SalesOrders\Schemas;

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

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
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
                            ->relationship('department', 'name')
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
                                Select::make('clearance_stock_id')
                                    ->label('Clearance Stock')
                                    ->options(function () {
                                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;

                                        return \App\Models\ClearanceStock::query()
                                            ->where('branch_id', $tenantId)
                                            ->where('qty_remaining', '>', 0)
                                            ->with('item')
                                            ->get()
                                            ->mapWithKeys(function ($stock) {
                                                $expiry = \App\Support\FormatsDates::formatDate($stock->expiry_date);

                                                return [$stock->id => "{$stock->item->name} | Batch: {$stock->batch_number} | Exp: {$expiry} | Price: FCFA ".number_format($stock->clearance_price, 0)." | Qty: {$stock->qty_remaining}"];
                                            });
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->live()
                                    ->columnSpan(2)
                                    ->disabled(fn (callable $get) => filled($get('id')))
                                    ->helperText('Select clearance stock to sell at the discounted clearance price')
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $stock = \App\Models\ClearanceStock::find($state);
                                            if ($stock) {
                                                $set('item_id', $stock->item_id);
                                                $set('batch_inventory_id', $stock->batch_inventory_id);
                                                $set('unit_price', $stock->clearance_price);
                                                $set('qty_sold', min((int) ($get('qty_sold') ?: 1), $stock->qty_remaining));

                                                $qty = min((int) ($get('qty_sold') ?: 1), $stock->qty_remaining);
                                                $set('line_total', $qty * $stock->clearance_price);
                                            }
                                        } else {
                                            $set('batch_inventory_id', null);
                                        }
                                    }),
                                Placeholder::make('clearance_batch_display')
                                    ->label('Clearance Batch')
                                    ->visible(fn (callable $get) => filled($get('clearance_stock_id')))
                                    ->content(function (callable $get) {
                                        $stock = \App\Models\ClearanceStock::find($get('clearance_stock_id'));

                                        if (! $stock) {
                                            return '—';
                                        }

                                        $expiry = \App\Support\FormatsDates::formatDate($stock->expiry_date);

                                        return "Batch: {$stock->batch_number} | Exp: {$expiry} | Available: {$stock->qty_remaining}";
                                    }),
                                Select::make('item_id')
                                    ->options(function () {
                                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                                        return \App\Models\Item::query()
                                            ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->label('Item')
                                    ->disabled(fn (callable $get) => filled($get('id')) || filled($get('clearance_stock_id')))
                                    ->helperText(function (callable $get) {
                                        $itemId = $get('item_id');
                                        if ($itemId) {
                                            $tenantId = \Filament\Facades\Filament::getTenant()->id;
                                            $stock = \App\Models\ItemStockLevel::where('item_id', $itemId)
                                                ->where('branch_id', $tenantId)
                                                ->value('qty_on_hand') ?? 0;
                                            return "Qty on hand: {$stock}";
                                        }
                                        return null;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                             $item = \App\Models\Item::with('packagingType')->find($state);
                                             if ($item) {
                                                 $set('unit_price', $item->selling_price);
                                                 if ($item->packaging_type_id) {
                                                     $set('packaging_type_id', $item->packaging_type_id);
                                                     $set('units_per_pack', $item->packagingType?->units_per_pack ?? 1);
                                                 }
                                             }
                                         }
                                         $set('batch_inventory_id', null);
                                         $set('clearance_stock_id', null);
                                     }),
                                Select::make('batch_inventory_id')
                                    ->label('Batch (Optional)')
                                    ->options(function (callable $get) {
                                        $itemId = $get('item_id');
                                        if (!$itemId) return [];
                                        
                                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                                        $selectedBatchId = $get('batch_inventory_id');

                                        $batches = \App\Models\BatchInventory::query()
                                            ->where('item_id', $itemId)
                                            ->when($tenantId, fn ($q) => $q->where('branch_id', $tenantId))
                                            ->where(function ($query) use ($selectedBatchId) {
                                                $query->where('qty_remaining', '>', 0);

                                                if ($selectedBatchId) {
                                                    $query->orWhere('id', $selectedBatchId);
                                                }
                                            })
                                            ->get();

                                        return $batches->mapWithKeys(function ($batch) {
                                                $expiry = $batch->expiry_date ? " | Exp: " . $batch->expiry_date->format('Y-m-d') : '';
                                                $qtyLabel = $batch->qty_remaining > 0
                                                    ? "Qty: {$batch->qty_remaining}"
                                                    : 'Clearance batch';

                                                return [$batch->id => "Batch: {$batch->batch_number}{$expiry} ({$qtyLabel})"];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live()
                                    ->visible(fn (callable $get) => ! filled($get('clearance_stock_id')))
                                    ->disabled(fn (callable $get) => filled($get('id'))),
                                Toggle::make('entry_mode')
                                    ->label('Pack Mode')
                                    ->formatStateUsing(fn ($state) => $state === 'pack')
                                    ->dehydrateStateUsing(fn ($state) => $state ? 'pack' : 'unit')
                                    ->default(false)
                                    ->live(),
                                Select::make('packaging_type_id')
                                    ->options(function (callable $get) {
                                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;

                                        return \App\Models\PackagingType::query()
                                            ->when($tenantId, fn ($q) => $q->where(fn ($inner) => $inner->where('branch_id', $tenantId)->orWhereNull('branch_id')))
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->nullable()
                                    ->searchable()
                                    ->live()
                                    ->label('Packaging Type')
                                    ->visible(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $pack = \App\Models\PackagingType::find($state);
                                            if ($pack) {
                                                $set('units_per_pack', $pack->units_per_pack);
                                                $packQty = floatval($get('pack_quantity') ?? 0);
                                                if ($packQty > 0) {
                                                    $set('qty_sold', $packQty * $pack->units_per_pack);
                                                }
                                            }
                                        }
                                    }),
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
                                    ->maxValue(function (callable $get) {
                                        if ($clearanceStockId = $get('clearance_stock_id')) {
                                            return \App\Models\ClearanceStock::find($clearanceStockId)?->qty_remaining;
                                        }

                                        return null;
                                    })
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
