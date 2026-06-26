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
                    
                    <x-filament::button wire:click="exportPdf('reports.sales.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        <x-filament::card>
            <div class="overflow-x-auto">
                <table class="report-table w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700 bg-zinc-800 text-zinc-300">
                            <th class="p-3 font-semibold">Grouped By ({{ ucfirst($data['group_by'] ?? 'date') }})</th>
                            <th class="p-3 font-semibold text-right">Orders Count</th>
                            <th class="p-3 font-semibold text-right">Total Units Sold</th>
                            <th class="p-3 font-semibold text-right">Revenue</th>
                            <th class="p-3 font-semibold text-right">Gross Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700 text-zinc-300">
                        @forelse($reportData as $row)
                            <tr class="hover:bg-zinc-800/50">
                                <td class="p-3 font-medium">{{ $row->label }}</td>
                                <td class="p-3 text-right">{{ number_format($row->order_count) }}</td>
                                <td class="p-3 text-right">{{ number_format($row->total_qty) }}</td>
                                <td class="p-3 text-right">FCFA {{ number_format($row->total_revenue, 2) }}</td>
                                <td class="p-3 text-right">FCFA {{ number_format($row->total_profit, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-zinc-500">No records found for the selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-zinc-500 bg-zinc-800/80 font-bold text-white">
                                <td class="p-3">Total</td>
                                <td class="p-3 text-right">{{ number_format($reportData->sum('order_count')) }}</td>
                                <td class="p-3 text-right">{{ number_format($reportData->sum('total_qty')) }}</td>
                                <td class="p-3 text-right">FCFA {{ number_format($reportData->sum('total_revenue'), 2) }}</td>
                                <td class="p-3 text-right">FCFA {{ number_format($reportData->sum('total_profit'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
