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
                    
                    <x-filament::button tag="a" href="{{ route('reports.expiry.pdf', $data) }}" target="_blank" color="success" icon="heroicon-o-document-arrow-down">
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
                            <th class="p-3 font-semibold">Item</th>
                            <th class="p-3 font-semibold">Category</th>
                            <th class="p-3 font-semibold">Batch #</th>
                            <th class="p-3 font-semibold">Expiry Date</th>
                            <th class="p-3 font-semibold text-right">Days to Expiry</th>
                            <th class="p-3 font-semibold text-right">Qty Remaining</th>
                            <th class="p-3 font-semibold text-right">Unit Cost</th>
                            <th class="p-3 font-semibold text-right">Total Value</th>
                            <th class="p-3 font-semibold">Urgency</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700 text-zinc-300">
                        @forelse($reportData as $row)
                            <tr class="hover:bg-zinc-800/50">
                                <td class="p-3 font-medium">{{ $row->item->name }}</td>
                                <td class="p-3">{{ $row->item->category?->name ?? '—' }}</td>
                                <td class="p-3">{{ $row->batch_number }}</td>
                                <td class="p-3">{{ $row->expiry_date ? $row->expiry_date->format('M d, Y') : '—' }}</td>
                                <td class="p-3 text-right font-semibold {{ $row->days_to_expiry <= 6 ? 'text-red-500' : ($row->days_to_expiry <= 30 ? 'text-amber-500' : 'text-zinc-400') }}">
                                    {{ $row->days_to_expiry }}
                                </td>
                                <td class="p-3 text-right">{{ number_format($row->qty_remaining) }}</td>
                                <td class="p-3 text-right">FCFA {{ number_format($row->unit_cost, 2) }}</td>
                                <td class="p-3 text-right font-medium">FCFA {{ number_format($row->total_cost, 2) }}</td>
                                <td class="p-3">
                                    <x-filament::badge :color="$row->urgency_color">
                                        {{ $row->urgency_label }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-4 text-center text-zinc-500">No records found for the selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-zinc-500 bg-zinc-800/80 font-bold text-white">
                                <td class="p-3" colspan="5">Total</td>
                                <td class="p-3 text-right">{{ number_format($reportData->sum('qty_remaining')) }}</td>
                                <td class="p-3 text-right">—</td>
                                <td class="p-3 text-right">FCFA {{ number_format($reportData->sum('total_cost'), 2) }}</td>
                                <td class="p-3">—</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
