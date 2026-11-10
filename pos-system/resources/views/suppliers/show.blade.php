@extends('layouts.app')
@section('title', $supplier->name)

@section('content')
<div class="page-head">
    <a href="{{ route('suppliers.index') }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i></a>
    <h2>{{ $supplier->name }}</h2>
    <div class="spacer"></div>
    <button class="btn" data-open="mEditS"><i class="fa-solid fa-pen"></i> Edit</button>
    @if ($supplier->balance > 0)
        <button class="btn btn-success" data-open="mPayS"><i class="fa-solid fa-money-bill-transfer"></i> Pay Supplier</button>
    @endif
</div>

<div class="stats">
    <div class="stat"><small>Contact</small><b style="font-size:17px">{{ $supplier->contact_person ?: '—' }} {{ $supplier->phone }}</b></div>
    <div class="stat"><small>We owe</small><b style="color:{{ $supplier->balance > 0 ? 'var(--amber-600)' : 'inherit' }}">{{ $settings['currency'] }} {{ money($supplier->balance) }}</b></div>
</div>

<div class="grid-2" style="gap:16px;align-items:start">
    <div class="card">
        <div class="card-pad" style="padding-bottom:0"><h3 style="margin:0 0 10px">Purchases</h3></div>
        <table class="table">
            <thead><tr><th>GRN</th><th>Date</th><th>Invoice</th><th class="num">Total</th><th class="num">Paid</th></tr></thead>
            <tbody>
            @forelse ($purchases as $p)
                <tr><td><a href="{{ route('purchases.show', $p->id) }}">{{ $p->ref_no }}</a></td><td>{{ $p->purchase_date }}</td><td>{{ $p->invoice_no }}</td><td class="num">{{ money($p->total) }}</td><td class="num">{{ money($p->paid) }}</td></tr>
            @empty
                <tr><td colspan="5" class="empty">No purchases.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $purchases->links() }}
    </div>
    <div class="card">
        <div class="card-pad" style="padding-bottom:0"><h3 style="margin:0 0 10px">Payments made</h3></div>
        <table class="table">
            <thead><tr><th>Date</th><th>Method</th><th>Note</th><th class="num">Amount</th></tr></thead>
            <tbody>
            @forelse ($payments as $p)
                <tr><td>{{ $p->created_at->format('Y-m-d') }}</td><td>{{ ucfirst($p->method) }}</td><td>{{ $p->note }}</td><td class="num">{{ money($p->amount) }}</td></tr>
            @empty
                <tr><td colspan="4" class="empty">No payments.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('suppliers._form', ['id' => 'mEditS', 'title' => 'Edit Supplier', 'action' => route('suppliers.update', $supplier), 'verb' => 'PUT', 's' => $supplier])

<div class="modal-bg" id="mPayS">
    <div class="modal">
        <form method="post" action="{{ route('suppliers.payment', $supplier) }}">
            @csrf
            <div class="modal-head"><h3>Pay Supplier</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="grid-2">
                    <div class="field"><label class="lbl">Amount *</label><input class="input" type="number" step="0.01" min="0.01" name="amount" value="{{ $supplier->balance }}" required></div>
                    <div class="field"><label class="lbl">Method</label><select class="input" name="method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="cheque">Cheque</option><option value="card">Card</option></select></div>
                </div>
                <div class="field"><label class="lbl">Note</label><input class="input" name="note" maxlength="200"></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-success">Save Payment</button></div>
        </form>
    </div>
</div>
@endsection
