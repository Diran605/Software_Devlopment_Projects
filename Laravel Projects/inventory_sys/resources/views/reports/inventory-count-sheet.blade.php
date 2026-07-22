<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Count Sheet — {{ $count->count_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #1a1a1a; line-height: 1.4; }

        /* ── Header ── */
        .header { margin-bottom: 14px; border-bottom: 3px solid #0d9488; padding-bottom: 10px; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .company-block { }
        .company-name { font-size: 17px; font-weight: 800; color: #0d9488; letter-spacing: 0.5px; }
        .doc-title { font-size: 12px; font-weight: 700; color: #1a1a1a; margin-top: 2px; text-transform: uppercase; letter-spacing: 1px; }
        .doc-number { font-size: 9px; color: #555; margin-top: 1px; }
        .stamp-box { border: 2px dashed #ccc; padding: 6px 16px; text-align: center; font-size: 8px; color: #999; line-height: 1.8; }
        .stamp-box strong { font-size: 9px; color: #555; }

        /* ── Meta grid ── */
        .meta-grid { display: flex; gap: 0; margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 3px; overflow: hidden; }
        .meta-item { flex: 1; padding: 5px 8px; border-right: 1px solid #e2e8f0; background: #f8fafc; }
        .meta-item:last-child { border-right: none; }
        .meta-label { font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; font-weight: 700; }
        .meta-value { font-size: 9px; color: #1a1a1a; font-weight: 600; margin-top: 1px; }

        /* ── Instructions ── */
        .instructions { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 3px; padding: 6px 10px; margin-bottom: 12px; font-size: 8px; color: #134e4a; }
        .instructions strong { font-weight: 700; }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead tr { background: #0d9488; }
        th { color: #fff; padding: 5px 4px; text-align: left; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
        th.right { text-align: right; }
        th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td { padding: 5px 4px; font-size: 8px; color: #1a1a1a; vertical-align: middle; }
        td.right { text-align: right; }
        td.center { text-align: center; }
        td.write-in { background: #fff; border-bottom: 1px solid #0d9488 !important; min-width: 50px; }
        td.write-notes { background: #fff; border-bottom: 1px solid #d1d5db; min-width: 70px; }

        /* Alternating item group shading */
        tr.group-header td { background: #e0f2fe; font-weight: 700; font-size: 8px; color: #0c4a6e; border-top: 1px solid #bae6fd; border-bottom: 1px solid #bae6fd; }

        /* ── Summary bar ── */
        .summary-bar { display: flex; gap: 0; border: 1px solid #0d9488; border-radius: 3px; overflow: hidden; margin-bottom: 16px; }
        .summary-item { flex: 1; padding: 6px 8px; border-right: 1px solid #99f6e4; text-align: center; }
        .summary-item:last-child { border-right: none; }
        .summary-num { font-size: 14px; font-weight: 800; color: #0d9488; }
        .summary-label { font-size: 7px; color: #6b7280; text-transform: uppercase; }

        /* ── Sign-off ── */
        .signoff { display: flex; gap: 20px; margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        .signoff-block { flex: 1; }
        .signoff-line { border-bottom: 1px solid #333; margin-bottom: 3px; height: 22px; }
        .signoff-label { font-size: 8px; color: #6b7280; }

        /* ── Footer ── */
        .footer { text-align: center; font-size: 7px; color: #9ca3af; margin-top: 14px; padding-top: 6px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-top">
            <div class="company-block">
                <div class="company-name">{{ $count->branch?->name ?? 'Inventory Management System' }}</div>
                <div class="doc-title">Physical Inventory Count Sheet</div>
                <div class="doc-number">{{ $count->count_number }} &nbsp;&bull;&nbsp; Generated: {{ now()->format('M d, Y H:i') }}</div>
            </div>
            <div class="stamp-box">
                <strong>COUNTED BY</strong><br>
                ___________________________<br>
                <strong>DATE</strong><br>
                ___________________________<br>
                <strong>VERIFIED BY</strong><br>
                ___________________________
            </div>
        </div>
    </div>

    {{-- Meta --}}
    <div class="meta-grid">
        <div class="meta-item">
            <div class="meta-label">Department</div>
            <div class="meta-value">{{ $count->department?->name ?? 'All Departments' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Count Date</div>
            <div class="meta-value">{{ $count->count_at?->format('M d, Y') ?? $count->created_at?->format('M d, Y') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Created By</div>
            <div class="meta-value">{{ $count->createdBy?->name ?? '—' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Status</div>
            <div class="meta-value">{{ ucfirst(str_replace('_', ' ', $count->status)) }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Total Lines</div>
            <div class="meta-value">{{ $count->lines->count() }}</div>
        </div>
    </div>

    {{-- Summary bar --}}
    @php
        $totalLines = $count->lines->count();
        $grouped = $count->lines->groupBy('item_id');
        $totalItems = $grouped->count();
    @endphp
    <div class="summary-bar">
        <div class="summary-item">
            <div class="summary-num">{{ $totalItems }}</div>
            <div class="summary-label">Unique Items</div>
        </div>
        <div class="summary-item">
            <div class="summary-num">{{ $totalLines }}</div>
            <div class="summary-label">Batch Lines</div>
        </div>
        <div class="summary-item">
            <div class="summary-num">0</div>
            <div class="summary-label">Lines Counted</div>
        </div>
        <div class="summary-item">
            <div class="summary-num">{{ $totalLines }}</div>
            <div class="summary-label">Lines Remaining</div>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="instructions">
        <strong>Instructions:</strong>
        Count each item and write the physical quantity in the <strong>"Physical Count"</strong> column.
        Write any discrepancies or observations in the <strong>"Notes"</strong> column.
        Do not alter the System Qty column. Return this sheet to your supervisor when complete.
    </div>

    {{-- Lines Table --}}
    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:21%">Item Name</th>
                <th style="width:10%">Category</th>
                <th style="width:7%">UOM</th>
                <th style="width:11%">Batch #</th>
                <th style="width:7%">Expiry</th>
                <th class="right" style="width:8%">System Qty</th>
                <th class="right" style="width:8%">Unit Cost</th>
                <th class="right" style="width:8%">Sell Price</th>
                <th class="center" style="width:8%">Physical Count</th>
                <th style="width:7%">Notes</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 0; $prevItemId = null; @endphp
            @foreach($count->lines as $line)
                @php $i++; $isNewItem = $line->item_id !== $prevItemId; $prevItemId = $line->item_id; @endphp
                @if($isNewItem)
                    <tr class="group-header">
                        <td colspan="11">&nbsp;&nbsp;{{ $line->item?->name ?? 'Unknown Item' }}</td>
                    </tr>
                @endif
                <tr>
                    <td>{{ $i }}</td>
                    <td style="color:#555">{{ $isNewItem ? '' : '' }}{{ $line->item?->name ?? '—' }}</td>
                    <td>{{ $line->item?->category?->name ?? '—' }}</td>
                    <td>{{ $line->item?->uom?->name ?? '—' }}</td>
                    <td>{{ $line->batchInventory?->batch_number ?? '—' }}</td>
                    <td>{{ $line->batchInventory?->expiry_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="right">{{ number_format($line->qty_system) }}</td>
                    <td class="right">{{ number_format($line->unit_cost, 0) }}</td>
                    <td class="right">{{ number_format($line->selling_price, 0) }}</td>
                    <td class="write-in center">&nbsp;</td>
                    <td class="write-notes">&nbsp;</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Sign-off section --}}
    <div class="signoff">
        <div class="signoff-block">
            <div class="signoff-line"></div>
            <div class="signoff-label">Counter Name &amp; Signature</div>
        </div>
        <div class="signoff-block">
            <div class="signoff-line"></div>
            <div class="signoff-label">Supervisor Name &amp; Signature</div>
        </div>
        <div class="signoff-block">
            <div class="signoff-line"></div>
            <div class="signoff-label">Date Completed</div>
        </div>
        <div class="signoff-block">
            <div class="signoff-line"></div>
            <div class="signoff-label">Date Verified</div>
        </div>
    </div>

    <div class="footer">
        {{ $count->branch?->name ?? 'Inventory Management System' }} &mdash; {{ $count->count_number }} &mdash; CONFIDENTIAL &mdash; DO NOT DISTRIBUTE &mdash; Printed {{ now()->format('M d, Y H:i') }}
    </div>

</body>
</html>
