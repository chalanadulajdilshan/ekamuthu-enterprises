@extends('layouts.app')
@section('title', 'Stock History')

@section('content')
<div class="page-head">
    <a href="{{ url()->previous() }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i></a>
    <h2>{{ $product->name }} <small class="muted">({{ $product->code }})</small></h2>
    <div class="spacer"></div>
    <div class="stat" style="padding:10px 16px"><small>Current stock · {{ $currentBranch->name }}</small><b>{{ qty($stock, 3) }} {{ $product->unit }}</b></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Reference</th><th class="num">Qty</th><th class="num">Balance</th><th>User</th></tr></thead>
            <tbody>
            @php($colors = ['sale' => 'blue', 'purchase' => 'green', 'adjust' => 'amber', 'void' => 'red', 'opening' => 'gray'])
            @forelse ($movements as $m)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m->created_at)->format('Y-m-d h:i A') }}</td>
                    <td><span class="badge badge-{{ $colors[$m->type] ?? 'gray' }}">{{ ucfirst($m->type) }}</span></td>
                    <td>
                        @if ($m->ref_type === 'sale')<a href="{{ route('reports.bill', $m->ref_id) }}">{{ $m->note }}</a>
                        @elseif ($m->ref_type === 'purchase')<a href="{{ route('purchases.show', $m->ref_id) }}">{{ $m->note }}</a>
                        @else {{ $m->note }} @endif
                    </td>
                    <td class="num" style="color:{{ $m->qty < 0 ? 'var(--red-600)' : 'var(--green-600)' }}"><b>{{ $m->qty > 0 ? '+' : '' }}{{ qty($m->qty, 3) }}</b></td>
                    <td class="num">{{ qty($m->balance_after, 3) }}</td>
                    <td>{{ $m->user_name }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No stock movements yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $movements->links() }}
</div>
@endsection
