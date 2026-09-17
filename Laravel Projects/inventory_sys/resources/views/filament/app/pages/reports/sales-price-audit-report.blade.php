<x-filament-panels::page>
<style>
    /* ── Table base ── */
    .audit-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .audit-table th {
        padding: 0.75rem 1rem;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: rgb(var(--color-primary-700) / 0.25);
        color: rgb(var(--color-primary-300));
        border-bottom: 1px solid rgb(var(--color-primary-700) / 0.4);
        white-space: normal; line-height: 1.2; vertical-align: bottom;
    }
    .audit-table td { padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .text-right { text-align: right !important; }
    .text-center { text-align: center !important; }

    /* ── Product rows ── */
    .product-row {
        cursor: pointer;
        transition: background 0.15s;
    }
    .product-row:hover { background: rgb(var(--color-primary-600) / 0.12) !important; }
    .product-row.expanded { background: rgb(var(--color-primary-700) / 0.2) !important; }

    /* ── Drill-down sub-table ── */
    .drill-row td { padding: 0; border: none; }
    .drill-inner {
        background: rgba(0,0,0,0.25);
        border-left: 3px solid rgb(var(--color-primary-500));
        overflow: hidden;
        transition: max-height 0.3s ease, opacity 0.3s ease;
    }
    .drill-inner table { margin: 0; }
    .drill-inner th {
        font-size: 0.7rem;
        background: rgba(0,0,0,0.3);
        color: rgb(var(--color-primary-400));
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .drill-inner td {
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        color: rgba(255,255,255,0.75);
    }

    /* ── Badges ── */
    .badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 9999px; font-size: 0.7rem; font-weight: 600;
    }
    .badge-match     { background: rgb(16 185 129 / 0.15); color: #34d399; }
    .badge-shortfall { background: rgb(239 68 68 / 0.15);  color: #f87171; }
    .badge-surplus   { background: rgb(245 158 11 / 0.15); color: #fbbf24; }

    /* ── Footer totals ── */
    .totals-row td {
        border-top: 2px solid rgb(var(--color-primary-600) / 0.5);
        background: rgb(var(--color-primary-900) / 0.4);
        font-weight: 700;
        color: #fff;
    }

    /* ── Expand icon ── */
    .expand-icon { transition: transform 0.2s ease; display: inline-block; }
    .expand-icon.rotated { transform: rotate(90deg); }
</style>

<div class="space-y-6">
    {{-- Filter Card --}}
    <x-filament::card>
        <form wire:submit.prevent="submit" class="space-y-4">
            {{ $this->form }}
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem; flex-wrap: wrap;">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-funnel">
                    Generate Report
                </x-filament::button>
                <x-filament::button
                    wire:click="exportPdf('reports.sales-price-audit.pdf')"
                    color="success"
                    icon="heroicon-o-document-arrow-down">
                    Export Full PDF
                </x-filament::button>
                    <x-filament::button type="button" wire:click="exportCsv('sales-price-audit-report')" color="info" icon="heroicon-o-table-cells">
                        Export CSV
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="exportExcel('sales-price-audit-report')" color="warning" icon="heroicon-o-document-text">
                        Export Excel
                    </x-filament::button>
                <x-filament::button
                    wire:click="exportPdf('reports.sales-price-audit.pdf', { summary_only: 1 })"
                    color="secondary"
                    icon="heroicon-o-document-text">
                    Export Summary Only
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    {{-- Report card --}}
    <x-filament::card>
        @if($dateFrom || $dateTo)
        <p class="text-sm text-gray-400 mb-4">
            Period:
            <span class="text-white font-medium">
                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M d, Y') : '—' }}
                →
                {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('M d, Y') : '—' }}
            </span>
            &nbsp;|&nbsp; Click any product row to expand transactions.
        </p>
        @endif

        <div class="overflow-x-auto" x-data="{ expandedRows: [] }">
            <table class="audit-table w-full">
                <thead>
                    <tr>
                        <th style="width:2%"></th>
                        <th style="width:3%" class="text-center">S/N</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-right" style="min-width: 100px;">Qty Available<br><span style="font-weight:400;text-transform:none;font-size:0.65rem;opacity:0.7">(start + received)</span></th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right" style="min-width: 100px;">Qty Remaining<br><span style="font-weight:400;text-transform:none;font-size:0.65rem;opacity:0.7">(at period end)</span></th>
                        <th class="text-right" style="min-width: 110px;">Expected Revenue<br><span style="font-weight:400;text-transform:none;font-size:0.65rem;opacity:0.7">(at standard price)</span></th>
                        <th class="text-right" style="min-width: 110px;">Actual Revenue<br><span style="font-weight:400;text-transform:none;font-size:0.65rem;opacity:0.7">(as recorded)</span></th>
                        <th class="text-right">Variance</th>
                        <th class="text-center">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $product)
                        @php
                            $hasDiscrepancy = abs($product->discrepancy) > 0.01;
                            $isShortfall    = $product->discrepancy < -0.01;
                            $isSurplus      = $product->discrepancy > 0.01;
                            $pct            = $product->expected_revenue > 0
                                                ? round(abs($product->discrepancy) / $product->expected_revenue * 100, 1)
                                                : 0;
                        @endphp

                        {{-- Product summary row --}}
                        <tr
                            class="product-row"
                            x-on:click="expandedRows.includes({{ $product->item_id }}) ? expandedRows = expandedRows.filter(id => id !== {{ $product->item_id }}) : expandedRows.push({{ $product->item_id }})"
                            :class="{ 'expanded': expandedRows.includes({{ $product->item_id }}) }"
                        >
                            <td style="text-align:center; color: rgb(var(--color-primary-400));">
                                <span class="expand-icon" :class="{ 'rotated': expandedRows.includes({{ $product->item_id }}) }">▶</span>
                            </td>
                            <td style="text-align:center; color:#9ca3af; font-size:0.8rem;">{{ $loop->iteration }}</td>
                            <td style="font-weight:600; color:#f1f5f9;">{{ $product->item_name }}</td>
                            <td style="color:#9ca3af; font-size:0.8rem;">{{ $product->category_name ?? '—' }}</td>
                            <td style="text-align:right; color:#d1d5db;">{{ number_format($product->qty_available) }}</td>
                            <td style="text-align:right; color:#fff; font-weight:600;">{{ number_format($product->total_qty_sold) }}</td>
                            <td style="text-align:right; color:#d1d5db;">{{ number_format($product->qty_remaining) }}</td>
                            <td style="text-align:right; color:#d1d5db;">{{ number_format($product->expected_revenue, 0) }}</td>
                            <td style="text-align:right; color:#fff; font-weight:600;">{{ number_format($product->actual_revenue, 0) }}</td>
                            <td style="text-align:right;">
                                @if($isShortfall)
                                    <span style="color:#f87171; font-weight:600;">−{{ number_format(abs($product->discrepancy), 0) }}</span>
                                @elseif($isSurplus)
                                    <span style="color:#fbbf24; font-weight:600;">+{{ number_format($product->discrepancy, 0) }}</span>
                                @else
                                    <span style="color:#6b7280;">0</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if($isShortfall)
                                    <span class="badge badge-shortfall">⚠ Below Standard ({{ $pct }}%)</span>
                                @elseif($isSurplus)
                                    <span class="badge badge-surplus">↑ Above Standard ({{ $pct }}%)</span>
                                @else
                                    <span class="badge badge-match">✓ Price Matches</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Drill-down row --}}
                        <tr class="drill-row" x-show="expandedRows.includes({{ $product->item_id }})" x-cloak>
                            <td colspan="11" style="padding:0;border:none;">
                                <div class="drill-inner" x-show="expandedRows.includes({{ $product->item_id }})" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0">
                                    <table class="w-full">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Order #</th>
                                                <th>Cashier</th>
                                                <th class="text-right">Qty Sold</th>
                                                <th class="text-right">Standard Price</th>
                                                <th class="text-right">Price Used</th>
                                                <th class="text-right">Expected Total</th>
                                                <th class="text-right">Actual Total</th>
                                                <th class="text-right">Line Variance</th>
                                                <th class="text-center">Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($product->transactions as $tx)
                                                @php
                                                    $txDisc = round($tx->line_discrepancy, 2);
                                                    $priceChanged = abs($tx->used_price - $tx->standard_price) > 0.01;
                                                @endphp
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($tx->sold_at)->format('M d, Y H:i') }}</td>
                                                    <td style="font-family:monospace; color:rgb(var(--color-primary-300));">{{ $tx->order_number }}</td>
                                                    <td>{{ $tx->cashier ?? 'Unknown' }}</td>
                                                    <td style="text-align:right;">{{ number_format($tx->qty_sold) }}</td>
                                                    <td style="text-align:right;">{{ number_format($tx->standard_price, 0) }}</td>
                                                    <td style="text-align:right; {{ $priceChanged ? 'color:#fbbf24; font-weight:600;' : '' }}">
                                                        {{ number_format($tx->used_price, 0) }}
                                                    </td>
                                                    <td style="text-align:right; color:#9ca3af;">{{ number_format($tx->expected_line_total, 0) }}</td>
                                                    <td style="text-align:right; color:#fff;">{{ number_format($tx->actual_line_total, 0) }}</td>
                                                    <td style="text-align:right;">
                                                        @if($txDisc < -0.01)
                                                            <span style="color:#f87171;">−{{ number_format(abs($txDisc), 0) }}</span>
                                                        @elseif($txDisc > 0.01)
                                                            <span style="color:#fbbf24;">+{{ number_format($txDisc, 0) }}</span>
                                                        @else
                                                            <span style="color:#6b7280;">—</span>
                                                        @endif
                                                    </td>
                                                    <td style="text-align:center;">
                                                        @if($txDisc < -0.01)
                                                            <span class="badge badge-shortfall">Price Altered ↓</span>
                                                        @elseif($txDisc > 0.01)
                                                            <span class="badge badge-surplus">Price Altered ↑</span>
                                                        @else
                                                            <span class="badge badge-match">OK</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="11" class="text-center p-8" style="color:rgba(255,255,255,0.3)">
                                No sales data found for the selected period and filters.
                            </td>
                        </tr>
                    @endforelse

                {{-- Totals footer --}}
                @if($reportData->isNotEmpty())
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="4" style="text-align:right; font-weight:700; color:#fff; padding-right: 1rem;">Grand Totals:</td>
                        <td style="text-align:right; color:#d1d5db;">{{ number_format($reportData->sum('qty_available')) }}</td>
                        <td style="text-align:right; color:#fff; font-weight:700;">{{ number_format($reportData->sum('total_qty_sold')) }}</td>
                        <td style="text-align:right; color:#d1d5db;">{{ number_format($reportData->sum('qty_remaining')) }}</td>
                        <td style="text-align:right; color:#d1d5db;">{{ number_format($reportData->sum('expected_revenue'), 0) }}</td>
                        <td style="text-align:right; color:#fff; font-weight:700;">{{ number_format($reportData->sum('actual_revenue'), 0) }}</td>
                        <td style="text-align:right;">
                            @php $totalDisc = round($reportData->sum('actual_revenue') - $reportData->sum('expected_revenue'), 2); @endphp
                            @if($totalDisc < -0.01)
                                <span style="color:#f87171; font-weight:700;">−{{ number_format(abs($totalDisc), 0) }}</span>
                            @elseif($totalDisc > 0.01)
                                <span style="color:#fbbf24; font-weight:700;">+{{ number_format($totalDisc, 0) }}</span>
                            @else
                                <span style="color:#6b7280;">0</span>
                            @endif
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </x-filament::card>
</div>

</x-filament-panels::page>
