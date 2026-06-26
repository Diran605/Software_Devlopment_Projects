<x-filament-panels::page>
    <style>
        .report-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        .report-table th, 
        .report-table td {
            padding: 0.75rem 1rem !important;
        }
        .report-table th {
            font-weight: 600;
            background-color: rgba(39, 39, 42, 0.8) !important;
            color: #e4e4e7 !important;
            border-bottom: 1px solid #3f3f46;
        }
        .report-table td {
            border-bottom: 1px solid #27272a;
            color: #d4d4d8;
        }
        .report-table tbody tr:hover {
            background-color: rgba(39, 39, 42, 0.4);
        }
        .report-table tfoot tr {
            border-top: 2px solid #52525b;
            background-color: rgba(39, 39, 42, 0.8) !important;
            font-weight: bold;
            color: #ffffff !important;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
    </style>
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit.prevent="submit" class="space-y-4">
                {{ $this->form }}
                
                <div class="flex items-center gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">
                        Filter Report
                    </x-filament::button>
                    
                    <x-filament::button wire:click="exportPdf('reports.profit-loss.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="rounded-xl bg-zinc-800/60 border border-zinc-700 p-4">
                <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Revenue</div>
                <div class="text-2xl font-bold text-emerald-400 mt-1">FCFA {{ number_format($reportData['revenue'], 2) }}</div>
                <div class="text-xs text-zinc-500 mt-1">{{ $reportData['order_count'] }} orders</div>
            </div>
            <div class="rounded-xl bg-zinc-800/60 border border-zinc-700 p-4">
                <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Cost of Goods</div>
                <div class="text-2xl font-bold text-rose-400 mt-1">FCFA {{ number_format($reportData['cogs'], 2) }}</div>
                <div class="text-xs text-zinc-500 mt-1">Direct costs</div>
            </div>
            <div class="rounded-xl bg-zinc-800/60 border border-zinc-700 p-4">
                <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Gross Profit</div>
                <div class="text-2xl font-bold {{ $reportData['gross_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }} mt-1">FCFA {{ number_format($reportData['gross_profit'], 2) }}</div>
                <div class="text-xs text-zinc-500 mt-1">{{ $reportData['gross_margin'] }}% margin</div>
            </div>
            <div class="rounded-xl bg-zinc-800/60 border border-zinc-700 p-4">
                <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Expenses</div>
                <div class="text-2xl font-bold text-amber-400 mt-1">FCFA {{ number_format($reportData['total_expenses'], 2) }}</div>
                <div class="text-xs text-zinc-500 mt-1">Operating costs</div>
            </div>
            <div class="rounded-xl bg-zinc-800/60 border {{ $reportData['net_profit'] >= 0 ? 'border-emerald-700/50' : 'border-rose-700/50' }} p-4">
                <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Net Profit</div>
                <div class="text-2xl font-bold {{ $reportData['net_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }} mt-1">FCFA {{ number_format($reportData['net_profit'], 2) }}</div>
                <div class="text-xs text-zinc-500 mt-1">{{ $reportData['net_margin'] }}% margin</div>
            </div>
        </div>

        {{-- Profit & Loss Statement --}}
        <x-filament::card>
            <h3 class="text-lg font-bold text-white mb-4">Profit & Loss Statement</h3>
            <div class="overflow-x-auto">
                <table class="report-table w-full text-left border-collapse text-sm">
                    <tbody class="text-zinc-300">
                        {{-- Revenue Section --}}
                        <tr class="bg-zinc-800/50 border-b border-zinc-700">
                            <td class="p-3 font-bold text-white text-base" colspan="2">Revenue</td>
                        </tr>
                        <tr class="border-b border-zinc-800">
                            <td class="p-3 pl-6">Sales Revenue</td>
                            <td class="p-3 text-right font-semibold text-emerald-400">FCFA {{ number_format($reportData['revenue'], 2) }}</td>
                        </tr>
                        <tr class="border-b border-zinc-700 bg-zinc-800/30">
                            <td class="p-3 pl-4 font-semibold text-white">Total Revenue</td>
                            <td class="p-3 text-right font-bold text-emerald-400">FCFA {{ number_format($reportData['revenue'], 2) }}</td>
                        </tr>

                        {{-- COGS Section --}}
                        <tr class="bg-zinc-800/50 border-b border-zinc-700 mt-2">
                            <td class="p-3 font-bold text-white text-base" colspan="2">Cost of Goods Sold</td>
                        </tr>
                        <tr class="border-b border-zinc-800">
                            <td class="p-3 pl-6">Direct Cost of Items Sold</td>
                            <td class="p-3 text-right font-semibold text-rose-400">(FCFA {{ number_format($reportData['cogs'], 2) }})</td>
                        </tr>
                        <tr class="border-b border-zinc-700 bg-zinc-800/30">
                            <td class="p-3 pl-4 font-semibold text-white">Total COGS</td>
                            <td class="p-3 text-right font-bold text-rose-400">(FCFA {{ number_format($reportData['cogs'], 2) }})</td>
                        </tr>

                        {{-- Gross Profit --}}
                        <tr class="border-b-2 border-zinc-500 bg-zinc-800/60">
                            <td class="p-3 font-bold text-lg text-white">Gross Profit</td>
                            <td class="p-3 text-right font-bold text-lg {{ $reportData['gross_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">FCFA {{ number_format($reportData['gross_profit'], 2) }}</td>
                        </tr>

                        {{-- Operating Expenses --}}
                        <tr class="bg-zinc-800/50 border-b border-zinc-700">
                            <td class="p-3 font-bold text-white text-base" colspan="2">Operating Expenses</td>
                        </tr>
                        @forelse($reportData['expense_breakdown'] as $expense)
                            <tr class="border-b border-zinc-800 hover:bg-zinc-800/40">
                                <td class="p-3 pl-6">{{ $expense->category_name }}</td>
                                <td class="p-3 text-right font-semibold text-amber-400">(FCFA {{ number_format($expense->total_amount, 2) }})</td>
                            </tr>
                        @empty
                            <tr class="border-b border-zinc-800">
                                <td class="p-3 pl-6 text-zinc-500 italic" colspan="2">No expenses recorded for this period.</td>
                            </tr>
                        @endforelse
                        <tr class="border-b border-zinc-700 bg-zinc-800/30">
                            <td class="p-3 pl-4 font-semibold text-white">Total Expenses</td>
                            <td class="p-3 text-right font-bold text-amber-400">(FCFA {{ number_format($reportData['total_expenses'], 2) }})</td>
                        </tr>

                        {{-- Net Profit --}}
                        <tr class="border-t-2 border-zinc-400 bg-zinc-800/80">
                            <td class="p-4 font-bold text-lg text-white">Net Profit / (Loss)</td>
                            <td class="p-4 text-right font-bold text-xl {{ $reportData['net_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                @if($reportData['net_profit'] < 0)
                                    (FCFA {{ number_format(abs($reportData['net_profit']), 2) }})
                                @else
                                    FCFA {{ number_format($reportData['net_profit'], 2) }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::card>

        {{-- Expense Breakdown Table --}}
        @if($reportData['expense_breakdown']->isNotEmpty())
        <x-filament::card>
            <h3 class="text-lg font-bold text-white mb-4">Expense Breakdown by Category</h3>
            <div class="overflow-x-auto">
                <table class="report-table w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700 bg-zinc-800 text-zinc-300">
                            <th class="p-3 font-semibold">Category</th>
                            <th class="p-3 font-semibold text-right">Count</th>
                            <th class="p-3 font-semibold text-right">Amount</th>
                            <th class="p-3 font-semibold text-right">% of Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700 text-zinc-300">
                        @foreach($reportData['expense_breakdown'] as $expense)
                            <tr class="hover:bg-zinc-800/50">
                                <td class="p-3 font-medium">{{ $expense->category_name }}</td>
                                <td class="p-3 text-right">{{ number_format($expense->expense_count) }}</td>
                                <td class="p-3 text-right font-semibold">FCFA {{ number_format($expense->total_amount, 2) }}</td>
                                <td class="p-3 text-right">
                                    @php $pct = $reportData['total_expenses'] > 0 ? round(($expense->total_amount / $reportData['total_expenses']) * 100, 1) : 0; @endphp
                                    <span class="inline-flex items-center gap-1">
                                        {{ $pct }}%
                                        <div class="w-16 h-2 bg-zinc-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-amber-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                        </div>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-zinc-500 bg-zinc-800/80 font-bold text-white">
                            <td class="p-3">Total</td>
                            <td class="p-3 text-right">{{ $reportData['expense_breakdown']->sum('expense_count') }}</td>
                            <td class="p-3 text-right text-amber-400">FCFA {{ number_format($reportData['total_expenses'], 2) }}</td>
                            <td class="p-3 text-right">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
