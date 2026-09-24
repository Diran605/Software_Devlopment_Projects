<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detailed Sales Report</title>
    <style>

        @page { margin: 20px; }
        body { padding: 0 !important; margin: 0 !important; font-size: 12px !important; }
        table { width: 100% !important; max-width: 100% !important; table-layout: fixed; word-wrap: break-word; }
        th, td { word-wrap: break-word; overflow-wrap: break-word; font-size: 11px !important; padding: 6px 4px !important; }
        th { font-size: 11px !important; }

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
        <h1>{{ $branchName ?? 'Branch' }} — Detailed Sales Report</h1>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="meta clearfix">
        <div class="meta-left">
            Period: {{ $from ?? 'All time' }} — {{ $to ?? 'Present' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Item</th>
                <th style="width: 10%;">Category</th>
                <th style="width: 7%; text-align:right;">Opening</th>
                <th style="width: 7%; text-align:right;">New(GRN)</th>
                <th style="width: 7%; text-align:right;">Adjust</th>
                <th style="width: 7%; text-align:right;">Total</th>
                <th style="width: 7%; text-align:right;">Sold</th>
                <th style="width: 7%; text-align:right;">Closing</th>
                <th style="width: 11%; text-align:right;">Revenue</th>
                <th style="width: 11%; text-align:right;">COGS</th>
                <th style="width: 11%; text-align:right;">Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData["rows"] as $row)
                <tr>
                    <td>{{ $row->item_name }}</td>
                    <td>{{ $row->category_name }}</td>
                    <td style="text-align:right;">{{ number_format($row->opening_stock) }}</td>
                    <td style="text-align:right;">{{ number_format($row->new_stock) }}</td>
                    <td style="text-align:right;">{{ number_format($row->adjustments) }}</td>
                    <td style="text-align:right;">{{ number_format($row->total_stock) }}</td>
                    <td style="text-align:right;">{{ number_format($row->qty_sold) }}</td>
                    <td style="text-align:right;">{{ number_format($row->closing_stock) }}</td>
                    <td style="text-align:right;">{{ number_format($row->revenue) }}</td>
                    <td style="text-align:right;">{{ number_format($row->cost) }}</td>
                    <td style="text-align:right;">{{ number_format($row->profit) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" style="text-align:right">TOTALS:</th>
                <th style="text-align:right">{{ number_format($reportData["total_opening_stock"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_new_stock"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_adj_stock"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_stock"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_qty_sold"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_closing_stock"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_revenue"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_cost"]) }}</th>
                <th style="text-align:right">{{ number_format($reportData["total_profit"]) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>


