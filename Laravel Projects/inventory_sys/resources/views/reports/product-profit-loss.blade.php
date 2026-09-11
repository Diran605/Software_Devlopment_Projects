<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Profit & Loss Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2d3748; color: #fff; padding: 6px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 5px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        td.right { text-align: right; }
        .grand-total { background: #edf2f7; font-weight: bold; }
        .grand-total td { padding: 8px 10px; font-size: 11px; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Product Profit & Loss</h1>
        <p>Period: {{ $filters['date_from'] ?? 'All time' }} to {{ $filters['date_to'] ?? 'Present' }} | Filter: {{ ucfirst($reportData['filter']) }}</p>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:3%; text-align:center;">S/N</th>
                            <th>Product</th>
                <th>Category</th>
                <th class="right">Qty Sold</th>
                <th class="right">Revenue</th>
                <th class="right">Cost (COGS)</th>
                <th class="right">Gross Profit</th>
                <th class="right">Margin %</th>
                <th class="right">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['rows'] as $row)
                <tr style="{{ $row->is_loss ? 'background-color:#fff5f5;' : ($row->is_low_margin ? 'background-color:#fffff0;' : '') }}">
                    <td><strong>{{ $row->item_name }}</strong></td>
                    <td style="color:#555;">{{ $row->category_name ?? '—' }}</td>
                    <td class="right">{{ number_format($row->total_qty) }}</td>
                    <td class="right" style="color:#276749;">{{ number_format($row->total_revenue, 2) }}</td>
                    <td class="right" style="color:#9b2c2c;">{{ number_format($row->total_cost, 2) }}</td>
                    <td class="right">
                        @if($row->is_loss)
                            <span style="color:#e53e3e;font-weight:bold;">({{ number_format(abs($row->total_profit), 2) }})</span>
                        @else
                            <span style="color:#276749;font-weight:bold;">{{ number_format($row->total_profit, 2) }}</span>
                        @endif
                    </td>
                    <td class="right">
                        <span style="font-weight:bold; color: {{ $row->margin_pct < 0 ? '#e53e3e' : ($row->is_low_margin ? '#d69e2e' : '#276749') }}">
                            {{ $row->margin_pct }}%
                        </span>
                    </td>
                    <td class="right">
                        @if($row->is_loss)
                            <span style="color:#e53e3e;font-size:9px;">Loss</span>
                        @elseif($row->is_low_margin)
                            <span style="color:#d69e2e;font-size:9px;">Low Margin</span>
                        @else
                            <span style="color:#276749;font-size:9px;">Healthy</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="4">TOTALS</td>
                <td class="right">{{ number_format($reportData['total_revenue'], 2) }}</td>
                <td class="right">{{ number_format($reportData['total_cost'], 2) }}</td>
                <td class="right">
                    @if($reportData['total_profit'] < 0)
                        <span style="color:#e53e3e;">({{ number_format(abs($reportData['total_profit']), 2) }})</span>
                    @else
                        <span style="color:#276749;">{{ number_format($reportData['total_profit'], 2) }}</span>
                    @endif
                </td>
                <td class="right">{{ $reportData['overall_margin'] }}%</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
