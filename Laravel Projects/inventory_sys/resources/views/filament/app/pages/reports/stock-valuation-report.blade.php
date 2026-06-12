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
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit.prevent="submit" class="space-y-4">
                {{ $this->form }}
                
                <div class="flex items-center gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">
                        Filter Report
                    </x-filament::button>
                    
                    <x-filament::button tag="a" href="{{ route('reports.stock-valuation.pdf', $data) }}" target="_blank" color="success" icon="heroicon-o-document-arrow-down">
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
                            <th class="p-3 font-semibold">Item & SKU</th>
                            <th class="p-3 font-semibold">Category</th>
                            <th class="p-3 font-semibold">UoM</th>
                            <th class="p-3 font-semibold text-right">Qty On Hand</th>
                            <th class="p-3 font-semibold text-right">Unit Cost</th>
                            <th class="p-3 font-semibold text-right">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700 text-zinc-300">
                        @forelse($reportData as $row)
                            <tr class="bg-zinc-900 font-medium text-white border-t border-zinc-700">
                                <td class="p-3">
                                    <div class="font-bold">{{ $row->item?->name }}</div>
                                    <div class="text-xs text-zinc-400 font-mono">SKU: {{ $row->item?->sku }}</div>
                                </td>
                                <td class="p-3 text-zinc-400">{{ $row->item?->category?->name ?? 'Uncategorized' }}</td>
                                <td class="p-3 text-zinc-400">{{ $row->item?->uom?->abbreviation }}</td>
                                <td class="p-3 text-right">{{ number_format($row->qty_on_hand) }}</td>
                                <td class="p-3 text-right">FCFA {{ number_format($row->item?->unit_cost ?? 0, 2) }}</td>
                                <td class="p-3 text-right font-bold text-success-400">FCFA {{ number_format($row->qty_on_hand * ($row->item?->unit_cost ?? 0), 2) }}</td>
                            </tr>
                            @if($row->batches && $row->batches->isNotEmpty())
                                <tr>
                                    <td colspan="6" class="p-0 bg-zinc-950/40">
                                        <div class="pl-8 pr-3 py-2">
                                            <table class="nested-report-table w-full text-left border-collapse text-xs">
                                                <thead>
                                                    <tr class="border-b border-zinc-850 text-zinc-400 font-semibold">
                                                        <th class="py-1 px-2">Batch #</th>
                                                        <th class="py-1 px-2">Expiry Date</th>
                                                        <th class="py-1 px-2 text-right">Remaining Qty</th>
                                                        <th class="py-1 px-2 text-right">Unit Cost</th>
                                                        <th class="py-1 px-2 text-right">Batch Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-zinc-900 text-zinc-450">
                                                    @foreach($row->batches as $batch)
                                                        <tr>
                                                            <td class="py-1 px-2 font-mono">{{ $batch->batch_number }}</td>
                                                            <td class="py-1 px-2">{{ $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') : 'No Expiry' }}</td>
                                                            <td class="py-1 px-2 text-right">{{ number_format($batch->qty_remaining) }}</td>
                                                            <td class="py-1 px-2 text-right">FCFA {{ number_format($batch->unit_cost, 2) }}</td>
                                                            <td class="py-1 px-2 text-right">FCFA {{ number_format($batch->qty_remaining * $batch->unit_cost, 2) }}</td>
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
                                <td colspan="6" class="p-4 text-center text-zinc-500">No stock levels found for the selected department or category.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-zinc-500 bg-zinc-800/80 font-bold text-white">
                                <td colspan="3" class="p-3 text-left">Grand Total</td>
                                <td class="p-3 text-right">{{ number_format($reportData->sum('qty_on_hand')) }}</td>
                                <td class="p-3"></td>
                                <td class="p-3 text-right text-success-400">FCFA {{ number_format($reportData->sum(fn($row) => $row->qty_on_hand * ($row->item?->unit_cost ?? 0)), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
