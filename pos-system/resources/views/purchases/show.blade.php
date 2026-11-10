@extends('layouts.app')
@section('title', $purchase->ref_no)

@section('content')
<div class="page-head">
    <a href="{{ route('purchases.index') }}" class="btn btn-ghost no-print"><i class="fa-solid fa-arrow-left"></i></a>
    <h2>{{ $purchase->ref_no }}</h2>
    <div class="spacer"></div>
    <button class="btn no-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
</div>

<div class="card card-pad" style="margin-bottom:16px">
    <div class="grid-3">
        <div><small class="muted">Supplier</small><div><b>{{ $purchase->supplier->name }}</b></div></div>
        <div><small class="muted">Date</small><div>{{ $purchase->purchase_date->format('Y-m-d') }}</div></div>
        <div><small class="muted">Supplier invoice</small><div>{{ $purchase->invoice_no ?: '—' }}</div></div>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>#</th><th>Code</th><th>Product</th><th class="num">Qty</th><th class="num">Unit cost</th><th class="num">Total</th></tr></thead>
        <tbody>
        @foreach ($purchase->items as $i => $it)
            <tr>
                <td>{{ $i + 1 }}</td><td>{{ $it->product->code }}</td><td>{{ $it->product->name }}</td>
                <td class="num">{{ qty($it->qty) }} {{ $it->product->unit }}</td>
                <td class="num">{{ money($it->unit_cost) }}</td>
                <td class="num">{{ money($it->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="num">Subtotal</td><td class="num">{{ money($purchase->subtotal) }}</td></tr>
            <tr><td colspan="5" class="num">Discount</td><td class="num">-{{ money($purchase->discount) }}</td></tr>
            <tr><td colspan="5" class="num"><b>Total</b></td><td class="num"><b>{{ money($purchase->total) }}</b></td></tr>
            <tr><td colspan="5" class="num">Paid</td><td class="num">{{ money($purchase->paid) }}</td></tr>
            <tr><td colspan="5" class="num">Due</td><td class="num">{{ money($purchase->total - $purchase->paid) }}</td></tr>
        </tfoot>
    </table>
</div>
<p class="muted">Entered by {{ $purchase->user->name }} · {{ $purchase->created_at->format('Y-m-d h:i A') }}@if ($purchase->note) · {{ $purchase->note }}@endif</p>
@endsection
