<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Count — {{ $count->count_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h1 { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
        .meta { margin-bottom: 12px; font-size: 9px; color: #555; }
        .meta span { display: inline-block; margin-right: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #2d3748; color: #fff; padding: 5px 3px; text-align: left; font-size: 8px; font-weight: 600; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 4px 3px; border-bottom: 1px solid #e2e8f0; font-size: 8px; }
        td.right { text-align: right; }
        tr:nth-child(even) { background: #f7fafc; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 16px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $count->branch?->name ?? 'Branch' }} — Inventory Count</h1>
        <p>{{ $count->count_number }} &mdash; {{ ucfirst(str_replace('_', ' ', $count->status)) }}</p>
    </div>

    <div class="meta">
        <span><strong>Department:</strong> {{ $count->department?->name ?? 'All' }}</span>
        <span><strong>Created:</strong> {{ $count->created_at?->format('M d, Y') }}</span>
        <span><strong>Created By:</strong> {{ $count->createdBy?->name ?? '—' }}</span>
    </div>

    @php $summary = $count->countSummary(); $variance = $count->varianceSummary(); @endphp
    <div class="meta">
        <span><strong>Lines:</strong> {{ $summary['total_lines'] ?? 0 }}</span>
        <span><strong>Counted:</strong> {{ $summary['counted_lines'] ?? 0 }}</span>
        <span><strong>Variances:</strong> {{ $variance['variance_count'] ?? 0 }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>SKU</th>
                <th>Batch #</th>
                <th>Expiry</th>
                <th class="right">System Qty</th>
                <th class="right">Counted Qty</th>
                <th class="right">Variance</th>
                <th class="right">Unit Cost</th>
                <th class="right">Variance Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach($count->lines as $line)
                <tr>
                    <td>{{ $line->item?->name ?? '—' }}</td>
                    <td>{{ $line->item?->sku ?? '—' }}</td>
                    <td>{{ $line->batchInventory?->batch_number ?? '—' }}</td>
                    <td>{{ $line->batchInventory?->expiry_date?->format('M d, Y') ?? '—' }}</td>
                    <td class="right">{{ number_format($line->qty_system) }}</td>
                    <td class="right">{{ $line->qty_counted !== null ? number_format($line->qty_counted) : 'Pending' }}</td>
                    <td class="right">{{ $line->qty_variance !== null ? number_format($line->qty_variance) : '—' }}</td>
                    <td class="right">FCFA {{ number_format($line->unit_cost, 2) }}</td>
                    <td class="right">{{ $line->variance_value !== null ? 'FCFA '.number_format($line->variance_value, 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
