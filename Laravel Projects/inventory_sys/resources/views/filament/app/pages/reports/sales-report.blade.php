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
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <x-filament::card>
            <form wire:submit.prevent="submit" style="display: flex; flex-direction: column; gap: 1rem;">
                {{ $this->form }}
                
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem;">
                    <x-filament::button type="submit" color="primary">
                        Filter Report
                    </x-filament::button>
                    
                    <x-filament::button type="button" wire:click="exportPdf('reports.sales.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportCsv('sales-report')" color="info" icon="heroicon-o-table-cells">
                        Export CSV
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportExcel('sales-report')" color="warning" icon="heroicon-o-document-text">
                        Export Excel
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        <x-filament::card>
            <div style="overflow-x: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                            <th>Grouped By ({{ ucfirst($data['group_by'] ?? 'date') }})</th>
                            <th class="text-right">Orders Count</th>
                            <th class="text-right">Total Units Sold</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-right">Gross Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr>
                                <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td>{{ $row->label }}</td>
                                <td class="text-right">{{ number_format($row->order_count) }}</td>
                                <td class="text-right">{{ number_format($row->total_qty) }}</td>
                                <td class="text-right">FCFA {{ number_format($row->total_revenue, 2) }}</td>
                                <td class="text-right">FCFA {{ number_format($row->total_profit, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 1rem; text-align: center; color: #71717a;">No records found for the selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="2">Total</td>
                                <td class="text-right">{{ number_format($reportData->sum('order_count')) }}</td>
                                <td class="text-right">{{ number_format($reportData->sum('total_qty')) }}</td>
                                <td class="text-right">FCFA {{ number_format($reportData->sum('total_revenue'), 2) }}</td>
                                <td class="text-right">FCFA {{ number_format($reportData->sum('total_profit'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
