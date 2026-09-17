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
                
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem; flex-wrap: wrap;">
                    <x-filament::button type="submit" color="primary">
                        Filter Report
                    </x-filament::button>
                    
                    <x-filament::button type="button" wire:click="exportPdf('reports.profit-loss.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportCsv('profit-loss-report')" color="info" icon="heroicon-o-table-cells">
                        Export CSV
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportExcel('profit-loss-report')" color="warning" icon="heroicon-o-document-text">
                        Export Excel
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Summary Cards --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="background: rgba(39,39,42,0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #3f3f46;">
                <div style="font-size: 0.75rem; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Revenue</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #34d399; margin-top: 0.25rem;">FCFA {{ number_format($reportData['revenue'], 2) }}</div>
                <div style="font-size: 0.75rem; color: #71717a; margin-top: 0.25rem;">{{ $reportData['order_count'] }} orders</div>
            </div>
            <div style="background: rgba(39,39,42,0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #3f3f46;">
                <div style="font-size: 0.75rem; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Cost of Goods</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #fb7185; margin-top: 0.25rem;">FCFA {{ number_format($reportData['cogs'], 2) }}</div>
                <div style="font-size: 0.75rem; color: #71717a; margin-top: 0.25rem;">Direct costs</div>
            </div>
            <div style="background: rgba(39,39,42,0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #3f3f46;">
                <div style="font-size: 0.75rem; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Gross Profit</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: {{ $reportData['gross_profit'] >= 0 ? '#34d399' : '#fb7185' }}; margin-top: 0.25rem;">FCFA {{ number_format($reportData['gross_profit'], 2) }}</div>
                <div style="font-size: 0.75rem; color: #71717a; margin-top: 0.25rem;">{{ $reportData['gross_margin'] }}% margin</div>
            </div>
            <div style="background: rgba(39,39,42,0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #3f3f46;">
                <div style="font-size: 0.75rem; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Expenses</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #fbbf24; margin-top: 0.25rem;">FCFA {{ number_format($reportData['total_expenses'], 2) }}</div>
                <div style="font-size: 0.75rem; color: #71717a; margin-top: 0.25rem;">Operating costs</div>
            </div>
            <div style="background: rgba(39,39,42,0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid {{ $reportData['net_profit'] >= 0 ? 'rgba(4,120,87,0.5)' : 'rgba(190,18,60,0.5)' }};">
                <div style="font-size: 0.75rem; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Net Profit</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: {{ $reportData['net_profit'] >= 0 ? '#34d399' : '#fb7185' }}; margin-top: 0.25rem;">FCFA {{ number_format($reportData['net_profit'], 2) }}</div>
                <div style="font-size: 0.75rem; color: #71717a; margin-top: 0.25rem;">{{ $reportData['net_margin'] }}% margin</div>
            </div>
        </div>

        {{-- Profit & Loss Statement --}}
        <x-filament::card>
            <h3 class="text-lg font-bold text-white mb-4">Profit & Loss Statement</h3>
            <div class="overflow-x-auto">
                <table class="report-table w-full text-left border-collapse text-sm">
                    <tbody class="text-zinc-300">
                        {{-- Revenue Section --}}
                        <tr style="background: rgba(39,39,42,0.5); border-bottom: 1px solid #3f3f46;">
                            <td style="padding: 0.75rem; font-weight: bold; color: #fff; font-size: 1rem;" colspan="3">Revenue</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #27272a;">
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1.5rem;">Sales Revenue</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #34d399;">FCFA {{ number_format($reportData['revenue'], 2) }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #3f3f46; background: rgba(39,39,42,0.3);">
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1rem; font-weight: 600; color: #fff;">Total Revenue</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: bold; color: #34d399;">FCFA {{ number_format($reportData['revenue'], 2) }}</td>
                        </tr>

                        {{-- COGS Section --}}
                        <tr style="background: rgba(39,39,42,0.5); border-bottom: 1px solid #3f3f46;">
                            <td style="padding: 0.75rem; font-weight: bold; color: #fff; font-size: 1rem;" colspan="3">Cost of Goods Sold</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #27272a;">
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1.5rem;">Direct Cost of Items Sold</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #fb7185;">(FCFA {{ number_format($reportData['cogs'], 2) }})</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #3f3f46; background: rgba(39,39,42,0.3);">
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1rem; font-weight: 600; color: #fff;">Total COGS</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: bold; color: #fb7185;">(FCFA {{ number_format($reportData['cogs'], 2) }})</td>
                        </tr>

                        {{-- Gross Profit --}}
                        <tr style="border-bottom: 2px solid #71717a; background: rgba(39,39,42,0.6);">
                            <td style="padding: 0.75rem; font-weight: bold; font-size: 1.125rem; color: #fff;">Gross Profit</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: bold; font-size: 1.125rem; color: {{ $reportData['gross_profit'] >= 0 ? '#34d399' : '#fb7185' }};">FCFA {{ number_format($reportData['gross_profit'], 2) }}</td>
                        </tr>

                        {{-- Losses Section (negative-margin sales lines) --}}
                        @if($reportData['loss_lines']->isNotEmpty())
                        <tr style="background: rgba(76,5,25,0.4); border-bottom: 1px solid #3f3f46;">
                            <td style="padding: 0.75rem; font-weight: bold; color: #fff; font-size: 1rem;" colspan="3">
                                ⚠️ Loss-Making Sales (sold below cost)
                            </td>
                        </tr>
                        @foreach($reportData['loss_lines'] as $lossLine)
                        <tr style="background: rgba(76,5,25,0.2); border-bottom: 1px solid #27272a;">
                            <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1.5rem;">
                                <span style="color: #fda4af; font-weight: 500;">{{ $lossLine->item_name }}</span>
                                <span style="color: #71717a; font-size: 0.75rem; margin-left: 0.5rem;">{{ $lossLine->category_name }}</span>
                                <span style="color: #71717a; font-size: 0.75rem; margin-left: 0.5rem;">— Qty: {{ number_format($lossLine->total_qty) }}</span>
                            </td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #fb7185;">
                                (FCFA {{ number_format(abs($lossLine->total_loss), 2) }})
                            </td>
                        </tr>
                        @endforeach
                        <tr style="border-bottom: 1px solid #3f3f46; background: rgba(136,19,55,0.3);">
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1rem; font-weight: 600; color: #fff;">Total Losses from Underselling</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: bold; color: #fb7185;">(FCFA {{ number_format(abs($reportData['total_loss']), 2) }})</td>
                        </tr>
                        @endif

                        {{-- Operating Expenses --}}
                        <tr style="background: rgba(39,39,42,0.5); border-bottom: 1px solid #3f3f46;">
                            <td style="padding: 0.75rem; font-weight: bold; color: #fff; font-size: 1rem;" colspan="3">Operating Expenses</td>
                        </tr>
                        @forelse($reportData['expense_breakdown'] as $expense)
                            <tr class="border-b border-zinc-800 hover:bg-zinc-800/40">
                                <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1.5rem;">{{ $expense->category_name }}</td>
                                <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #fbbf24;">(FCFA {{ number_format($expense->total_amount, 2) }})</td>
                            </tr>
                        @empty
                            <tr style="border-bottom: 1px solid #27272a;">
                                <td class="p-3 pl-6 text-zinc-500 italic" colspan="3">No expenses recorded for this period.</td>
                            </tr>
                        @endforelse
                        <tr style="border-bottom: 1px solid #3f3f46; background: rgba(39,39,42,0.3);">
                            <td style="padding: 0.75rem 0.75rem 0.75rem 1rem; font-weight: 600; color: #fff;">Total Expenses</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: bold; color: #fbbf24;">(FCFA {{ number_format($reportData['total_expenses'], 2) }})</td>
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
                            <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                            <th class="p-3 font-semibold">Category</th>
                            <th class="p-3 font-semibold text-right">Count</th>
                            <th class="p-3 font-semibold text-right">Amount</th>
                            <th class="p-3 font-semibold text-right">% of Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700 text-zinc-300">
                        @foreach($reportData['expense_breakdown'] as $expense)
                            <tr class="hover:bg-zinc-800/50">
                                <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
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
                            <td colspan="2" class="p-3">Total</td>
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
