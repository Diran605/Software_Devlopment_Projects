<x-filament-panels::page>
    <style>
        .report-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; line-height: 1.25rem; }
        .report-table th, .report-table td { padding: 0.75rem 1rem !important; }
        .report-table th { font-weight: 600; background-color: rgba(39,39,42,0.8) !important; color: #e4e4e7 !important; border-bottom: 1px solid #3f3f46; }
        .report-table td { border-bottom: 1px solid #27272a; color: #d4d4d8; }
        .report-table tbody tr:hover { background-color: rgba(39,39,42,0.4); }
        .report-table tfoot tr { border-top: 2px solid #52525b; background-color: rgba(39,39,42,0.8) !important; font-weight: bold; color: #fff !important; }
        .text-right { text-align: right !important; }
        .rank-badge { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; font-weight: bold; font-size: 0.75rem; }
        .rank-1 { background: #f59e0b; color: #000; }
        .rank-2 { background: #9ca3af; color: #000; }
        .rank-3 { background: #b45309; color: #fff; }
        .rank-other { background: rgba(63,63,70,0.8); color: #d4d4d8; }
        .bar-container { width: 80px; height: 8px; background: rgba(63,63,70,0.6); border-radius: 4px; overflow: hidden; display: inline-block; vertical-align: middle; margin-left: 6px; }
        .bar-fill { height: 100%; border-radius: 4px; background: #10b981; }
    </style>

    <div class="space-y-6">
        {{-- Filter Form --}}
        <x-filament::card>
            <form wire:submit.prevent="submit" class="space-y-4">
                {{ $this->form }}
                <div class="flex items-center gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">
                        Apply Filters
                    </x-filament::button>
                    
                    <x-filament::button type="button" wire:click="exportPdf('reports.trending-products.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportCsv('trending-products-report')" color="info" icon="heroicon-o-table-cells">
                        Export CSV
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportExcel('trending-products-report')" color="warning" icon="heroicon-o-document-text">
                        Export Excel
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        @php
            $data = $reportData;
            $sortLabel = match($data['sort_by']) {
                'qty'    => 'Qty Sold',
                'profit' => 'Gross Profit',
                'orders' => 'Order Count',
                default  => 'Revenue',
            };
        @endphp

        {{-- Summary Cards --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Products Shown</div>
                <div class="text-2xl font-bold text-white mt-1">{{ $data['products']->count() }}</div>
            </div>
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Total Revenue</div>
                <div class="text-2xl font-bold text-emerald-400 mt-1">FCFA {{ number_format($data['grand_revenue'], 0) }}</div>
            </div>
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Total Qty Sold</div>
                <div class="text-2xl font-bold text-sky-400 mt-1">{{ number_format($data['grand_qty']) }}</div>
            </div>
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Total Profit</div>
                <div class="text-2xl font-bold {{ $data['grand_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }} mt-1">
                    FCFA {{ number_format($data['grand_profit'], 0) }}
                </div>
            </div>
        </div>

        {{-- Products Table --}}
        <x-filament::card>
            <h3 class="text-lg font-bold text-white mb-4">🔥 Trending Products — Ranked by {{ $sortLabel }}</h3>
            @if($data['products']->isEmpty())
                <div class="text-center py-12 text-zinc-500">No sales data found for the selected period.</div>
            @else
            <div class="overflow-x-auto">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                            <th style="width:48px">#</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-right">Qty Sold</th>
                            <th class="text-right">Avg Price</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-right">Cost</th>
                            <th class="text-right">Gross Profit</th>
                            <th class="text-right">Margin</th>
                            <th class="text-right">Revenue Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['products'] as $row)
                        <tr>
                            <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td>
                                @php $rankClass = match(true) { $row->rank === 1 => 'rank-1', $row->rank === 2 => 'rank-2', $row->rank === 3 => 'rank-3', default => 'rank-other' }; @endphp
                                <span class="rank-badge {{ $rankClass }}">{{ $row->rank }}</span>
                            </td>
                            <td class="font-semibold text-white">{{ $row->item_name }}</td>
                            <td class="text-zinc-400 text-xs">{{ $row->category_name ?? '—' }}</td>
                            <td class="text-right text-sky-300 font-semibold">{{ number_format($row->total_qty) }}</td>
                            <td class="text-right text-zinc-300">FCFA {{ number_format($row->avg_price, 0) }}</td>
                            <td class="text-right text-emerald-400 font-semibold">FCFA {{ number_format($row->total_revenue, 0) }}</td>
                            <td class="text-right text-rose-400">FCFA {{ number_format($row->total_cost, 0) }}</td>
                            <td class="text-right font-bold {{ $row->total_profit >= 0 ? 'text-emerald-400' : 'text-rose-500' }}">
                                FCFA {{ number_format($row->total_profit, 0) }}
                            </td>
                            <td class="text-right">
                                @php
                                    $mc = $row->margin_pct < 0 ? 'text-rose-400' : ($row->margin_pct < 20 ? 'text-amber-400' : 'text-emerald-400');
                                @endphp
                                <span class="{{ $mc }} font-semibold">{{ $row->margin_pct }}%</span>
                            </td>
                            <td class="text-right">
                                <span class="text-zinc-300 text-xs">{{ $row->revenue_share }}%</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width:{{ min($row->revenue_share, 100) }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-white font-bold">TOTALS</td>
                            <td class="text-right text-sky-300">{{ number_format($data['grand_qty']) }}</td>
                            <td></td>
                            <td class="text-right text-emerald-400">FCFA {{ number_format($data['grand_revenue'], 0) }}</td>
                            <td></td>
                            <td class="text-right {{ $data['grand_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                FCFA {{ number_format($data['grand_profit'], 0) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
