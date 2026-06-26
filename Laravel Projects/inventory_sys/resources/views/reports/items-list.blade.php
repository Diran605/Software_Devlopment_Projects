<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Items List Report</title>
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
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Items List Report</h1>
        <p>As of {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Category</th>
                <th>UoM</th>
                <th class="right">Qty On Hand</th>
                <th class="right">Unit Cost</th>
                <th class="right">Min Selling Price</th>
                <th class="right">Selling Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td>{{ $item->category?->name ?? 'Uncategorized' }}</td>
                    <td>{{ $item->uom?->abbreviation ?? $item->uom?->name }}</td>
                    <td class="right">{{ number_format($item->qty_on_hand) }}</td>
                    <td class="right">FCFA {{ number_format($item->unit_cost, 2) }}</td>
                    <td class="right">FCFA {{ number_format($item->min_selling_price, 2) }}</td>
                    <td class="right">FCFA {{ number_format($item->selling_price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#999;">No items found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>