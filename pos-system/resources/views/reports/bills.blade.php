@extends('layouts.app')
@section('title', 'Bills')

@section('content')
<div class="page-head"><h2>Bills · {{ $currentBranch->name }}</h2></div>

<form class="filters" method="get">
    <div><label class="lbl">From</label><input class="input" type="date" name="from" value="{{ $range[0] }}"></div>
    <div><label class="lbl">To</label><input class="input" type="date" name="to" value="{{ $range[1] }}"></div>
    <div><label class="lbl">Bill no</label><input class="input" name="q" value="{{ request('q') }}" placeholder="HS-000123"></div>
    <div><label class="lbl">Payment</label>
        <select class="input" name="method"><option value="">All</option>@foreach (['cash', 'card', 'bank', 'credit'] as $m)<option value="{{ $m }}" @selected(request('method') === $m)>{{ ucfirst($m) }}</option>@endforeach</select></div>
    <div><label class="lbl">Status</label>
        <select class="input" name="status"><option value="">All</option><option value="completed" @selected(request('status') === 'completed')>Completed</option><option value="void" @selected(request('status') === 'void')>Void</option></select></div>
    <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Bill No</th><th>Date / time</th><th>Customer</th><th>Cashier</th><th class="num">Items</th><th>Payment</th><th class="num">Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($bills as $b)
                <tr>
                    <td><a href="{{ route('reports.bill', $b->id) }}"><b>{{ $b->bill_no }}</b></a></td>
                    <td>{{ \Carbon\Carbon::parse($b->created_at)->format('Y-m-d h:i A') }}</td>
                    <td>{{ $b->customer ?? 'Walk-in' }}</td>
                    <td>{{ $b->cashier }}</td>
                    <td class="num">{{ $b->item_count }}</td>
                    <td>{{ ucfirst($b->payment_method) }}</td>
                    <td class="num"><b>{{ money($b->total) }}</b></td>
                    <td>{!! $b->status === 'void' ? '<span class="badge badge-red">Void</span>' : '<span class="badge badge-green">Completed</span>' !!}</td>
                    <td style="text-align:right;white-space:nowrap">
                        <a class="btn btn-sm" href="{{ route('reports.bill', $b->id) }}"><i class="fa-regular fa-eye"></i></a>
                        <a class="btn btn-sm" target="_blank" href="{{ route('pos.receipt', $b->id) }}"><i class="fa-solid fa-print"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No bills found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $bills->links() }}
</div>
@endsection
