<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2d3748; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        th.right { text-align: right; }
        th.center { text-align: center; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        td.right { text-align: right; }
        td.center { text-align: center; }
        tr:nth-child(even) { background: #f7fafc; }
        .po-header { background: #edf2f7; font-weight: bold; }
        .line-items { padding-left: 20px; }
        .line-items td { font-size: 9px; color: #555; border-bottom: 1px solid #f0f0f0; }
        .status-badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .status-draft { background: #e2e8f0; color: #4a5568; }
        .status-issued { background: #bee3f8; color: #2b6cb0; }
        .status-partial { background: #fefcbf; color: #975a16; }
        .status-received { background: #c6f6d5; color: #276749; }
        .status-cancelled { background: #fed7d7; color: #9b2c2c; }
        tfoot tr { background: #edf2f7 !important; font-weight: bold; }
        tfoot td { border-top: 2px solid #2d3748; padding: 8px 6px; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Purchase Report</h1>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>PO Number</th>
                <th>Supplier</th>
                <th>Ordered At</th>
                <th class="center">Status</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $po)
                <tr class="po-header">
                    <td>{{ $po->po_number }}</td>
                    <td>{{ $po->supplier?->name }}</td>
                    <td>{{ $po->ordered_at ? \Carbon\Carbon::parse($po->ordered_at)->format('M d, Y') : '—' }}</td>
                    <td class="center">
                        @php
                            $statusClass = match($po->status) {
                                'draft' => 'status-draft',
                                'issued' => 'status-issued',
                                'partially_received' => 'status-partial',
                                'fully_received' => 'status-received',
                                'cancelled' => 'status-cancelled',
                                default => 'status-draft',
                            };
                            $statusLabel = match($po->status) {
                                'draft' => 'Draft',
                                'issued' => 'Issued',
                                'partially_received' => 'Partial',
                                'fully_received' => 'Received',
                                'cancelled' => 'Cancelled',
                                default => ucfirst($po->status),
                            };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="right">FCFA {{ number_format($po->total_amount, 2) }}</td>
                </tr>
                @if($po->purchaseOrderLines && $po->purchaseOrderLines->isNotEmpty())
                    @foreach($po->purchaseOrderLines as $line)
                        <tr class="line-items">
                            <td colspan="2" style="padding-left:20px;">↳ {{ $line->item?->name }}</td>
                            <td>Qty: {{ number_format($line->qty_ordered) }} (Rcvd: {{ number_format($line->qty_received) }})</td>
                            <td class="right">@ FCFA {{ number_format($line->unit_cost, 2) }}</td>
                            <td class="right">FCFA {{ number_format($line->qty_ordered * $line->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr><td colspan="5" style="text-align:center;color:#999;">No purchase orders found.</td></tr>
            @endforelse
        </tbody>
        @if($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4">Grand Total ({{ $data->count() }} orders)</td>
                <td class="right">FCFA {{ number_format($data->sum('total_amount'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
