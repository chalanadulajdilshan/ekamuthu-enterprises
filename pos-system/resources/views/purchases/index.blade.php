@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
<div class="page-head">
    <h2>Purchases (GRN)</h2>
    <div class="spacer"></div>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Purchase</a>
</div>

<div class="card card-pad">
    <form class="filters" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="GRN / supplier invoice no">
        <select class="input" name="supplier">
            <option value="">All suppliers</option>
            @foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(request('supplier') == $s->id)>{{ $s->name }}</option>@endforeach
        </select>
        <input class="input" type="date" name="from" value="{{ request('from') }}">
        <input class="input" type="date" name="to" value="{{ request('to') }}">
        <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>GRN No</th><th>Date</th><th>Supplier</th><th>Invoice No</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Due</th><th></th></tr></thead>
            <tbody>
            @forelse ($purchases as $p)
                <tr>
                    <td><b>{{ $p->ref_no }}</b></td>
                    <td>{{ $p->purchase_date }}</td>
                    <td>{{ $p->supplier }}</td>
                    <td>{{ $p->invoice_no }}</td>
                    <td class="num">{{ money($p->total) }}</td>
                    <td class="num">{{ money($p->paid) }}</td>
                    <td class="num">@if ($p->total - $p->paid > 0)<span class="badge badge-amber">{{ money($p->total - $p->paid) }}</span>@else <span class="badge badge-green">Paid</span> @endif</td>
                    <td style="text-align:right"><a class="btn btn-sm" href="{{ route('purchases.show', $p->id) }}"><i class="fa-regular fa-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No purchases yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $purchases->links() }}
</div>
@endsection
