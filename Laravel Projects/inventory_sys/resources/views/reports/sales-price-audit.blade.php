<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Price Audit — {{ $from }} to {{ $to }}</title>
    <style>

        @page { margin: 20px; }
        body { padding: 0 !important; margin: 0 !important; font-size: 12px !important; }
        table { width: 100% !important; max-width: 100% !important; table-layout: fixed; word-wrap: break-word; }
        th, td { word-wrap: break-word; overflow-wrap: break-word; font-size: 11px !important; padding: 6px 4px !important; }
        th { font-size: 11px !important; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5px; color: #1a1a1a; line-height: 1.4; }

        /* Header */
        .header { border-bottom: 3px solid #0d9488; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header-left .company { font-size: 16px; font-weight: 800; color: #0d9488; }
        .header-left .title   { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 2px; }
        .header-left .subtitle{ font-size: 8px; color: #6b7280; margin-top: 1px; }
        .header-right { text-align: right; font-size: 8px; color: #6b7280; }

        /* Meta strip */
        .meta { display: flex; gap: 0; margin-bottom: 10px; border: 1px solid #e2e8f0; border-radius: 3px; overflow: hidden; }
        .meta-item { flex: 1; padding: 5px 8px; border-right: 1px solid #e2e8f0; background: #f8fafc; }
        .meta-item:last-child { border-right: none; }
        .meta-label { font-size: 7px; text-transform: uppercase; color: #9ca3af; font-weight: 700; }
        .meta-value { font-size: 9px; color: #111; font-weight: 600; margin-top: 1px; }

        /* Product block */
        .product-block { margin-bottom: 14px; page-break-inside: avoid; }
        .product-header {
            background: #0d9488; color: #fff;
            padding: 5px 8px; font-size: 9px; font-weight: 700;
            display: flex; justify-content: space-between; align-items: center;
            border-radius: 2px 2px 0 0;
        }
        .product-header .badge {
            font-size: 7.5px; padding: 2px 6px; border-radius: 9999px;
            font-weight: 700; background: rgba(255,255,255,0.2);
        }
        .product-header .badge-shortfall { background: #fee2e2; color: #b91c1c; }
        .product-header .badge-surplus   { background: #fef3c7; color: #92400e; }
        .product-header .badge-match     { background: #d1fae5; color: #065f46; }

        /* Summary row under product header */
        .product-summary {
            background: #f0fdfa; border-left: 1px solid #0d9488; border-right: 1px solid #0d9488;
            display: flex; gap: 0;
        }
        .ps-item { flex: 1; padding: 4px 8px; border-right: 1px solid #99f6e4; }
        .ps-item:last-child { border-right: none; }
        .ps-label { font-size: 6.5px; text-transform: uppercase; color: #6b7280; }
        .ps-value { font-size: 8.5px; font-weight: 700; color: #134e4a; }

        /* Transaction table */
        table { width: 100%; border-collapse: collapse; border: 1px solid #0d9488; border-top: none; border-radius: 0 0 2px 2px; overflow: hidden; }
        th { background: #134e4a; color: #fff; padding: 4px 5px; text-align: left; font-size: 7px; text-transform: uppercase; }
        th.r { text-align: right; }
        td { padding: 3.5px 5px; font-size: 7.5px; border-bottom: 1px solid #e5e7eb; }
        td.r { text-align: right; }
        tr:nth-child(even) { background: #f9fafb; }
        .altered { color: #b91c1c; font-weight: 700; }
        .ok-price { color: #065f46; }

        /* Grand totals footer */
        .grand-totals { margin-top: 14px; border: 2px solid #0d9488; border-radius: 3px; overflow: hidden; }
        .grand-totals-header { background: #0d9488; color: #fff; padding: 5px 8px; font-weight: 700; font-size: 9px; }
        .grand-totals table { border: none; }
        .grand-totals th { background: #134e4a; }
        .grand-totals td { background: #f0fdfa; }

        /* Footer */
        .footer { text-align: center; font-size: 7px; color: #9ca3af; margin-top: 14px; padding-top: 6px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <div class="company">{{ is_object($branch) ? $branch->name : ($branch ?? 'Inventory Management System') }}</div>
            <div class="title">Sales Price Audit Report</div>
            <div class="subtitle">
                Period: {{ $from ? \Carbon\Carbon::parse($from)->format('M d, Y') : 'All Time' }}
                — {{ $to ? \Carbon\Carbon::parse($to)->format('M d, Y') : 'Today' }}
                @if($department) &nbsp;| Dept: {{ $department }} @endif
                @if($category)   &nbsp;| Category: {{ $category }} @endif
            </div>
        </div>
        <div class="header-right">
            Generated: {{ now()->format('M d, Y H:i') }}<br>
            CONFIDENTIAL
        </div>
    </div>

    <div class="meta">
        <div class="meta-item">
            <div class="meta-label">Products</div>
            <div class="meta-value">{{ $reportData->count() }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Total Qty Sold</div>
            <div class="meta-value">{{ number_format($reportData->sum('total_qty_sold')) }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Expected Revenue</div>
            <div class="meta-value">{{ number_format($reportData->sum('expected_revenue'), 0) }} FCFA</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Actual Revenue</div>
            <div class="meta-value">{{ number_format($reportData->sum('actual_revenue'), 0) }} FCFA</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Net Variance</div>
            @php $netDisc = $reportData->sum('actual_revenue') - $reportData->sum('expected_revenue'); @endphp
            <div class="meta-value" style="color: {{ $netDisc < -0.01 ? '#b91c1c' : ($netDisc > 0.01 ? '#92400e' : '#065f46') }}">
                {{ $netDisc >= 0 ? '+' : '' }}{{ number_format($netDisc, 0) }} FCFA
            </div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Items w/ Discrepancy</div>
            <div class="meta-value">{{ $reportData->filter(fn($p) => abs($p->discrepancy) > 0.01)->count() }}</div>
        </div>
    </div>

    {{-- One block per product --}}
    @if($summaryOnly ?? false)
        {{-- Unified summary table showing all products --}}
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #0d9488; margin-top: 10px;">
            <thead>
                <tr style="background: #134e4a; color: #fff;">
                    <th style="padding: 5px 8px; font-size: 8px; text-align: left; width: 3%;">S/N</th>
                    <th style="padding: 5px 8px; font-size: 8px; text-align: left;">Product</th>
                    <th style="padding: 5px 8px; font-size: 8px; text-align: left;">Category</th>
                    <th class="r" style="padding: 5px 8px; font-size: 8px; text-align: right;">Qty Available<br><span style="font-weight:normal;font-size:6px;color:#99f6e4">(start + received)</span></th>
                    <th class="r" style="padding: 5px 8px; font-size: 8px; text-align: right;">Qty Sold</th>
                    <th class="r" style="padding: 5px 8px; font-size: 8px; text-align: right;">Qty Remaining</th>
                    <th class="r" style="padding: 5px 8px; font-size: 8px; text-align: right;">Standard Price</th>
                    <th class="r" style="padding: 5px 8px; font-size: 8px; text-align: right;">Expected Rev</th>
                    <th class="r" style="padding: 5px 8px; font-size: 8px; text-align: right;">Actual Rev</th>
                    <th class="r" style="padding: 5px 8px; font-size: 8px; text-align: right;">Variance</th>
                    <th style="padding: 5px 8px; font-size: 8px; text-align: center;">Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $product)
                    @php
                        $isShortfall = $product->discrepancy < -0.01;
                        $isSurplus   = $product->discrepancy > 0.01;
                        $pct = $product->expected_revenue > 0
                                 ? round(abs($product->discrepancy) / $product->expected_revenue * 100, 1) : 0;
                    @endphp
                    <tr style="background: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                        <td style="padding: 5px 8px; font-size: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $loop->iteration }}</td>
                        <td style="padding: 5px 8px; font-size: 8px; font-weight: bold; border-bottom: 1px solid #e2e8f0;">{{ $product->item_name }}</td>
                        <td style="padding: 5px 8px; font-size: 8px; color: #64748b; border-bottom: 1px solid #e2e8f0;">{{ $product->category_name ?? '—' }}</td>
                        <td class="r" style="padding: 5px 8px; font-size: 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ number_format($product->qty_available) }}</td>
                        <td class="r" style="padding: 5px 8px; font-size: 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #e2e8f0;">{{ number_format($product->total_qty_sold) }}</td>
                        <td class="r" style="padding: 5px 8px; font-size: 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ number_format($product->qty_remaining) }}</td>
                        <td class="r" style="padding: 5px 8px; font-size: 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ number_format($product->standard_price, 0) }}</td>
                        <td class="r" style="padding: 5px 8px; font-size: 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ number_format($product->expected_revenue, 0) }}</td>
                        <td class="r" style="padding: 5px 8px; font-size: 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #e2e8f0;">{{ number_format($product->actual_revenue, 0) }}</td>
                        <td class="r" style="padding: 5px 8px; font-size: 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #e2e8f0; color: {{ $isShortfall ? '#b91c1c' : ($isSurplus ? '#b45309' : '#047857') }}">
                            {{ $product->discrepancy >= 0 ? '+' : '' }}{{ number_format($product->discrepancy, 0) }}
                        </td>
                        <td style="padding: 5px 8px; font-size: 8px; text-align: center; border-bottom: 1px solid #e2e8f0;">
                            @if($isShortfall)
                                <span style="color:#b91c1c; font-weight:bold;">⚠ Below ({{ $pct }}%)</span>
                            @elseif($isSurplus)
                                <span style="color:#b45309; font-weight:bold;">↑ Above ({{ $pct }}%)</span>
                            @else
                                <span style="color:#047857;">✓ Match</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        {{-- Full report with separate cards and sub-tables --}}
        @foreach($reportData as $product)
            @php
                $isShortfall = $product->discrepancy < -0.01;
                $isSurplus   = $product->discrepancy > 0.01;
                $pct = $product->expected_revenue > 0
                         ? round(abs($product->discrepancy) / $product->expected_revenue * 100, 1) : 0;
            @endphp

            <div class="product-block">
                <div class="product-header">
                    <span>{{ $loop->iteration }}. {{ $product->item_name }} @if($product->category_name) &mdash; <em style="opacity:0.8;font-size:8px">{{ $product->category_name }}</em> @endif</span>
                    <span>
                        @if($isShortfall)
                            <span class="badge badge-shortfall">⚠ Below Standard ({{ $pct }}%)</span>
                        @elseif($isSurplus)
                            <span class="badge badge-surplus">↑ Above Standard ({{ $pct }}%)</span>
                        @else
                            <span class="badge badge-match">✓ Price Matches</span>
                        @endif
                    </span>
                </div>

                <div class="product-summary">
                    <div class="ps-item"><div class="ps-label">Qty Available</div><div class="ps-value">{{ number_format($product->qty_available) }}</div></div>
                    <div class="ps-item"><div class="ps-label">Qty Sold</div><div class="ps-value">{{ number_format($product->total_qty_sold) }}</div></div>
                    <div class="ps-item"><div class="ps-label">Qty Remaining</div><div class="ps-value">{{ number_format($product->qty_remaining) }}</div></div>
                    <div class="ps-item"><div class="ps-label">Standard Price</div><div class="ps-value">{{ number_format($product->standard_price, 0) }}</div></div>
                    <div class="ps-item"><div class="ps-label">Expected Revenue</div><div class="ps-value">{{ number_format($product->expected_revenue, 0) }}</div></div>
                    <div class="ps-item"><div class="ps-label">Actual Revenue</div><div class="ps-value">{{ number_format($product->actual_revenue, 0) }}</div></div>
                    <div class="ps-item">
                        <div class="ps-label">Variance</div>
                        <div class="ps-value" style="color:{{ $isShortfall ? '#b91c1c' : ($isSurplus ? '#92400e' : '#065f46') }}">
                            {{ $product->discrepancy >= 0 ? '+' : '' }}{{ number_format($product->discrepancy, 0) }}
                        </div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Order #</th>
                            <th>Cashier</th>
                            <th class="r">Qty</th>
                            <th class="r">Std Price</th>
                            <th class="r">Price Used</th>
                            <th class="r">Expected Total</th>
                            <th class="r">Actual Total</th>
                            <th class="r">Variance</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->transactions as $tx)
                            @php
                                $txDisc       = round($tx->line_discrepancy, 2);
                                $priceChanged = abs($tx->used_price - $tx->standard_price) > 0.01;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($tx->sold_at)->format('d/m/Y H:i') }}</td>
                                <td style="font-family:monospace">{{ $tx->order_number }}</td>
                                <td>{{ $tx->cashier ?? '—' }}</td>
                                <td class="r">{{ number_format($tx->qty_sold) }}</td>
                                <td class="r">{{ number_format($tx->standard_price, 0) }}</td>
                                <td class="r {{ $priceChanged ? 'altered' : 'ok-price' }}">{{ number_format($tx->used_price, 0) }}</td>
                                <td class="r">{{ number_format($tx->expected_line_total, 0) }}</td>
                                <td class="r" style="font-weight:600">{{ number_format($tx->actual_line_total, 0) }}</td>
                                <td class="r {{ $txDisc < -0.01 ? 'altered' : ($txDisc > 0.01 ? '' : 'ok-price') }}">
                                    {{ $txDisc >= 0 ? '+' : '' }}{{ number_format($txDisc, 0) }}
                                </td>
                                <td>
                                    @if($txDisc < -0.01) Price Altered ↓
                                    @elseif($txDisc > 0.01) Price Altered ↑
                                    @else OK
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <div class="footer">
        Inventory Management System &mdash; Sales Price Audit &mdash; CONFIDENTIAL &mdash; Printed {{ now()->format('M d, Y H:i') }}
    </div>

</body>
</html>
