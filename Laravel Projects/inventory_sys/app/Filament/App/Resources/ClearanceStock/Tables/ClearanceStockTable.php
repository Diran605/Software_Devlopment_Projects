<?php

namespace App\Filament\App\Resources\ClearanceStock\Tables;

use App\Filament\Concerns\HasClearanceStockReversalAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClearanceStockTable
{
    use HasClearanceStockReversalAction;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable(),
                TextColumn::make('batch_number')
                    ->label('Batch #'),
                TextColumn::make('expiry_date')
                    ->date(),
                TextColumn::make('days_to_expiry')
                    ->label('Days to Expiry')
                    ->getStateUsing(fn ($record) => $record->expiry_date ? \Illuminate\Support\Carbon::parse($record->expiry_date)->diffInDays(now(), false) : null),
                TextColumn::make('qty_on_clearance'),
                TextColumn::make('qty_remaining'),
                TextColumn::make('original_price')
                    ->money('xaf'),
                TextColumn::make('clearance_price')
                    ->money('xaf'),
                TextColumn::make('unit_cost')
                    ->money('xaf'),
                BadgeColumn::make('status')
                    ->getStateUsing(fn ($record) => $record->qty_remaining > 0 ? 'Active' : 'Depleted')
                    ->colors([
                        'info' => 'Active',
                        'gray' => 'Depleted',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                static::makeClearanceStockReversalAction(),
            ])
            ->emptyStateHeading('No clearance stock yet');
    }
}
