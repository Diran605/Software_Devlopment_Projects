<x-filament-panels::page>
<style>
    .dsr-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .dsr-table th {
        padding: 0.6rem 0.75rem;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: rgba(30,41,59,0.9);
        color: rgb(148,163,184);
        border-bottom: 1px solid rgba(255,255,255,0.08);
        white-space: nowrap;
    }
    .dsr-table td {
        padding: 0.6rem 0.75rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        color: #d1d5db;
        vertical-align: middle;
    }
    .dsr-table tbody tr:hover { background: rgba(255,255,255,0.03); }
    .dsr-table tfoot td {
        padding: 0.75rem;
        font-weight: 700;
        color: #fff;
        border-top: 2px solid rgba(255,255,255,0.15);
        background: rgba(30,41,59,0.8);
    }
    .text-right  { text-align: right  !important; }
    .text-center { text-align: center !important; }
    .dsr-badge {
        display: inline-flex; align-items: center;
        padding: 2px 8px; border-radius: 9999px;
        font-size: 0.68rem; font-weight: 600;
    }
    /* Summary card container */
    .dsr-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .dsr-card {
        background: rgba(30,41,59,0.7);
        border-radius: 0.75rem;
        padding: 1rem 1.1rem;
        border: 1px solid rgba(255,255,255,0.08);
        min-width: 0;
        overflow: hidden;
    }
    .dsr-card-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 0.25rem;
    }
    .dsr-card-value {
        font-size: 1.4rem;
        font-weight: 700;
        word-break: break-all;
        line-height: 1.2;
    }
    .dsr-card-sub {
        font-size: 0.72rem;
        color: #64748b;
        margin-top: 0.2rem;
    }
</style>

<div class="space-y-5">
    {{-- Filter card --}}
    <x-filament::card>
        <form wire:submit.prevent="submit" class="space-y-4">
            {{ $this->form }}
            <div class="flex items-center gap-3 mt-4 flex-wrap">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-funnel">
                    Generate Report
                </x-filament::button>
                <x-filament::button type="button" wire:click="exportPdf('reports.detailed-sales.pdf')" color="success" icon="heroicon-o-document-arrow-down">
                    Export PDF
                </x-filament::button>
                <x-filament::button type="button" wire:click="exportCsv('detailed-sales-report')" color="info" icon="heroicon-o-table-cells">
                    Export CSV
                </x-filament::button>
                <x-filament::button type="button" wire:click="exportExcel('detailed-sales-report')" color="warning" icon="heroicon-o-document-text">
                    Export Excel
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    @php $data = $reportData; @endphp

    {{-- Summary Cards --}}
    <div class="dsr-cards">
        <div class="dsr-card">
            <div class="dsr-card-label">Items Tracked</div>
            <div class="dsr-card-value" style="color:#fff;">{{ $data['rows']->count() }}</div>
            <div class="dsr-card-sub">{{ $data['date_from'] }} – {{ $data['date_to'] }}</div>
        </div>
        <div class="dsr-card">
            <div class="dsr-card-label">Opening Stock</div>
            <div class="dsr-card-value" style="color:#94a3b8;">{{ number_format($data['total_opening_stock']) }}</div>
            <div class="dsr-card-sub">Units at period start</div>
        </div>
        <div class="dsr-card">
            <div class="dsr-card-label">New Stock (GRN)</div>
            <div class="dsr-card-value" style="color:#38bdf8;">{{ number_format($data['total_new_stock']) }}</div>
            <div class="dsr-card-sub">Received this period</div>
        </div>
        <div class="dsr-card">
            <div class="dsr-card-label">Total Available</div>
            <div class="dsr-card-value" style="color:#e2e8f0;">{{ number_format($data['total_stock']) }}</div>
            <div class="dsr-card-sub">Opening + New</div>
        </div>
        <div class="dsr-card">
            <div class="dsr-card-label">Qty Sold</div>
            <div class="dsr-card-value" style="color:#f59e0b;">{{ number_format($data['total_qty_sold']) }}</div>
            <div class="dsr-card-sub">Units dispatched</div>
        </div>
        <div class="dsr-card">
            <div class="dsr-card-label">Closing Stock</div>
            <div class="dsr-card-value" style="color:#a78bfa;">{{ number_format($data['total_closing_stock']) }}</div>
            <div class="dsr-card-sub">Remaining at period end</div>
        </div>
        <div class="dsr-card">
            <div class="dsr-card-label">Revenue</div>
            <div class="dsr-card-value" style="color:#34d399;">FCFA {{ number_format($data['total_revenue'], 0) }}</div>
            <div class="dsr-card-sub">Total sales value</div>
        </div>
        <div class="dsr-card">
            <div class="dsr-card-label">Cost (COGS)</div>
            <div class="dsr-card-value" style="color:#fb7185;">FCFA {{ number_format($data['total_cost'], 0) }}</div>
            <div class="dsr-card-sub">Direct cost of items sold</div>
        </div>
        <div class="dsr-card" style="border-color: {{ $data['total_profit'] >= 0 ? 'rgba(4,120,87,0.5)' : 'rgba(190,18,60,0.5)' }};">
            <div class="dsr-card-label">Net Profit</div>
            <div class="dsr-card-value" style="color: {{ $data['total_profit'] >= 0 ? '#34d399' : '#fb7185' }};">
                FCFA {{ number_format($data['total_profit'], 0) }}
            </div>
            <div class="dsr-card-sub">{{ $data['overall_margin'] }}% overall margin</div>
        </div>
    </div>

    {{-- Main Table --}}
    <x-filament::card>
        <h3 style="font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 1rem;">
            Detailed Sales Breakdown
            <span style="font-size: 0.75rem; font-weight: 400; color: #64748b; margin-left: 0.5rem;">
                {{ $data['date_from'] }} to {{ $data['date_to'] }}
            </span>
        </h3>

        @if($data['rows']->isEmpty())
            <div style="text-align: center; padding: 3rem; color: #475569;">
                No stock activity found for the selected period and filters.
            </div>
        @else
        <div style="overflow-x: auto;">
            <table class="dsr-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:3%;">#</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th class="text-right">Opening<br>Stock</th>
                        <th class="text-right">New Stock<br>(GRN)</th>
                        <th class="text-right">Total<br>Available</th>
                        <th class="text-right">Qty<br>Sold</th>
                        <th class="text-right">Closing<br>Stock</th>
                        <th class="text-right">Selling<br>Price</th>
                        <th class="text-right">Cost<br>Price</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Cost</th>
                        <th class="text-right">Profit</th>
                        <th class="text-right">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['rows'] as $row)
                    @php
                        $isLoss = $row->profit < 0;
                        $isLow  = !$isLoss && $row->margin_pct < 20;
                    @endphp
                    <tr style="{{ $isLoss ? 'background: rgba(127,29,29,0.15);' : ($isLow ? 'background: rgba(120,53,15,0.12);' : '') }}">
                        <td class="text-center" style="color: #64748b;">{{ $loop->iteration }}</td>
                        <td style="color: #f1f5f9; font-weight: 600;">{{ $row->item_name }}</td>
                        <td style="color: #94a3b8; font-size: 0.78rem;">{{ $row->category_name }}</td>
                        <td class="text-right" style="color: #94a3b8;">{{ number_format($row->opening_stock) }}</td>
                        <td class="text-right" style="color: #38bdf8;">{{ number_format($row->new_stock) }}</td>
                        <td class="text-right" style="color: #e2e8f0; font-weight: 600;">{{ number_format($row->total_stock) }}</td>
                        <td class="text-right" style="color: #f59e0b; font-weight: 600;">{{ number_format($row->qty_sold) }}</td>
                        <td class="text-right" style="color: {{ $row->closing_stock > 0 ? '#a78bfa' : '#fb7185' }}; font-weight: 600;">{{ number_format($row->closing_stock) }}</td>
                        <td class="text-right" style="color: #94a3b8;">{{ number_format($row->selling_price, 0) }}</td>
                        <td class="text-right" style="color: #94a3b8;">{{ number_format($row->unit_cost, 0) }}</td>
                        <td class="text-right" style="color: #34d399;">{{ number_format($row->revenue, 0) }}</td>
                        <td class="text-right" style="color: #fb7185;">{{ number_format($row->cost, 0) }}</td>
                        <td class="text-right" style="color: {{ $isLoss ? '#fb7185' : '#34d399' }}; font-weight: 700;">
                            @if($isLoss)
                                ({{ number_format(abs($row->profit), 0) }})
                            @else
                                {{ number_format($row->profit, 0) }}
                            @endif
                        </td>
                        <td class="text-right">
                            @if($isLoss)
                                <span class="dsr-badge" style="background: rgba(239,68,68,0.15); color: #fb7185;">{{ $row->margin_pct }}%</span>
                            @elseif($isLow)
                                <span class="dsr-badge" style="background: rgba(245,158,11,0.15); color: #f59e0b;">{{ $row->margin_pct }}%</span>
                            @else
                                <span class="dsr-badge" style="background: rgba(16,185,129,0.15); color: #34d399;">{{ $row->margin_pct }}%</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">TOTALS</td>
                        <td class="text-right">{{ number_format($data['total_opening_stock']) }}</td>
                        <td class="text-right" style="color: #38bdf8;">{{ number_format($data['total_new_stock']) }}</td>
                        <td class="text-right">{{ number_format($data['total_stock']) }}</td>
                        <td class="text-right" style="color: #f59e0b;">{{ number_format($data['total_qty_sold']) }}</td>
                        <td class="text-right" style="color: #a78bfa;">{{ number_format($data['total_closing_stock']) }}</td>
                        <td colspan="2"></td>
                        <td class="text-right" style="color: #34d399;">{{ number_format($data['total_revenue'], 0) }}</td>
                        <td class="text-right" style="color: #fb7185;">{{ number_format($data['total_cost'], 0) }}</td>
                        <td class="text-right" style="color: {{ $data['total_profit'] >= 0 ? '#34d399' : '#fb7185' }};">
                            {{ number_format($data['total_profit'], 0) }}
                        </td>
                        <td class="text-right" style="color: {{ $data['overall_margin'] >= 0 ? '#34d399' : '#fb7185' }};">
                            {{ $data['overall_margin'] }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </x-filament::card>
</div>

</x-filament-panels::page>
