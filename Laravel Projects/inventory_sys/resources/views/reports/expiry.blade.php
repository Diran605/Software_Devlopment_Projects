<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expiry Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 9px; color: #555; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 9px; color: #666; }
        .meta-left { float: left; }
        .meta-right { float: right; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2d3748; color: #fff; padding: 6px 4px; text-align: left; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        td.right { text-align: right; }
        tr:nth-child(even) { background: #f7fafc; }
        tfoot tr { background: #edf2f7 !important; font-weight: bold; }
        tfoot td { border-top: 2px solid #2d3748; padding: 6px 4px; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
        .clearfix::after { content: ''; display: table; clear: both; }
        .badge {
            display: inline-block;
            padding: 2px 4px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            text-align: center;
            color: #fff;
        }
        .badge-danger { background-color: #e53e3e; }
        .badge-warning { background-color: #dd6b20; color: #fff; }
        .badge-info { background-color: #3182ce; }
        .badge-success { background-color: #38a169; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Expiry Report</h1>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="meta clearfix">
        <div class="meta-left">
            <strong>Days to Expiry Threshold:</strong> Within {{ $filters['days_threshold'] ?? 90 }} Days<br>
            <strong>Urgency Band:</strong> {{ ucfirst(str_replace('_', ' ', $filters['urgency_band'] ?? 'all')) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Batch #</th>
                <th>Expiry Date</th>
                <th class="right">Days to Expiry</th>
                <th class="right">Qty Remaining</th>
                <th class="right">Unit Cost</th>
                <th class="right">Total Value</th>
                <th>Urgency Band</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->item->name }}</td>
                    <td>{{ $row->item->category?->name ?? '—' }}</td>
                    <td>{{ $row->batch_number }}</td>
                    <td>{{ $row->expiry_date ? $row->expiry_date->format('M d, Y') : '—' }}</td>
                    <td class="right">{{ $row->days_to_expiry }}</td>
                    <td class="right">{{ number_format($row->qty_remaining) }}</td>
                    <td class="right">FCFA {{ number_format($row->unit_cost, 2) }}</td>
                    <td class="right">FCFA {{ number_format($row->total_cost, 2) }}</td>
                    <td>
                        @if($row->urgency_band === 'expired' || $row->urgency_band === 'critical')
                            <span class="badge badge-danger">{{ $row->urgency_label }}</span>
                        @elseif($row->urgency_band === 'urgent')
                            <span class="badge badge-warning">{{ $row->urgency_label }}</span>
                        @elseif($row->urgency_band === 'approaching')
                            <span class="badge badge-info">{{ $row->urgency_label }}</span>
                        @else
                            <span class="badge badge-success">{{ $row->urgency_label }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#999;padding: 10px;">No batches nearing expiry found within selected filters.</td></tr>
            @endforelse
        </tbody>
        @if($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="5">Total</td>
                <td class="right">{{ number_format($data->sum('qty_remaining')) }}</td>
                <td class="right">—</td>
                <td class="right">FCFA {{ number_format($data->sum('total_cost'), 2) }}</td>
                <td>—</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
