<x-filament-panels::page>
    <style>
        .report-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .report-table th, .report-table td { padding: 0.75rem 1rem !important; }
        .report-table th { font-weight: 600; background-color: rgba(39, 39, 42, 0.8) !important; color: #e4e4e7 !important; }
        .report-table td { border-bottom: 1px solid #27272a; color: #d4d4d8; }
        .text-right { text-align: right !important; }
    </style>
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit.prevent="submit" class="space-y-4">
                {{ $this->form }}
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem; flex-wrap: wrap;">
                    <x-filament::button type="submit" color="primary">Filter Report</x-filament::button>
                    <x-filament::button type="button" wire:click="exportPdf('reports.expenses.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportCsv('expense-report')" color="info" icon="heroicon-o-table-cells">
                        Export CSV
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportExcel('expense-report')" color="warning" icon="heroicon-o-document-text">
                        Export Excel
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        <x-filament::card>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: rgba(39,39,42,0.6); border-radius: 0.5rem; padding: 1rem; border: 1px solid #3f3f46;">
                    <div style="font-size: 0.875rem; color: #a1a1aa; font-weight: 600;">Total Expenses</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #fff; margin-top: 0.25rem;">FCFA {{ number_format($reportData['total_amount'], 2) }}</div>
                </div>
                <div style="background: rgba(39,39,42,0.6); border-radius: 0.5rem; padding: 1rem; border: 1px solid #3f3f46;">
                    <div style="font-size: 0.875rem; color: #a1a1aa; font-weight: 600;">Expense Entries</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #fff; margin-top: 0.25rem;">{{ number_format($reportData['expense_count']) }}</div>
                </div>
                <div style="background: rgba(39,39,42,0.6); border-radius: 0.5rem; padding: 1rem; border: 1px solid #3f3f46;">
                    <div style="font-size: 0.875rem; color: #a1a1aa; font-weight: 600;">Included in P&amp;L</div>
                    <div style="font-size: 0.875rem; color: #34d399; margin-top: 0.5rem;">These totals feed the Profit &amp; Loss report for the same date range.</div>
                </div>
            </div>

            <h3 class="text-lg font-semibold text-white mb-3">By Category</h3>
            <table class="report-table w-full mb-8">
                <thead>
                    <tr>
                        <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                            <th>Category</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['category_summary'] as $row)
                        <tr>
                            <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td>{{ $row->category_name }}</td>
                            <td class="text-right">{{ number_format($row->expense_count) }}</td>
                            <td class="text-right">FCFA {{ number_format($row->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-4 text-center text-zinc-500">No expenses found.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="text-lg font-semibold text-white mb-3">Expense Lines</h3>
            <div class="overflow-x-auto">
                <table class="report-table w-full">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Category</th>
                            <th>Payee</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['rows'] as $expense)
                            <tr>
                                <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td>{{ $expense->expense_date?->format('M d, Y') }}</td>
                                <td>{{ $expense->reference_number ?? '—' }}</td>
                                <td>{{ $expense->category?->name ?? 'Uncategorized' }}</td>
                                <td>{{ $expense->payee ?? '—' }}</td>
                                <td>{{ $expense->description ?? '—' }}</td>
                                <td class="text-right">FCFA {{ number_format($expense->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-center text-zinc-500">No expenses found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
