<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Clearance Activity Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 9px; color: #555; }
        .meta { margin-bottom: 12px; font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2d3748; color: #fff; padding: 6px 4px; text-align: left; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        td.right { text-align: right; }
        tr:nth-child(even) { background: #f7fafc; }
        tfoot tr { background: #edf2f7 !important; font-weight: bold; }
        tfoot td { border-top: 2px solid #2d3748; padding: 6px 4px; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Clearance Activity Report</h1>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="meta">
        @if(!empty($filters['date_from']))<strong>From:</strong> {{ $filters['date_from'] }} &nbsp; @endif
        @if(!empty($filters['date_to']))<strong>To:</strong> {{ $filters['date_to'] }} &nbsp; @endif
        @if(!empty($filters['action_type']))<strong>Action:</strong> {{ ucfirst($filters['action_type']) }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Action</th>
                <th>Item</th>
                <th>Batch #</th>
                <th class="right">Qty</th>
                <th class="right">Loss / Value</th>
                <th>Reference</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->created_at?->format('M d, Y H:i') }}</td>
                    <td>{{ ucfirst($row->action_type) }}</td>
                    <td>{{ $row->item?->name ?? '—' }}</td>
                    <td>{{ $row->clearanceStock?->batch_number ?? '—' }}</td>
                    <td class="right">{{ number_format($row->qty) }}</td>
                    <td class="right">FCFA {{ number_format($row->loss_value, 2) }}</td>
                    <td>
                        @if($row->sales_order_id)
                            SO #{{ $row->salesOrder?->order_number ?? $row->sales_order_id }}
                        @elseif($row->donation_id)
                            DON #{{ $row->donation?->donation_number ?? $row->donation_id }}
                        @elseif($row->disposal_id)
                            DSP #{{ $row->disposal?->disposal_number ?? $row->disposal_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $row->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#999;padding:10px;">No clearance activity found.</td></tr>
            @endforelse
        </tbody>
        @if($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="right">{{ number_format($data->sum('qty')) }}</td>
                <td class="right">FCFA {{ number_format($data->sum('loss_value'), 2) }}</td>
                <td colspan="2">—</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
