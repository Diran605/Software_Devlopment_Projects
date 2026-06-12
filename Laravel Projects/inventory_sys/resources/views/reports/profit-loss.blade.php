<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .section-header { background: #2d3748; color: #fff; }
        .section-header td { padding: 8px 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .line-item td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; }
        .line-item td:last-child { text-align: right; font-weight: 600; }
        .indent td:first-child { padding-left: 25px; }
        .subtotal { background: #edf2f7; font-weight: bold; }
        .subtotal td { padding: 8px 10px; border-top: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; }
        .subtotal td:last-child { text-align: right; }
        .grand-total { background: #2d3748; color: #fff; }
        .grand-total td { padding: 10px; font-size: 14px; font-weight: bold; }
        .grand-total td:last-child { text-align: right; }
        .positive { color: #276749; }
        .negative { color: #9b2c2c; }
        .expense-table th { background: #4a5568; color: #fff; padding: 6px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        .expense-table th.right { text-align: right; }
        .expense-table td { padding: 5px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .expense-table td.right { text-align: right; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch->name ?? 'Branch' }} — Profit & Loss Statement</h1>
        <p>Period: {{ $filters['date_from'] ?? 'All time' }} to {{ $filters['date_to'] ?? 'Present' }}</p>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <table>
        {{-- Revenue --}}
        <tr class="section-header">
            <td colspan="2">Revenue</td>
        </tr>
        <tr class="line-item indent">
            <td>Sales Revenue</td>
            <td>FCFA {{ number_format($data['revenue'], 2) }}</td>
        </tr>
        <tr class="subtotal">
            <td>Total Revenue</td>
            <td style="text-align:right">FCFA {{ number_format($data['revenue'], 2) }}</td>
        </tr>

        {{-- COGS --}}
        <tr class="section-header">
            <td colspan="2">Cost of Goods Sold</td>
        </tr>
        <tr class="line-item indent">
            <td>Direct Cost of Items Sold</td>
            <td>(FCFA {{ number_format($data['cogs'], 2) }})</td>
        </tr>
        <tr class="subtotal">
            <td>Total Cost of Goods Sold</td>
            <td style="text-align:right">(FCFA {{ number_format($data['cogs'], 2) }})</td>
        </tr>

        {{-- Gross Profit --}}
        <tr class="subtotal" style="background:#d4edda;">
            <td style="font-size:13px;">Gross Profit</td>
            <td style="text-align:right;font-size:13px;" class="{{ $data['grossProfit'] >= 0 ? 'positive' : 'negative' }}">
                FCFA {{ number_format($data['grossProfit'], 2) }}
            </td>
        </tr>

        {{-- Operating Expenses --}}
        <tr class="section-header">
            <td colspan="2">Operating Expenses</td>
        </tr>
        @forelse($data['expenseBreakdown'] as $expense)
            <tr class="line-item indent">
                <td>{{ $expense->category_name }}</td>
                <td>(FCFA {{ number_format($expense->total_amount, 2) }})</td>
            </tr>
        @empty
            <tr class="line-item indent">
                <td colspan="2" style="color:#999;font-style:italic;">No expenses recorded</td>
            </tr>
        @endforelse
        <tr class="subtotal">
            <td>Total Operating Expenses</td>
            <td style="text-align:right">(FCFA {{ number_format($data['totalExpenses'], 2) }})</td>
        </tr>

        {{-- Net Profit --}}
        <tr class="grand-total">
            <td>Net Profit / (Loss)</td>
            <td style="text-align:right">
                @if($data['netProfit'] < 0)
                    (FCFA {{ number_format(abs($data['netProfit']), 2) }})
                @else
                    FCFA {{ number_format($data['netProfit'], 2) }}
                @endif
            </td>
        </tr>
    </table>

    @if($data['expenseBreakdown']->isNotEmpty())
    <h3 style="font-size:12px;margin-bottom:8px;">Expense Breakdown by Category</h3>
    <table class="expense-table">
        <thead>
            <tr>
                <th>Category</th>
                <th class="right">Amount</th>
                <th class="right">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['expenseBreakdown'] as $expense)
                <tr>
                    <td>{{ $expense->category_name }}</td>
                    <td class="right">FCFA {{ number_format($expense->total_amount, 2) }}</td>
                    <td class="right">{{ $data['totalExpenses'] > 0 ? number_format(($expense->total_amount / $data['totalExpenses']) * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Inventory Management System &mdash; Confidential
    </div>
</body>
</html>
