<?php

namespace App\Filament\App\Widgets;

use App\Models\SalesOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Facades\Filament;

class RecentSalesTable extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $branchId = $tenant ? $tenant->id : null;
        
        return $table
            ->query(
                SalesOrder::query()
                    ->where('branch_id', $branchId)
                    ->latest('sold_at')
                    ->limit(10)
                    ->with(['customer', 'servedBy'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->url(fn ($record) => route('filament.app.resources.sales-orders.view', [
                        'tenant' => Filament::getTenant()?->code,
                        'record' => $record->id,
                    ])),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->placeholder('Walk-in Customer'),
                Tables\Columns\TextColumn::make('sales_order_lines_count')
                    ->counts('salesOrderLines')
                    ->label('No. of Items')
                    ->numeric(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('XAF'),
                Tables\Columns\TextColumn::make('servedBy.name')
                    ->label('Served By'),
                Tables\Columns\TextColumn::make('sold_at')
                    ->label('Sold At')
                    ->dateTime('M d, Y H:i'),
            ])
            ->paginated(false);
    }
}
