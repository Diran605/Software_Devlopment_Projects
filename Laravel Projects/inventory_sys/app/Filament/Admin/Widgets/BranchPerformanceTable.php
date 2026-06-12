<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Branch;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class BranchPerformanceTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Branch::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Branch')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('revenue')
                    ->label("Today's Revenue")
                    ->state(function ($record) {
                        $rev = SalesOrder::where('branch_id', $record->id)
                            ->whereDate('sold_at', today())
                            ->sum('grand_total');
                        return number_format($rev, 0) . ' XAF';
                    }),
                Tables\Columns\TextColumn::make('orders')
                    ->label('Orders Today')
                    ->state(fn ($record) => 
                        SalesOrder::where('branch_id', $record->id)
                            ->whereDate('sold_at', today())
                            ->count()
                    ),
                Tables\Columns\TextColumn::make('top_item')
                    ->label('Top Product (Revenue)')
                    ->state(function ($record) {
                        $topLine = SalesOrderLine::query()
                            ->select('sales_order_lines.item_id', DB::raw('SUM(sales_order_lines.line_total) as total_line'), DB::raw('MIN(sales_order_lines.id) as min_id'))
                            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
                            ->where('sales_orders.branch_id', $record->id)
                            ->whereDate('sales_orders.sold_at', today())
                            ->whereNull('sales_order_lines.deleted_at')
                            ->groupBy('sales_order_lines.item_id')
                            ->orderByDesc('total_line')
                            ->orderBy('min_id')
                            ->first();
                        if ($topLine) {
                            $item = Item::find($topLine->item_id);
                            return $item ? "{$item->name} (" . number_format($topLine->total_line, 0) . " XAF)" : '—';
                        }
                        return '—';
                    }),
            ])
            ->paginated(false);
    }
}
