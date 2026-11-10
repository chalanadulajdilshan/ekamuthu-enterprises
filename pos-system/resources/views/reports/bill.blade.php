@extends('layouts.app')
@section('title', $sale->bill_no)

@section('content')
<div class="page-head">
    <a href="{{ route('reports.bills') }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i></a>
    <h2>{{ $sale->bill_no }}
        {!! $sale->status === 'void' ? '<span class="badge badge-red">Void</span>' : '<span class="badge badge-green">Completed</span>' !!}</h2>
    <div class="spacer"></div>
    <a class="btn" target="_blank" href="{{ route('pos.receipt', $sale) }}"><i class="fa-solid fa-print"></i> Reprint</a>
    @if ($sale->status !== 'void')
        <form method="post" action="{{ route('reports.void', $sale) }}" data-confirm="Void {{ $sale->bill_no }}? Stock will be returned and any credit reversed.">
            @csrf<button class="btn btn-danger"><i class="fa-solid fa-ban"></i> Void Bill</button>
        </form>
    @endif
</div>

<div class="stats">
    <div class="stat"><small>Date</small><b style="font-size:17px">{{ $sale->created_at->format('Y-m-d h:i A') }}</b></div>
    <div class="stat"><small>Customer</small><b style="font-size:17px">{{ $sale->customer->name ?? 'Walk-in Customer' }}</b></div>
    <div class="stat"><small>Cashier</small><b style="font-size:17px">{{ $sale->user->name }}</b></div>
    <div class="stat"><small>Payment</small><b style="font-size:17px">{{ ucfirst($sale->payment_method) }}</b></div>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>#</th><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Cost</th><th class="num">Total</th></tr></thead>
        <tbody>
        @foreach ($sale->items as $i => $it)
            <tr><td>{{ $i + 1 }}</td><td>{{ $it->name }}</td><td class="num">{{ qty($it->qty) }}</td><td class="num">{{ money($it->unit_price) }}</td><td class="num muted">{{ money($it->cost_price) }}</td><td class="num">{{ money($it->line_total) }}</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="num">Subtotal</td><td class="num">{{ money($sale->subtotal) }}</td></tr>
            <tr><td colspan="5" class="num">Discount</td><td class="num">-{{ money($sale->discount_amount) }}</td></tr>
            <tr><td colspan="5" class="num">{{ $settings['tax_label'] }} ({{ qty($sale->tax_rate, 2) }}%)</td><td class="num">{{ money($sale->tax_amount) }}</td></tr>
            <tr><td colspan="5" class="num"><b>Total</b></td><td class="num"><b>{{ money($sale->total) }}</b></td></tr>
            <tr><td colspan="5" class="num">Gross profit</td><td class="num">{{ money($sale->total - $sale->tax_amount - $sale->cost_total) }}</td></tr>
        </tfoot>
    </table>
</div>
@if ($sale->note)<p class="muted">Note: {{ $sale->note }}</p>@endif
@endsection
