<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expense Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .meta { margin-bottom: 12px; font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #2d3748; color: #fff; padding: 6px 4px; text-align: left; font-size: 9px; }
        th.right { text-align: right; }
        td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        td.right { text-align: right; }
        tfoot tr { background: #edf2f7 !important; font-weight: bold; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Expense Report</h1>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="meta">
        @if(!empty($filters['date_from']))<strong>From:</strong> {{ $filters['date_from'] }} &nbsp; @endif
        @if(!empty($filters['date_to']))<strong>To:</strong> {{ $filters['date_to'] }} @endif
        <br><strong>Total Expenses:</strong> FCFA {{ number_format($data['total_amount'], 2) }} |
        <strong>Entries:</strong> {{ number_format($data['expense_count']) }}
    </div>

    <h3 style="margin-bottom:8px;">Summary by Category</h3>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="right">Count</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['category_summary'] as $row)
                <tr>
                    <td>{{ $row->category_name }}</td>
                    <td class="right">{{ number_format($row->expense_count) }}</td>
                    <td class="right">FCFA {{ number_format($row->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="margin-bottom:8px;">Expense Lines</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Category</th>
                <th>Payee</th>
                <th>Description</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['rows'] as $expense)
                <tr>
                    <td>{{ $expense->expense_date?->format('M d, Y') }}</td>
                    <td>{{ $expense->reference_number ?? '—' }}</td>
                    <td>{{ $expense->category?->name ?? 'Uncategorized' }}</td>
                    <td>{{ $expense->payee ?? '—' }}</td>
                    <td>{{ $expense->description ?? '—' }}</td>
                    <td class="right">FCFA {{ number_format($expense->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#999;padding:10px;">No expenses found.</td></tr>
            @endforelse
        </tbody>
        @if($data['rows']->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="5">Total</td>
                <td class="right">FCFA {{ number_format($data['total_amount'], 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">Inventory Management System — Confidential</div>
</body>
</html>
