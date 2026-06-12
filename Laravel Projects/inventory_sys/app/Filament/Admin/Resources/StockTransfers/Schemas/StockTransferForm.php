<?php

namespace App\Filament\Admin\Resources\StockTransfers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('transfer_type')
                            ->options([
                                'inter_department' => 'Inter-Department',
                                'inter_branch' => 'Inter-Branch',
                            ])
                            ->required()
                            ->live()
                            ->default('inter_department')
                            ->label('Transfer Type'),
                        Select::make('from_branch_id')
                            ->relationship('fromBranch', 'name')
                            ->required()
                            ->live()
                            ->label('From Branch'),
                        Select::make('from_department_id')
                            ->relationship('fromDepartment', 'name', modifyQueryUsing: fn (Builder $query, callable $get) =>
                                $query->where('branch_id', $get('from_branch_id'))
                            )
                            ->nullable()
                            ->live()
                            ->label('From Department'),
                        Select::make('to_branch_id')
                            ->relationship('toBranch', 'name')
                            ->required(fn (callable $get) => $get('transfer_type') === 'inter_branch')
                            ->visible(fn (callable $get) => $get('transfer_type') === 'inter_branch')
                            ->live()
                            ->label('To Branch'),
                        Select::make('to_department_id')
                            ->relationship('toDepartment', 'name', modifyQueryUsing: fn (Builder $query, callable $get) =>
                                $query->where('branch_id', $get('to_branch_id') ?? $get('from_branch_id'))
                            )
                            ->nullable()
                            ->live()
                            ->label('To Department'),
                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                Repeater::make('stockTransferLines')
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                Select::make('item_id')
                                    ->options(function (callable $get) {
                                        $branchId = $get('../../from_branch_id');
                                        return \App\Models\Item::query()
                                            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->label('Item'),
                                Select::make('batch_inventory_id')
                                    ->options(function (callable $get) {
                                        $itemId = $get('item_id');
                                        $fromBranchId = $get('../../from_branch_id');
                                        $fromDeptId = $get('../../from_department_id');

                                        if (!$itemId || !$fromBranchId) {
                                            return [];
                                        }

                                        $query = \App\Models\BatchInventory::where('item_id', $itemId)
                                            ->where('branch_id', $fromBranchId)
                                            ->where('qty_remaining', '>', 0);

                                        if ($fromDeptId) {
                                            $query->where('department_id', $fromDeptId);
                                        } else {
                                            $query->whereNull('department_id');
                                        }

                                        return $query->get()->mapWithKeys(function ($batch) {
                                            $expiry = $batch->expiry_date ? " (Exp: {$batch->expiry_date})" : "";
                                            return [$batch->id => "{$batch->batch_number} - Qty: {$batch->qty_remaining}{$expiry}"];
                                        });
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $batch = \App\Models\BatchInventory::find($state);
                                            if ($batch) {
                                                $set('batch_number', $batch->batch_number);
                                                $set('expiry_date', $batch->expiry_date);
                                                $set('unit_cost', $batch->unit_cost);
                                            }
                                        }
                                    })
                                    ->label('Batch'),
                                TextInput::make('qty_requested')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->label('Qty Requested'),
                                TextInput::make('unit_cost')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('FCFA ')
                                    ->label('Unit Cost'),
                                TextInput::make('batch_number')
                                    ->readOnly()
                                    ->label('Batch Number'),
                                DatePicker::make('expiry_date')
                                    ->readOnly()
                                    ->label('Expiry Date'),
                            ]),
                    ])
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        TextInput::make('transfer_number')
                            ->required()
                            ->default(fn () => 'TRF-' . strtoupper(uniqid()))
                            ->label('Transfer Number'),
                        Hidden::make('requested_by')
                            ->default(fn () => auth()->id()),
                        Hidden::make('branch_id')
                            ->default(fn (callable $get) => $get('from_branch_id')),
                    ]),
            ]);
    }
}
