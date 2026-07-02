<x-filament-panels::page>
    <style>
        .report-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; line-height: 1.25rem; }
        .report-table th, .report-table td { padding: 0.75rem 1rem !important; }
        .report-table th { font-weight: 600; background-color: rgba(39,39,42,0.8) !important; color: #e4e4e7 !important; border-bottom: 1px solid #3f3f46; }
        .report-table td { border-bottom: 1px solid #27272a; color: #d4d4d8; }
        .report-table tbody tr:hover { background-color: rgba(39,39,42,0.4); }
        .report-table tfoot tr { border-top: 2px solid #52525b; background-color: rgba(39,39,42,0.8) !important; font-weight: bold; color: #fff !important; }
        .text-right { text-align: right !important; }
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
                    
                    <x-filament::button wire:click="exportPdf('reports.product-profit-loss.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        @php $data = $reportData; @endphp

        {{-- Summary Cards --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Products</div>
                <div class="text-2xl font-bold text-white mt-1">{{ $data['rows']->count() }}</div>
            </div>
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Total Revenue</div>
                <div class="text-2xl font-bold text-emerald-400 mt-1">FCFA {{ number_format($data['total_revenue'], 0) }}</div>
            </div>
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Total Cost</div>
                <div class="text-2xl font-bold text-rose-400 mt-1">FCFA {{ number_format($data['total_cost'], 0) }}</div>
            </div>
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Net Profit / (Loss)</div>
                <div class="text-2xl font-bold {{ $data['total_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }} mt-1">
                    FCFA {{ number_format($data['total_profit'], 0) }}
                </div>
            </div>
            <div class="bg-zinc-800/60 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Overall Margin</div>
                <div class="text-2xl font-bold {{ $data['overall_margin'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }} mt-1">
                    {{ $data['overall_margin'] }}%
                </div>
            </div>
        </div>

        {{-- Profitable vs Loss-Making --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="bg-emerald-900/30 rounded-xl p-4 border border-emerald-700/40 flex items-center gap-4">
                <div class="text-3xl">✅</div>
                <div>
                    <div class="text-xs text-zinc-400 uppercase font-semibold">Profitable Products</div>
                    <div class="text-3xl font-bold text-emerald-400">{{ $data['profitable_count'] }}</div>
                </div>
            </div>
            <div class="bg-rose-900/30 rounded-xl p-4 border border-rose-700/40 flex items-center gap-4">
                <div class="text-3xl">⚠️</div>
                <div>
                    <div class="text-xs text-zinc-400 uppercase font-semibold">Loss-Making Products</div>
                    <div class="text-3xl font-bold text-rose-400">{{ $data['loss_count'] }}</div>
                </div>
            </div>
        </div>

        {{-- Products Table --}}
        <x-filament::card>
            <h3 class="text-lg font-bold text-white mb-4">
                Product Profit & Loss
                @if($data['filter'] !== 'all')
                    <span class="ml-2 text-sm font-normal px-2 py-1 rounded-full
                        {{ $data['filter'] === 'profit' ? 'bg-emerald-900/50 text-emerald-300' : 'bg-rose-900/50 text-rose-300' }}">
                        {{ match($data['filter']) { 'profit' => '📈 Profitable Only', 'loss' => '📉 Loss-Making Only', 'negative' => '🔴 Negative Margin', default => '' } }}
                    </span>
                @endif
            </h3>

            @if($data['rows']->isEmpty())
                <div class="text-center py-12 text-zinc-500">No data for the selected filters.</div>
            @else
            <div class="overflow-x-auto">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-right">Qty Sold</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-right">Cost (COGS)</th>
                            <th class="text-right">Gross Profit</th>
                            <th class="text-right">Margin %</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['rows'] as $row)
                        <tr @class(['bg-rose-950/20' => $row->is_loss, 'bg-amber-950/20' => $row->is_low_margin && !$row->is_loss])>
                            <td class="font-semibold text-white">{{ $row->item_name }}</td>
                            <td class="text-zinc-400 text-xs">{{ $row->category_name ?? '—' }}</td>
                            <td class="text-right text-sky-300 font-semibold">{{ number_format($row->total_qty) }}</td>
                            <td class="text-right text-emerald-400 font-semibold">{{ number_format($row->total_revenue, 2) }}</td>
                            <td class="text-right text-rose-400">FCFA {{ number_format($row->total_cost, 2) }}</td>
                            <td class="text-right font-bold {{ $row->is_loss ? 'text-rose-400' : 'text-emerald-400' }}">
                                @if($row->is_loss)
                                    (FCFA {{ number_format(abs($row->total_profit), 2) }})
                                @else
                                    FCFA {{ number_format($row->total_profit, 2) }}
                                @endif
                            </td>
                            <td class="text-right">
                                @php $mc = $row->margin_pct < 0 ? 'text-rose-400' : ($row->is_low_margin ? 'text-amber-400' : 'text-emerald-400'); @endphp
                                <span class="{{ $mc }} font-semibold">{{ $row->margin_pct }}%</span>
                            </td>
                            <td class="text-right">
                                @if($row->is_loss)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-300 bg-rose-900/40 px-2 py-1 rounded-full">📉 Loss</span>
                                @elseif($row->is_low_margin)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-300 bg-amber-900/40 px-2 py-1 rounded-full">⚠️ Low Margin</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-300 bg-emerald-900/40 px-2 py-1 rounded-full">✅ Healthy</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-white font-bold">TOTALS</td>
                            <td class="text-right text-emerald-400">FCFA {{ number_format($data['total_revenue'], 2) }}</td>
                            <td class="text-right text-rose-400">FCFA {{ number_format($data['total_cost'], 2) }}</td>
                            <td class="text-right {{ $data['total_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                @if($data['total_profit'] < 0)
                                    (FCFA {{ number_format(abs($data['total_profit']), 2) }})
                                @else
                                    FCFA {{ number_format($data['total_profit'], 2) }}
                                @endif
                            </td>
                            <td class="text-right {{ $data['overall_margin'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $data['overall_margin'] }}%
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
