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
                    
                    <x-filament::button type="button" wire:click="exportPdf('reports.purchases.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                        Export PDF
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportCsv('purchase-report')" color="info" icon="heroicon-o-table-cells">
                        Export CSV
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportExcel('purchase-report')" color="warning" icon="heroicon-o-document-text">
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
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Ordered At</th>
                            <th>Expected Delivery</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr style="background: rgba(24,24,27,0.8); font-weight: 500; color: #fff; border-top: 1px solid #3f3f46;">
                                <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                            <td style="font-weight: 700;">{{ $row->po_number }}</td>
                                <td style="color: #a1a1aa;">{{ $row->supplier?->name }}</td>
                                <td style="color: #a1a1aa;">{{ $row->ordered_at ? \Carbon\Carbon::parse($row->ordered_at)->format('M d, Y H:i') : '—' }}</td>
                                <td style="color: #a1a1aa;">{{ $row->expected_delivery_at ? \Carbon\Carbon::parse($row->expected_delivery_at)->format('M d, Y') : '—' }}</td>
                                <td style="text-align: center;">
                                    @php
                                        $styleMap = [
                                            'draft'              => 'color:#a1a1aa; background:rgba(39,39,42,0.6);',
                                            'issued'             => 'color:#38bdf8; background:rgba(8,47,73,0.4);',
                                            'partially_received' => 'color:#fbbf24; background:rgba(69,26,3,0.4);',
                                            'fully_received'     => 'color:#34d399; background:rgba(6,78,59,0.4);',
                                            'cancelled'          => 'color:#fb7185; background:rgba(136,19,55,0.4);',
                                        ];
                                        $badgeStyle = $styleMap[$row->status] ?? 'color:#a1a1aa; background:rgba(39,39,42,0.6);';
                                        $label = match ($row->status) {
                                            'draft'              => 'Draft',
                                            'issued'             => 'Issued',
                                            'partially_received' => 'Partially Received',
                                            'fully_received'     => 'Fully Received',
                                            'cancelled'          => 'Cancelled',
                                            default              => ucfirst($row->status),
                                        };
                                    @endphp
                                    <span style="display:inline-flex; align-items:center; padding:0.125rem 0.5rem; border-radius:0.25rem; font-size:0.75rem; font-weight:600; {{ $badgeStyle }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="text-right" style="font-weight:700; color:#34d399;">FCFA {{ number_format($row->total_amount, 2) }}</td>
                            </tr>
                            @if($row->purchaseOrderLines && $row->purchaseOrderLines->isNotEmpty())
                                <tr>
                                    <td colspan="7" style="padding:0; background:rgba(9,9,11,0.4);">
                                        <div style="padding-left:2rem; padding-right:0.75rem; padding-top:0.5rem; padding-bottom:0.5rem;">
                                            <table class="nested-report-table">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                                    <th>Item</th>
                                                        <th class="text-right">Qty Ordered</th>
                                                        <th class="text-right">Qty Received</th>
                                                        <th class="text-right">Unit Cost</th>
                                                        <th class="text-right">Line Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($row->purchaseOrderLines as $line)
                                                        <tr>
                                                            <td class="text-center" style="text-align:center; color:#888;">{{ $loop->iteration }}</td>
                                    <td style="font-weight:600; color:#d4d4d8;">{{ $line->item?->name }}</td>
                                                            <td class="text-right">{{ number_format($line->qty_ordered) }}</td>
                                                            <td class="text-right">{{ number_format($line->qty_received) }}</td>
                                                            <td class="text-right">FCFA {{ number_format($line->unit_cost, 2) }}</td>
                                                            <td class="text-right">FCFA {{ number_format($line->qty_ordered * $line->unit_cost, 2) }}</td>
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
                                <td colspan="7" style="padding: 1rem; text-align: center; color: #71717a;">No purchase orders found for the selected supplier or status.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:left;">Grand Total</td>
                                <td class="text-right" style="color:#34d399;">FCFA {{ number_format($reportData->sum('total_amount'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
