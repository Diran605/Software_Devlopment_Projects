<?php

namespace App\Filament\App\Resources\GoodsReceivedNotes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceivedNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->label('Supplier'),
                        DateTimePicker::make('received_at')
                            ->default(now())
                            ->required()
                            ->label('Received At'),
                        Select::make('purchase_order_id')
                            ->relationship('purchaseOrder', 'po_number', modifyQueryUsing: fn (Builder $query, callable $get) =>
                                $query->whereIn('status', ['issued', 'partially_received'])
                                    ->when($get('supplier_id'), fn ($q, $sup) => $q->where('supplier_id', $sup))
                            )
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $po = \App\Models\PurchaseOrder::with('purchaseOrderLines')->find($state);
                                    if ($po) {
                                        $set('supplier_id', $po->supplier_id);
                                        $lines = [];
                                        foreach ($po->purchaseOrderLines as $poLine) {
                                            $remaining = $poLine->qty_ordered - $poLine->qty_received;
                                            if ($remaining > 0) {
                                                $lines[] = [
                                                    'item_id' => $poLine->item_id,
                                                    'entry_mode' => 'unit',
                                                    'pack_quantity' => 0,
                                                    'units_per_pack' => 1,
                                                    'qty_received' => $remaining,
                                                    'unit_cost' => $poLine->unit_cost,
                                                    'line_total' => $remaining * $poLine->unit_cost,
                                                    'batch_number' => 'BCH-' . strtoupper(uniqid()),
                                                    'expiry_date' => null,
                                                ];
                                            }
                                        }
                                        $set('grnLineItems', $lines);
                                        $set('total_qty', array_sum(array_column($lines, 'qty_received')));
                                        $set('total_cost', array_sum(array_column($lines, 'line_total')));
                                    }
                                }
                            })
                            ->label('Purchase Order'),
                        TextInput::make('supplier_reference_no')
                            ->label('Supplier Reference No'),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->nullable()
                            ->label('Department'),
                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                Repeater::make('grnLineItems')
                    ->schema([
                        Grid::make(5)
                            ->schema([
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
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $item = \App\Models\Item::find($state);
                                            if ($item) {
                                                 $set('unit_cost', $item->unit_cost);
                                             }
                                         }
                                    }),
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
                                        $set('qty_received', $qty);
                                        $set('line_total', $qty * floatval($get('unit_cost') ?? 0));
                                    }),
                                TextInput::make('units_per_pack')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->visible(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $packs = floatval($get('pack_quantity') ?? 0);
                                        $qty = $packs * floatval($state ?? 1);
                                        $set('qty_received', $qty);
                                        $set('line_total', $qty * floatval($get('unit_cost') ?? 0));
                                    }),
                                TextInput::make('qty_received')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->live()
                                    ->label('Qty Received')
                                    ->readOnly(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = floatval($state ?? 0);
                                        $set('line_total', $qty * floatval($get('unit_cost') ?? 0));
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
                                        $qty = floatval($get('qty_received') ?? 0);
                                        $set('line_total', $qty * $cost);
                                    }),
                                TextInput::make('line_total')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('FCFA ')
                                    ->default(0.00)
                                    ->label('Line Total'),
                                TextInput::make('batch_number')
                                    ->required()
                                    ->default(fn () => 'BCH-' . strtoupper(uniqid()))
                                    ->label('Batch Number'),
                                DatePicker::make('expiry_date')
                                    ->nullable()
                                    ->label('Expiry Date'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $lines = $get('grnLineItems') ?? [];
                        $totalQty = 0;
                        $totalCost = 0;
                        foreach ($lines as $line) {
                            $qty = floatval($line['qty_received'] ?? 0);
                            $cost = floatval($line['unit_cost'] ?? 0);
                            $totalQty += $qty;
                            $totalCost += $qty * $cost;
                        }
                        $set('total_qty', $totalQty);
                        $set('total_cost', $totalCost);
                    }),

                Grid::make(3)
                    ->schema([
                        TextInput::make('total_qty')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->label('Total Quantity'),
                        TextInput::make('total_cost')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->prefix('FCFA ')
                            ->default(0.00)
                            ->label('Total Cost'),
                        TextInput::make('grn_number')
                            ->required()
                            ->default(fn () => 'GRN-' . strtoupper(uniqid()))
                            ->label('GRN Number'),
                        Hidden::make('received_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }
}
