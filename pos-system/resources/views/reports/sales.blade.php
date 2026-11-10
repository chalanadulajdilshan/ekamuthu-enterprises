@extends('layouts.app')
@section('title', 'Sales Summary')

@section('content')
<div class="page-head"><h2>Sales Summary · {{ $currentBranch->name }}</h2></div>
@include('reports._range')

@php($cur = $settings['currency'])
<div class="stats">
    <div class="stat"><small>Bills</small><b>{{ number_format($summary->bills) }}</b></div>
    <div class="stat"><small>Gross sales</small><b>{{ money($summary->gross) }}</b></div>
    <div class="stat"><small>Discounts</small><b>{{ money($summary->discount) }}</b></div>
    <div class="stat"><small>{{ $settings['tax_label'] }} collected</small><b>{{ money($summary->tax) }}</b></div>
    <div class="stat"><small>Net sales ({{ $cur }})</small><b style="color:var(--blue-600)">{{ money($summary->net) }}</b></div>
    <div class="stat"><small>Gross profit</small><b style="color:var(--green-600)">{{ money($summary->net - $summary->tax - $summary->cost) }}</b></div>
</div>

<div class="grid-2" style="gap:16px;align-items:start;grid-template-columns:2fr 1fr">
    <div class="card">
        <div class="card-pad" style="padding-bottom:0"><h3 style="margin:0 0 10px">Daily</h3></div>
        @php($max = max(1, (float) $daily->max('total')))
        <table class="table">
            <thead><tr><th>Date</th><th class="num">Bills</th><th style="width:40%"></th><th class="num">Sales</th><th class="num">Profit</th></tr></thead>
            <tbody>
            @forelse ($daily as $d)
                <tr>
                    <td><a href="{{ route('reports.bills', ['from' => $d->sale_date, 'to' => $d->sale_date]) }}">{{ $d->sale_date }}</a></td>
                    <td class="num">{{ number_format($d->bills) }}</td>
                    <td><div style="height:10px;border-radius:5px;background:var(--blue-500);width:{{ round($d->total / $max * 100, 1) }}%"></div></td>
                    <td class="num">{{ money($d->total) }}</td>
                    <td class="num">{{ money($d->profit) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No sales in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <div class="card-pad" style="padding-bottom:0"><h3 style="margin:0 0 10px">By payment method</h3></div>
        <table class="table">
            <thead><tr><th>Method</th><th class="num">Bills</th><th class="num">Total</th></tr></thead>
            <tbody>
            @forelse ($byMethod as $m)
                <tr><td>{{ ucfirst($m->payment_method) }}</td><td class="num">{{ number_format($m->bills) }}</td><td class="num">{{ money($m->total) }}</td></tr>
            @empty
                <tr><td colspan="3" class="empty">—</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
