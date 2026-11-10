@extends('layouts.app')
@section('title', 'Inventory')

@section('content')
<div class="page-head"><h2>Inventory · {{ $currentBranch->name }}</h2></div>

<div class="stats">
    <div class="stat"><small>Stock lines</small><b>{{ number_format($summary->lines_ ?? 0) }}</b></div>
    <div class="stat"><small>Stock value (cost)</small><b>{{ $settings['currency'] }} {{ number_format($summary->cost_value ?? 0, 2) }}</b></div>
    <div class="stat"><small>Stock value (retail)</small><b>{{ $settings['currency'] }} {{ number_format($summary->retail_value ?? 0, 2) }}</b></div>
    <div class="stat"><small>Low stock</small><b style="color:var(--amber-600)">{{ number_format($summary->low_count ?? 0) }}</b></div>
    <div class="stat"><small>Out of stock</small><b style="color:var(--red-600)">{{ number_format($summary->out_count ?? 0) }}</b></div>
</div>

<div class="card card-pad">
    <form class="filters" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Name, code or barcode..." style="min-width:260px">
        <select class="input" name="category">
            <option value="">All categories</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <select class="input" name="filter">
            <option value="">All stock</option>
            <option value="low" @selected(request('filter') === 'low')>Low stock</option>
            <option value="out" @selected(request('filter') === 'out')>Out of stock</option>
        </select>
        <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Code</th><th>Product</th><th>Category</th><th class="num">Re-order</th><th class="num">In stock</th><th class="num">Value (cost)</th><th></th></tr></thead>
            <tbody>
            @forelse ($items as $p)
                @php($state = $p->stock <= 0 ? 'red' : ($p->stock <= $p->reorder_level ? 'amber' : 'green'))
                <tr>
                    <td><b>{{ $p->code }}</b></td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->category }}</td>
                    <td class="num">{{ qty($p->reorder_level, 3) }}</td>
                    <td class="num"><span class="badge badge-{{ $state }}">{{ qty($p->stock, 3) }} {{ $p->unit }}</span></td>
                    <td class="num">{{ number_format(max($p->stock, 0) * $p->cost_price, 2) }}</td>
                    <td style="text-align:right;white-space:nowrap">
                        <button class="btn btn-sm" data-edit="mAdjust" data-action="{{ route('inventory.adjust') }}"
                                data-json="{{ json_encode(['product_id' => $p->id, 'pname' => $p->name.' (current: '.qty($p->stock).')', 'qty' => '']) }}"><i class="fa-solid fa-sliders"></i> Adjust</button>
                        <a class="btn btn-sm" href="{{ route('inventory.movements', $p->id) }}"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</div>

<div class="modal-bg" id="mAdjust">
    <div class="modal">
        <form method="post" action="{{ route('inventory.adjust') }}">
            @csrf
            <input type="hidden" name="product_id" id="adjPid">
            <div class="modal-head"><h3>Stock Adjustment</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="field"><label class="lbl">Product</label><input class="input" name="pname" readonly></div>
                <div class="grid-2">
                    <div class="field">
                        <label class="lbl">Action</label>
                        <select class="input" name="mode"><option value="add">Add (+)</option><option value="remove">Remove (−)</option><option value="set">Set exact count</option></select>
                    </div>
                    <div class="field"><label class="lbl">Quantity</label><input class="input" type="number" step="0.001" min="0" name="qty" required></div>
                </div>
                <div class="field"><label class="lbl">Reason</label><input class="input" name="note" maxlength="200" placeholder="e.g. Damaged, Stock count"></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // hidden product_id is skipped by the generic edit-filler, so set it explicitly
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-edit="mAdjust"]');
        if (b) document.getElementById('adjPid').value = JSON.parse(b.dataset.json).product_id;
    });
</script>
@endpush
