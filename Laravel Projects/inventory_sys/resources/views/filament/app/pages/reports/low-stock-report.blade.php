<x-filament-panels::page>
    <style>
        .report-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; line-height: 1.25rem; }
        .report-table th, .report-table td { padding: 0.75rem 1rem !important; }
        .report-table th { font-weight: 600; background-color: rgba(39, 39, 42, 0.8) !important; color: #e4e4e7 !important; border-bottom: 1px solid #3f3f46; }
        .report-table td { border-bottom: 1px solid #27272a; color: #d4d4d8; }
        .report-table tbody tr:hover { background-color: rgba(39, 39, 42, 0.4); }
        .text-right { text-align: right !important; }
    </style>
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit.prevent="submit" class="space-y-4">
                {{ $this->form }}
                <div class="flex items-center gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">Filter Report</x-filament::button>
                    <x-filament::button wire:click="exportPdf('reports.low-stock.pdf')" color="success" icon="heroicon-o-document-arrow-down">
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
                            <th>Item</th>
                            <th>Category</th>
                            <th class="text-right">Qty On Hand</th>
                            <th class="text-right">Reorder Level</th>
                            <th class="text-right">Shortfall</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr>
                                <td>{{ $row->item->name }}</td>
                                <td>{{ $row->item->category?->name ?? '—' }}</td>
                                <td class="text-right">{{ number_format($row->qty_on_hand) }}</td>
                                <td class="text-right">{{ number_format($row->item->reorder_level) }}</td>
                                <td class="text-right text-danger-500">{{ number_format(max(0, $row->item->reorder_level - $row->qty_on_hand)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-zinc-500">No low stock items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
