<?php

namespace App\Filament\App\Resources\OpeningStocks\Schemas;

use App\Models\Item;
use App\Models\PackagingType;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OpeningStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        DateTimePicker::make('posted_at')
                            ->required()
                            ->default(now()),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->nullable(),
                        Textarea::make('notes')
                            ->columnSpan(1)
                            ->rows(1),
                    ]),

                Repeater::make('openingStockLines')
            ->schema([
                Hidden::make('id'), // Preserve the line ID
                Grid::make(6)
                    ->schema([
                        Select::make('item_id')
                                    ->options(function (): array {
                                        $tenantId = Filament::getTenant()?->id;

                                        return Item::query()
                                            ->when($tenantId, fn ($query) => $query->where('branch_id', $tenantId))
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->disabled(fn (callable $get): bool => filled($get('id')))
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        if ($state) {
                                            $item = Item::with('packagingType')->find($state);
                                            if ($item?->packaging_type_id) {
                                                $set('packaging_type_id', $item->packaging_type_id);
                                                $set('units_per_pack', $item->packagingType?->units_per_pack ?? 1);
                                            }
                                        }
                                    }),
                                TextInput::make('batch_number')
                                    ->required(),
                                DatePicker::make('expiry_date')
                                    ->nullable(),
                                Toggle::make('entry_mode')
                                    ->label('Enter as Packages')
                                    ->formatStateUsing(fn ($state) => $state === 'pack')
                                    ->dehydrateStateUsing(fn ($state) => $state ? 'pack' : 'unit')
                                    ->default(false)
                                    ->live(),
                                Select::make('packaging_type_id')
                                    ->options(function (): array {
                                        $tenantId = Filament::getTenant()?->id;

                                        return PackagingType::query()
                                            ->when($tenantId, fn ($q) => $q->where(fn ($inner) => $inner->where('branch_id', $tenantId)->orWhereNull('branch_id')))
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->nullable()
                                    ->searchable()
                                    ->live()
                                    ->label('Packaging Type')
                                    ->visible(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if ($state) {
                                            $pack = PackagingType::find($state);
                                            if ($pack) {
                                                $set('units_per_pack', $pack->units_per_pack);
                                                $packQty = floatval($get('pack_quantity') ?? 0);
                                                if ($packQty > 0) {
                                                    $set('qty_on_hand', (int) ($packQty * $pack->units_per_pack));
                                                }
                                            }
                                        }
                                    }),
                                TextInput::make('pack_quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->label('Pack Qty')
                                    ->visible(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        $units = floatval($get('units_per_pack') ?? 1);
                                        $set('qty_on_hand', (int) (floatval($state ?? 0) * $units));
                                    }),
                                TextInput::make('units_per_pack')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->label('Units per Pack')
                                    ->visible(fn (callable $get) => $get('entry_mode'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        $packs = floatval($get('pack_quantity') ?? 0);
                                        $set('qty_on_hand', (int) ($packs * floatval($state ?? 1)));
                                    }),
                                TextInput::make('qty_on_hand')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('Qty On Hand (Base Units)')
                                    ->readOnly(fn (callable $get) => $get('entry_mode')),
                                TextInput::make('unit_cost')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Unit Cost'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->minItems(1),
            ]);
    }
}
