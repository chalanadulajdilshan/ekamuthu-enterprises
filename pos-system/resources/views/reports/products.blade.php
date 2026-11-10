@extends('layouts.app')
@section('title', 'Product Sales')

@section('content')
<div class="page-head"><h2>Product Sales · {{ $currentBranch->name }}</h2></div>

<form class="filters" method="get">
    <div><label class="lbl">From</label><input class="input" type="date" name="from" value="{{ $range[0] }}"></div>
    <div><label class="lbl">To</label><input class="input" type="date" name="to" value="{{ $range[1] }}"></div>
    <div><label class="lbl">Sort by</label>
        <select class="input" name="sort">
            <option value="revenue" @selected($sort === 'revenue')>Revenue</option>
            <option value="qty" @selected($sort === 'qty')>Quantity</option>
            <option value="profit" @selected($sort === 'profit')>Profit</option>
        </select></div>
    <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
</form>

<div class="card">
    <table class="table">
        <thead><tr><th>#</th><th>Product</th><th class="num">Qty sold</th><th class="num">Revenue</th><th class="num">Profit</th></tr></thead>
        <tbody>
        @forelse ($rows as $i => $r)
            <tr>
                <td>{{ $rows->firstItem() + $i }}</td>
                <td><a href="{{ route('inventory.movements', $r->product_id) }}">{{ $r->name }}</a></td>
                <td class="num">{{ qty($r->qty) }}</td>
                <td class="num">{{ money($r->revenue) }}</td>
                <td class="num">{{ money($r->profit) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">No sales in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $rows->links() }}
</div>
@endsection
