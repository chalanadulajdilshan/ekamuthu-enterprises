@extends('layouts.app')
@section('title', $show === 'out' ? 'Out of Stock' : 'Re-order List')

@section('content')
<div class="page-head">
    <h2>{{ $show === 'out' ? 'Out of Stock' : 'Re-order List' }} · {{ $currentBranch->name }}</h2>
    <div class="spacer"></div>
    <a class="btn no-print" href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
    <button type="button" class="btn btn-primary no-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
</div>

<div class="stats no-print">
    <a class="stat" href="{{ request()->fullUrlWithQuery(['show' => 'out', 'page' => null]) }}" style="{{ $show === 'out' ? 'outline:2px solid var(--red-600)' : '' }}">
        <small>Out of stock</small><b style="color:var(--red-600)">{{ number_format($counts->out_count ?? 0) }}</b></a>
    <a class="stat" href="{{ request()->fullUrlWithQuery(['show' => 'low', 'page' => null]) }}" style="{{ $show === 'low' ? 'outline:2px solid var(--amber-600)' : '' }}">
        <small>Low stock (at / below re-order level)</small><b style="color:var(--amber-600)">{{ number_format($counts->low_count ?? 0) }}</b></a>
</div>

<div class="card card-pad">
    <form class="filters no-print" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Name, code or barcode..." style="min-width:240px">
        <select class="input" name="category">
            <option value="">All categories</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <select class="input" name="show">
            <option value="out" @selected($show === 'out')>Out of stock only</option>
            <option value="low" @selected($show === 'low')>Out of stock + low stock</option>
        </select>
        <select class="input" name="sort">
            <option value="demand" @selected($sort === 'demand')>Most sold (30 days) first</option>
            <option value="stock" @selected($sort === 'stock')>Lowest stock first</option>
            <option value="category" @selected($sort === 'category')>Category</option>
            <option value="name" @selected($sort === 'name')>Name</option>
        </select>
        <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
        @if (request()->hasAny(['q', 'category', 'show', 'sort']))<a class="btn btn-ghost" href="{{ route('reports.out-of-stock') }}">Reset</a>@endif
    </form>

    <p class="muted print-only" style="margin:0 0 10px">Printed {{ now()->format('d M Y h:i A') }} · {{ $rows->total() }} items</p>

    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th>#</th><th>Code</th><th>Product</th><th>Category</th>
                <th class="num">Stock</th><th class="num">Re-order</th><th class="num">Sold 30d</th>
                <th>Last sold</th><th>Last supplier</th><th class="num">Last cost</th><th class="num">Suggested order</th>
                <th class="no-print"></th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $i => $r)
                <tr>
                    <td>{{ $rows->firstItem() + $i }}</td>
                    <td><b>{{ $r->code }}</b></td>
                    <td>{{ $r->name }}</td>
                    <td class="muted">{{ $r->category }}</td>
                    <td class="num"><span class="badge badge-{{ $r->stock <= 0 ? 'red' : 'amber' }}">{{ qty($r->stock, 3) }}</span> <small class="muted">{{ $r->unit }}</small></td>
                    <td class="num">{{ qty($r->reorder_level, 3) }}</td>
                    <td class="num">{{ qty($r->sold_30, 3) }}</td>
                    <td>{{ $r->last_sold ? \Carbon\Carbon::parse($r->last_sold)->format('d M Y') : '—' }}</td>
                    <td>{{ $r->supplier ?? '—' }}@if ($r->supplier_phone)<br><small class="muted">{{ $r->supplier_phone }}</small>@endif</td>
                    <td class="num">{{ money($r->last_cost) }}</td>
                    <td class="num"><b>{{ $r->suggested > 0 ? qty($r->suggested, 3) : '—' }}</b></td>
                    <td class="no-print" style="white-space:nowrap;text-align:right">
                        <a class="btn btn-sm" href="{{ route('inventory.movements', $r->id) }}" title="Stock history"><i class="fa-solid fa-clock-rotate-left"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12" class="empty"><i class="fa-solid fa-circle-check" style="color:var(--green-600)"></i>
                    {{ $show === 'out' ? 'Nothing is out of stock.' : 'Nothing is at or below its re-order level.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="no-print">{{ $rows->links() }}</div>
    <p class="muted no-print" style="font-size:12px;margin:10px 0 0">Suggested order = enough for the last 30 days of sales or 2× the re-order level (whichever is more), minus current stock.</p>
</div>
@endsection
