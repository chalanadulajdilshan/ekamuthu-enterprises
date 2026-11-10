<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->bill_no }}</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        body { font-family: "Courier New", ui-monospace, monospace; font-size: 12px; color: #000; width: 74mm; margin: 0 auto; padding: 6px 0; }
        h1 { font-size: 17px; margin: 0; text-align: center; }
        .c { text-align: center; }
        .r { text-align: right; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .tot td { font-size: 13px; }
        .grand td { font-size: 16px; font-weight: bold; padding-top: 4px; }
        .void { border: 2px solid #000; text-align: center; font-weight: bold; font-size: 18px; margin: 6px 0; }
        .toolbar { text-align: center; margin: 14px 0; font-family: sans-serif; }
        .toolbar button { padding: 8px 18px; font-size: 14px; cursor: pointer; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <h1>{{ $settings['store_name'] }}</h1>
    <div class="c">{{ $sale->branch->name }}</div>
    @if ($settings['store_address'])<div class="c">{{ $settings['store_address'] }}</div>@endif
    @if ($settings['store_phone'])<div class="c">Tel: {{ $settings['store_phone'] }}</div>@endif
    <hr>
    <table>
        <tr><td>Bill No</td><td class="r"><b>{{ $sale->bill_no }}</b></td></tr>
        <tr><td>Date</td><td class="r">{{ $sale->created_at->format('Y-m-d h:i A') }}</td></tr>
        <tr><td>Cashier</td><td class="r">{{ $sale->user->name }}</td></tr>
        <tr><td>Customer</td><td class="r">{{ $sale->customer->name ?? 'Walk-in Customer' }}</td></tr>
    </table>
    @if ($sale->status === 'void')<div class="void">*** VOID ***</div>@endif
    <hr>
    <table>
        @foreach ($sale->items as $i => $it)
            <tr><td colspan="3">{{ $i + 1 }}. {{ $it->name }}</td></tr>
            <tr>
                <td>&nbsp;&nbsp;{{ qty($it->qty, 3) }} x {{ number_format($it->unit_price, 2) }}</td>
                <td></td>
                <td class="r">{{ number_format($it->line_total, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <table class="tot">
        <tr><td>Subtotal</td><td class="r">{{ number_format($sale->subtotal, 2) }}</td></tr>
        @if ($sale->discount_amount > 0)
            <tr><td>Discount{{ $sale->discount_type === 'percent' ? ' ('.qty($sale->discount_value, 2).'%)' : '' }}</td><td class="r">-{{ number_format($sale->discount_amount, 2) }}</td></tr>
        @endif
        <tr><td>{{ $settings['tax_label'] }} ({{ qty($sale->tax_rate, 2) }}%)</td><td class="r">{{ number_format($sale->tax_amount, 2) }}</td></tr>
        <tr class="grand"><td>TOTAL {{ $settings['currency'] }}</td><td class="r">{{ number_format($sale->total, 2) }}</td></tr>
        <tr><td>Paid ({{ ucfirst($sale->payment_method) }})</td><td class="r">{{ number_format($sale->payment_method === 'cash' ? $sale->total + $sale->change_amount : $sale->paid_amount, 2) }}</td></tr>
        @if ($sale->change_amount > 0)<tr><td>Change</td><td class="r">{{ number_format($sale->change_amount, 2) }}</td></tr>@endif
        @if ($sale->payment_method === 'credit' && $sale->total > $sale->paid_amount)
            <tr><td>On account</td><td class="r">{{ number_format($sale->total - $sale->paid_amount, 2) }}</td></tr>
        @endif
    </table>
    <hr>
    <div class="c">Items: {{ $sale->item_count }}</div>
    <div class="c" style="margin-top:6px">{{ $settings['receipt_footer'] }}</div>
    <div class="c" style="font-size:10px;margin-top:8px">Powered by Source Code</div>

    <div class="toolbar"><button onclick="window.print()">Print</button></div>
    @if ($autoPrint)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 50));</script>
    @endif
</body>
</html>
