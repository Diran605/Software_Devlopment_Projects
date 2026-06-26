<x-filament-panels::page>
    <style>
        .report-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; line-height: 1.25rem; }
        .report-table th, .report-table td { padding: 0.75rem 1rem !important; }
        .report-table th { font-weight: 600; background-color: rgba(39, 39, 42, 0.8) !important; color: #e4e4e7 !important; border-bottom: 1px solid #3f3f46; }
        .report-table td { border-bottom: 1px solid #27272a; color: #d4d4d8; }
        .report-table tbody tr:hover { background-color: rgba(39, 39, 42, 0.4); }
        .report-table tfoot tr { border-top: 2px solid #52525b; background-color: rgba(39, 39, 42, 0.8) !important; font-weight: bold; color: #ffffff !important; }
        .text-right { text-align: right !important; }
    </style>
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit.prevent="submit" class="space-y-4">
                {{ $this->form }}
                <div class="flex items-center gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">Filter Report</x-filament::button>
                    <x-filament::button wire:click="exportPdf('reports.clearance-activity.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        <x-filament::card>
            <div class="overflow-x-auto">
                <table class="report-table w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Item</th>
                            <th>Batch #</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Loss / Value</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr>
                                <td>{{ $row->created_at?->format('M d, Y H:i') }}</td>
                                <td>{{ ucfirst($row->action_type) }}</td>
                                <td>{{ $row->item?->name ?? '—' }}</td>
                                <td>{{ $row->clearanceStock?->batch_number ?? '—' }}</td>
                                <td class="text-right">{{ number_format($row->qty) }}</td>
                                <td class="text-right">FCFA {{ number_format($row->loss_value, 2) }}</td>
                                <td>
                                    @if($row->sales_order_id)
                                        {{ $row->salesOrder?->order_number ?? 'Sales Order' }}
                                    @elseif($row->donation_id)
                                        {{ $row->donation?->donation_number ?? 'Donation' }}
                                    @elseif($row->disposal_id)
                                        {{ $row->disposal?->disposal_number ?? 'Disposal' }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-center text-zinc-500">No clearance activity found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="4">Total</td>
                                <td class="text-right">{{ number_format($reportData->sum('qty')) }}</td>
                                <td class="text-right">FCFA {{ number_format($reportData->sum('loss_value'), 2) }}</td>
                                <td>—</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
