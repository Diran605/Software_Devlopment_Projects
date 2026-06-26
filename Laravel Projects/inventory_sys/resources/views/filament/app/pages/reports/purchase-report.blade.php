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
                    
                    <x-filament::button wire:click="exportPdf('reports.purchases.pdf')" color="success" icon="heroicon-o-document-arrow-down">
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
                            <th class="p-3 font-semibold">PO Number</th>
                            <th class="p-3 font-semibold">Supplier</th>
                            <th class="p-3 font-semibold">Ordered At</th>
                            <th class="p-3 font-semibold">Expected Delivery</th>
                            <th class="p-3 font-semibold text-center">Status</th>
                            <th class="p-3 font-semibold text-right">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700 text-zinc-300">
                        @forelse($reportData as $row)
                            <tr class="bg-zinc-900 font-medium text-white border-t border-zinc-700">
                                <td class="p-3 font-bold">{{ $row->po_number }}</td>
                                <td class="p-3 text-zinc-400">{{ $row->supplier?->name }}</td>
                                <td class="p-3 text-zinc-400">{{ $row->ordered_at ? \Carbon\Carbon::parse($row->ordered_at)->format('M d, Y H:i') : '—' }}</td>
                                <td class="p-3 text-zinc-400">{{ $row->expected_delivery_at ? \Carbon\Carbon::parse($row->expected_delivery_at)->format('M d, Y') : '—' }}</td>
                                <td class="p-3 text-center">
                                    @php
                                        $color = match ($row->status) {
                                            'draft' => 'text-zinc-400 bg-zinc-850',
                                            'issued' => 'text-sky-400 bg-sky-950/40',
                                            'partially_received' => 'text-amber-400 bg-amber-950/40',
                                            'fully_received' => 'text-emerald-400 bg-emerald-950/40',
                                            'cancelled' => 'text-rose-400 bg-rose-950/40',
                                            default => 'text-zinc-400 bg-zinc-850',
                                        };
                                        $label = match ($row->status) {
                                            'draft' => 'Draft',
                                            'issued' => 'Issued',
                                            'partially_received' => 'Partially Received',
                                            'fully_received' => 'Fully Received',
                                            'cancelled' => 'Cancelled',
                                            default => ucfirst($row->status),
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $color }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="p-3 text-right font-bold text-success-400">FCFA {{ number_format($row->total_amount, 2) }}</td>
                            </tr>
                            @if($row->purchaseOrderLines && $row->purchaseOrderLines->isNotEmpty())
                                <tr>
                                    <td colspan="6" class="p-0 bg-zinc-950/40">
                                        <div class="pl-8 pr-3 py-2">
                                            <table class="nested-report-table">
                                                <thead>
                                                    <tr class="border-b border-zinc-800 text-zinc-400 font-semibold">
                                                        <th class="py-1 px-2">Item</th>
                                                        <th class="py-1 px-2 text-right">Qty Ordered</th>
                                                        <th class="py-1 px-2 text-right">Qty Received</th>
                                                        <th class="py-1 px-2 text-right">Unit Cost</th>
                                                        <th class="py-1 px-2 text-right">Line Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-zinc-900 text-zinc-400">
                                                    @foreach($row->purchaseOrderLines as $line)
                                                        <tr>
                                                            <td class="py-1 px-2 font-semibold text-zinc-300">{{ $line->item?->name }}</td>
                                                            <td class="py-1 px-2 text-right">{{ number_format($line->qty_ordered) }}</td>
                                                            <td class="py-1 px-2 text-right">{{ number_format($line->qty_received) }}</td>
                                                            <td class="py-1 px-2 text-right">FCFA {{ number_format($line->unit_cost, 2) }}</td>
                                                            <td class="py-1 px-2 text-right">FCFA {{ number_format($line->qty_ordered * $line->unit_cost, 2) }}</td>
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
                                <td colspan="6" class="p-4 text-center text-zinc-500">No purchase orders found for the selected supplier or status.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-zinc-500 bg-zinc-800/80 font-bold text-white">
                                <td colspan="5" class="p-3 text-left">Grand Total</td>
                                <td class="p-3 text-right text-success-400">FCFA {{ number_format($reportData->sum('total_amount'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
