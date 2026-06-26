<?php

namespace App\Filament\App\Widgets;

use App\Models\BatchInventory;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NearExpiryAlert extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $branchId = Filament::getTenant()?->id;
        $thresholdDays = 30;

        return $table
            ->heading('⚠️ Items Nearing Expiry')
            ->query(
                BatchInventory::query()
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->where('qty_remaining', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', now()->addDays($thresholdDays))
                    ->with(['item', 'item.category'])
                    ->orderBy('expiry_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch #'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date('M d, Y')
                    ->color(fn ($record) => $record->expiry_date?->isPast() ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('days_left')
                    ->label('Days Left')
                    ->state(fn ($record) => (int) now()->startOfDay()->diffInDays($record->expiry_date?->startOfDay(), false))
                    ->badge()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 7 ? 'danger' : 'warning')),
                Tables\Columns\TextColumn::make('qty_remaining')
                    ->label('Qty Remaining')
                    ->numeric(),
                Tables\Columns\TextColumn::make('item.category.name')
                    ->label('Category')
                    ->placeholder('—'),
            ])
            ->paginated(false)
            ->emptyStateHeading('✅ No batches nearing expiry in the next 30 days.');
    }
}
