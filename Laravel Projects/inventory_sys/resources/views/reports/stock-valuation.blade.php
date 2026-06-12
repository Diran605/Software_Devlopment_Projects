<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Valuation Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2d3748; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        td.right { text-align: right; }
        tr:nth-child(even) { background: #f7fafc; }
        tfoot tr { background: #edf2f7 !important; font-weight: bold; }
        tfoot td { border-top: 2px solid #2d3748; padding: 8px 6px; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Stock Valuation Report</h1>
        <p>As of {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>UoM</th>
                <th class="right">Qty On Hand</th>
                <th class="right">Unit Cost</th>
                <th class="right">Total Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->item?->name }}</td>
                    <td>{{ $row->item?->category?->name ?? 'Uncategorized' }}</td>
                    <td>{{ $row->item?->uom?->abbreviation ?? $row->item?->uom?->name }}</td>
                    <td class="right">{{ number_format($row->qty_on_hand) }}</td>
                    <td class="right">FCFA {{ number_format($row->item?->unit_cost ?? 0, 2) }}</td>
                    <td class="right">FCFA {{ number_format($row->qty_on_hand * ($row->item?->unit_cost ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#999;">No stock data found.</td></tr>
            @endforelse
        </tbody>
        @if($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">Grand Total</td>
                <td class="right">{{ number_format($data->sum('qty_on_hand')) }}</td>
                <td></td>
                <td class="right">FCFA {{ number_format($data->sum(fn($r) => $r->qty_on_hand * ($r->item?->unit_cost ?? 0)), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
