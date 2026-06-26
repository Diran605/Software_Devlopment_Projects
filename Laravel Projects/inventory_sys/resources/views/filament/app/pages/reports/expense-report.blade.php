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
                <div class="flex items-center gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">Filter Report</x-filament::button>
                    <x-filament::button wire:click="exportPdf('reports.expenses.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        <x-filament::card>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="rounded-lg border border-zinc-700 p-4">
                    <div class="text-sm text-zinc-400">Total Expenses</div>
                    <div class="text-2xl font-bold text-white">FCFA {{ number_format($reportData['total_amount'], 2) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-700 p-4">
                    <div class="text-sm text-zinc-400">Expense Entries</div>
                    <div class="text-2xl font-bold text-white">{{ number_format($reportData['expense_count']) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-700 p-4">
                    <div class="text-sm text-zinc-400">Included in P&amp;L</div>
                    <div class="text-sm text-emerald-400 mt-2">These totals feed the Profit &amp; Loss report for the same date range.</div>
                </div>
            </div>

            <h3 class="text-lg font-semibold text-white mb-3">By Category</h3>
            <table class="report-table w-full mb-8">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['category_summary'] as $row)
                        <tr>
                            <td>{{ $row->category_name }}</td>
                            <td class="text-right">{{ number_format($row->expense_count) }}</td>
                            <td class="text-right">FCFA {{ number_format($row->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-4 text-center text-zinc-500">No expenses found.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="text-lg font-semibold text-white mb-3">Expense Lines</h3>
            <div class="overflow-x-auto">
                <table class="report-table w-full">
                    <thead>
                        <tr>
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
                                <td>{{ $expense->expense_date?->format('M d, Y') }}</td>
                                <td>{{ $expense->reference_number ?? '—' }}</td>
                                <td>{{ $expense->category?->name ?? 'Uncategorized' }}</td>
                                <td>{{ $expense->payee ?? '—' }}</td>
                                <td>{{ $expense->description ?? '—' }}</td>
                                <td class="text-right">FCFA {{ number_format($expense->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-4 text-center text-zinc-500">No expenses found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
