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
        
        .nested-report-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.75rem;
            line-height: 1rem;
        }
        .nested-report-table th, 
        .nested-report-table td {
            padding: 0.5rem 0.75rem !important;
        }
        .nested-report-table th {
            font-weight: 600;
            background-color: rgba(24, 24, 27, 0.9) !important;
            color: #a1a1aa !important;
            border-bottom: 1px solid #27272a;
        }
        .nested-report-table td {
            border-bottom: 1px solid #18181b;
            color: #a1a1aa;
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
                    
                    <x-filament::button type="button" wire:click="exportPdf('reports.stock-valuation.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportCsv('stock-valuation-report')" color="info" icon="heroicon-o-table-cells">
                        Export CSV
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportExcel('stock-valuation-report')" color="warning" icon="heroicon-o-document-text">
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
                            <th>Item &amp; SKU</th>
                            <th>Category</th>
                            <th>UoM</th>
                            <th class="text-right">Qty On Hand</th>
                            <th class="text-right">Unit Cost</th>
                            <th class="text-right">Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr style="background: rgba(24,24,27,0.8); font-weight: 500; color: #fff; border-top: 1px solid #3f3f46;">
                                <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td>
                                    <div style="font-weight:700;">{{ $row->item?->name }}</div>
                                    <div style="font-size:0.75rem; color:#a1a1aa; font-family:monospace;">SKU: {{ $row->item?->sku }}</div>
                                </td>
                                <td style="color: #a1a1aa;">{{ $row->item?->category?->name ?? 'Uncategorized' }}</td>
                                <td style="color: #a1a1aa;">{{ $row->item?->uom?->abbreviation }}</td>
                                <td class="text-right">{{ number_format($row->qty_on_hand) }}</td>
                                <td class="text-right">FCFA {{ number_format($row->item?->unit_cost ?? 0, 2) }}</td>
                                <td class="text-right" style="font-weight:700; color:#34d399;">FCFA {{ number_format($row->qty_on_hand * ($row->item?->unit_cost ?? 0), 2) }}</td>
                            </tr>
                            @if($row->batches && $row->batches->isNotEmpty())
                                <tr>
                                    <td colspan="7" style="padding:0; background:rgba(9,9,11,0.4);">
                                        <div style="padding-left:2rem; padding-right:0.75rem; padding-top:0.5rem; padding-bottom:0.5rem;">
                                            <table class="nested-report-table">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                                    <th>Batch #</th>
                                                        <th>Expiry Date</th>
                                                        <th class="text-right">Remaining Qty</th>
                                                        <th class="text-right">Unit Cost</th>
                                                        <th class="text-right">Batch Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($row->batches as $batch)
                                                        <tr>
                                                            <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                                    <td style="font-family:monospace;">{{ $batch->batch_number }}</td>
                                                            <td>{{ $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') : 'No Expiry' }}</td>
                                                            <td class="text-right">{{ number_format($batch->qty_remaining) }}</td>
                                                            <td class="text-right">FCFA {{ number_format($batch->unit_cost, 2) }}</td>
                                                            <td class="text-right">FCFA {{ number_format($batch->qty_remaining * $batch->unit_cost, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 1rem; text-align: center; color: #71717a;">No stock levels found for the selected department or category.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:left;">Grand Total</td>
                                <td class="text-right">{{ number_format($reportData->sum('qty_on_hand')) }}</td>
                                <td></td>
                                <td class="text-right" style="color:#34d399;">FCFA {{ number_format($reportData->sum(fn($row) => $row->qty_on_hand * ($row->item?->unit_cost ?? 0)), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
