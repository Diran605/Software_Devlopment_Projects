<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 8px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .row-3 { display: grid; grid-template-columns: 1fr auto auto; gap: 4px; margin: 2px 0; }
        .right { text-align: right; }
        .total-row { font-weight: bold; font-size: 13px; }
        @media print {
            body { width: 80mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="center bold">{{ strtoupper($order->branch->name) }}</div>
    @if($order->branch->address)
    <div class="center">{{ $order->branch->address }}</div>
    @endif
    @if($order->branch->phone)
    <div class="center">Tel: {{ $order->branch->phone }}</div>
    @endif

    <div class="divider"></div>

    <div class="row">
        <span>Receipt #:</span>
        <span class="bold">{{ $order->order_number }}</span>
    </div>
    <div class="row">
        <span>Date:</span>
        <span>{{ $order->sold_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row">
        <span>Served by:</span>
        <span>{{ $order->servedBy->name }}</span>
    </div>
    @if($order->customer_name || $order->customer)
    <div class="row">
        <span>Customer:</span>
        <span>{{ $order->customer?->name ?? $order->customer_name }}</span>
    </div>
    @endif

    <div class="divider"></div>

    {{-- Column headers --}}
    <div class="row-3">
        <span class="bold">Item</span>
        <span class="bold right">Qty</span>
        <span class="bold right">Total</span>
    </div>
    <div class="divider"></div>

    {{-- Lines --}}
    @foreach($order->salesOrderLines as $line)
    <div class="row-3">
        <span>{{ $line->item->name }}</span>
        <span class="right">{{ $line->qty_sold }}</span>
        <span class="right">{{ number_format($line->line_total, 0) }}</span>
    </div>
    <div style="font-size:10px; color:#555; padding-left:4px;">
        {{ number_format($line->unit_price, 0) }} / {{ $line->item->uom->abbreviation }}
    </div>
    @endforeach

    <div class="divider"></div>

    {{-- Totals --}}
    @if($order->discount_total > 0)
    <div class="row">
        <span>Subtotal</span>
        <span>{{ number_format($order->subtotal, 0) }}</span>
    </div>
    <div class="row">
        <span>Discount</span>
        <span>- {{ number_format($order->discount_total, 0) }}</span>
    </div>
    @endif

    <div class="row total-row">
        <span>TOTAL</span>
        <span>{{ number_format($order->grand_total, 0) }} FCFA</span>
    </div>
    <div class="row">
        <span>Cash Tendered</span>
        <span>{{ number_format($order->amount_tendered, 0) }}</span>
    </div>
    @if($order->amount_tendered > $order->grand_total)
    <div class="row">
        <span>Change</span>
        <span>{{ number_format($order->amount_tendered - $order->grand_total, 0) }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <div class="center" style="margin-top: 8px;">Thank you for your purchase!</div>
    <div class="center" style="font-size: 10px; margin-top: 4px; color: #555;">
        {{ $order->sold_at->format('d/m/Y H:i:s') }}
    </div>

    {{-- Print button — hidden when printing --}}
    <div class="no-print center" style="margin-top: 16px;">
        <button onclick="window.print()" style="padding: 8px 24px; cursor: pointer;">
            Print Receipt
        </button>
    </div>

</body>
</html>
