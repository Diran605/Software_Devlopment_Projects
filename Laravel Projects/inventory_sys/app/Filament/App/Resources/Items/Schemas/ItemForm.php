<?php

namespace App\Filament\App\Resources\Items\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ItemForm
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
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Category'),
                        Select::make('uom_id')
                            ->relationship('uom', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Unit of Measure'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('unit_cost')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('FCFA ')
                            ->label('Reference Cost Price')
                            ->helperText('This is a reference price only. Actual acquisition cost is recorded on each Opening Stock entry or GRN.'),
                        TextInput::make('min_selling_price')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('FCFA ')
                            ->label('Min Selling Price'),
                        TextInput::make('selling_price')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('FCFA ')
                            ->label('Selling Price'),
                        TextInput::make('reorder_level')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->label('Reorder Level'),
                        TextInput::make('reorder_quantity')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->label('Reorder Quantity'),
                        Toggle::make('is_packaged')
                            ->label('Is Packaged')
                            ->live()
                            ->default(false),
                        Select::make('packaging_type_id')
                            ->relationship('packagingType', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Packaging Type')
                            ->visible(fn ($get) => $get('is_packaged')),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        \Filament\Forms\Components\Placeholder::make('stock_breakdown')
                            ->label('Current Stock Breakdown')
                            ->content(function ($record) {
                                if (!$record) {
                                    return 'No stock recorded yet (New item).';
                                }
                                $tenant = \Filament\Facades\Filament::getTenant();
                                if (!$tenant) {
                                    return 'N/A';
                                }
                                $levels = $record->itemStockLevels()
                                    ->where('branch_id', $tenant->id)
                                    ->with('department')
                                    ->get();

                                if ($levels->isEmpty()) {
                                    return 'No stock recorded for this branch.';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    $levels->map(function ($lvl) {
                                        $deptName = $lvl->department?->name ?? 'Main/Default';
                                        return "<strong>{$deptName}</strong>: {$lvl->qty_on_hand} units on hand (Available: {$lvl->qty_available}, Reserved: {$lvl->qty_reserved})";
                                    })->implode('<br>')
                                );
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
