<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Low Stock Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 9px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2d3748; color: #fff; padding: 6px 4px; text-align: left; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        td.right { text-align: right; }
        tr:nth-child(even) { background: #f7fafc; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Low Stock Report</h1>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th class="right">Qty On Hand</th>
                <th class="right">Reorder Level</th>
                <th class="right">Shortfall</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->item->name }}</td>
                    <td>{{ $row->item->category?->name ?? '—' }}</td>
                    <td class="right">{{ number_format($row->qty_on_hand) }}</td>
                    <td class="right">{{ number_format($row->item->reorder_level) }}</td>
                    <td class="right">{{ number_format(max(0, $row->item->reorder_level - $row->qty_on_hand)) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#999;padding:10px;">No low stock items found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
