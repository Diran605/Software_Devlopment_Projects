<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #555; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 10px; color: #666; }
        .meta-left { float: left; }
        .meta-right { float: right; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2d3748; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        td.right { text-align: right; }
        tr:nth-child(even) { background: #f7fafc; }
        tfoot tr { background: #edf2f7 !important; font-weight: bold; }
        tfoot td { border-top: 2px solid #2d3748; padding: 8px 6px; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
        .clearfix::after { content: ''; display: table; clear: both; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Sales Report</h1>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="meta clearfix">
        <div class="meta-left">
            <strong>Period:</strong> {{ $filters['date_from'] ?? 'All time' }} — {{ $filters['date_to'] ?? 'Present' }}<br>
            <strong>Grouped By:</strong> {{ ucfirst($groupBy) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ ucfirst($groupBy) }}</th>
                <th class="right">Orders</th>
                <th class="right">Units Sold</th>
                <th class="right">Revenue</th>
                <th class="right">Gross Profit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td class="right">{{ number_format($row->order_count) }}</td>
                    <td class="right">{{ number_format($row->total_qty) }}</td>
                    <td class="right">FCFA {{ number_format($row->total_revenue, 2) }}</td>
                    <td class="right">FCFA {{ number_format($row->total_profit, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#999;">No data found.</td></tr>
            @endforelse
        </tbody>
        @if($data->isNotEmpty())
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="right">{{ number_format($data->sum('order_count')) }}</td>
                <td class="right">{{ number_format($data->sum('total_qty')) }}</td>
                <td class="right">FCFA {{ number_format($data->sum('total_revenue'), 2) }}</td>
                <td class="right">FCFA {{ number_format($data->sum('total_profit'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
