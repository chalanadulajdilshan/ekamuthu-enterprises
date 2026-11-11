@extends('layouts.app')
@section('title', 'Cash Book')

@php
    $kinds = [
        'sales' => ['Cash sales', 'badge-green'],
        'void' => ['Voided bills', 'badge-red'],
        'customer' => ['Customer payment', 'badge-blue'],
        'purchase' => ['Purchase (GRN) paid', 'badge-amber'],
        'supplier' => ['Supplier payment', 'badge-amber'],
    ];
    $isAdmin = auth()->user()->isAdmin();
    $oneDay = $range[0] === $range[1];
@endphp

@section('content')
<div class="page-head">
    <h2>Cash Book · {{ $currentBranch->name }}</h2>
    <div class="spacer"></div>
    <button class="btn btn-success" data-open="mCashIn"><i class="fa-solid fa-circle-plus"></i> Cash In</button>
    <button class="btn btn-danger" data-open="mCashOut"><i class="fa-solid fa-circle-minus"></i> Cash Out</button>
    <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
</div>

<form class="filters" method="get">
    <div><label class="lbl">From</label><input class="input" type="date" name="from" value="{{ $range[0] }}"></div>
    <div><label class="lbl">To</label><input class="input" type="date" name="to" value="{{ $range[1] }}"></div>
    <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
    <a class="btn btn-sm btn-ghost" href="?">Today</a>
    <a class="btn btn-sm btn-ghost" href="?from={{ now()->subDay()->toDateString() }}&to={{ now()->subDay()->toDateString() }}">Yesterday</a>
    <a class="btn btn-sm btn-ghost" href="?from={{ now()->subDays(6)->toDateString() }}&to={{ now()->toDateString() }}">7 days</a>
    <a class="btn btn-sm btn-ghost" href="?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}">This month</a>
</form>

<div class="stats">
    <div class="stat"><small>Opening balance ({{ $range[0] }})</small><b>{{ money($opening) }}</b></div>
    <div class="stat"><small>Cash in</small><b style="color:var(--green-600)">{{ money($totalIn) }}</b></div>
    <div class="stat"><small>Cash out</small><b style="color:var(--red-600)">{{ money($totalOut) }}</b></div>
    <div class="stat"><small>Cash in hand ({{ $range[1] }}) · {{ $settings['currency'] }}</small><b style="color:{{ $closing < 0 ? 'var(--red-600)' : 'var(--blue-600)' }}">{{ money($closing) }}</b></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Date</th><th>Time</th><th>Type</th><th>Ref</th><th>Description</th>
                <th class="num">Cash in</th><th class="num">Cash out</th><th class="num">Balance</th>
                @if ($isAdmin)<th class="print-hide"></th>@endif
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $range[0] }}</td><td></td><td><span class="badge badge-gray">Opening</span></td><td></td>
                <td class="muted">Balance brought forward</td><td class="num"></td><td class="num"></td>
                <td class="num"><b>{{ money($opening) }}</b></td>
                @if ($isAdmin)<td class="print-hide"></td>@endif
            </tr>
            @forelse ($rows as $r)
                @php
                    $isEntry = str_starts_with($r->kind, 'entry:');
                    $type = $isEntry ? substr($r->kind, 6) : null;
                    [$label, $badge] = $isEntry
                        ? [\App\Models\CashEntry::TYPES[$type][0] ?? ucfirst($type), (\App\Models\CashEntry::TYPES[$type][1] ?? 'out') === 'in' ? 'badge-green' : 'badge-red']
                        : ($kinds[$r->kind] ?? [ucfirst($r->kind), 'badge-gray']);
                @endphp
                <tr>
                    <td>{{ $r->d }}</td>
                    <td class="muted">{{ in_array($r->kind, ['sales', 'void'], true) ? '' : \Illuminate\Support\Carbon::parse($r->t)->format('H:i') }}</td>
                    <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                    <td>
                        @if ($r->kind === 'purchase' && $isAdmin)
                            <a href="{{ route('purchases.show', $r->ref_id) }}">{{ $r->ref }}</a>
                        @else
                            {{ $r->ref }}
                        @endif
                    </td>
                    <td>
                        @if ($r->kind === 'sales' && $isAdmin)
                            <a href="{{ route('reports.bills', ['from' => $r->d, 'to' => $r->d]) }}">{{ $r->descr }}</a>
                        @elseif ($r->kind === 'customer')
                            <a href="{{ route('customers.show', $r->ref_id) }}">{{ $r->descr }}</a>
                        @elseif ($r->kind === 'supplier' && $isAdmin)
                            <a href="{{ route('suppliers.show', $r->ref_id) }}">{{ $r->descr }}</a>
                        @else
                            {{ $r->descr }}
                        @endif
                    </td>
                    <td class="num">{{ (float) $r->cin ? money($r->cin) : '' }}</td>
                    <td class="num">{{ (float) $r->cout ? money($r->cout) : '' }}</td>
                    <td class="num"><b>{{ money($r->balance) }}</b></td>
                    @if ($isAdmin)
                        <td class="print-hide">
                            @if ($isEntry)
                                <form method="post" action="{{ route('cash-book.destroy', $r->ref_id) }}" onsubmit="return confirm('Delete this cash book entry?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost" title="Delete"><i class="fa-solid fa-trash" style="color:var(--red-600)"></i></button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $isAdmin ? 9 : 8 }}" class="empty">No cash movements {{ $oneDay ? 'on this day' : 'in this period' }}.</td></tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr>
                <th colspan="5" class="num">Totals / closing balance</th>
                <th class="num">{{ money($totalIn) }}</th>
                <th class="num">{{ money($totalOut) }}</th>
                <th class="num">{{ money($closing) }}</th>
                @if ($isAdmin)<th class="print-hide"></th>@endif
            </tr>
            </tfoot>
        </table>
    </div>
</div>
<p class="muted print-hide" style="font-size:12.5px;margin-top:10px">
    Only cash is counted: card / bank bills and non-cash payments are left out. Bills are shown as one total per day;
    voided bills are taken out on the day they were voided.
</p>

@foreach (['in' => ['mCashIn', 'Cash In', $inTypes, 'btn-success'], 'out' => ['mCashOut', 'Cash Out', $outTypes, 'btn-danger']] as $dir => [$id, $title, $types, $btn])
<div class="modal-bg" id="{{ $id }}">
    <div class="modal">
        <form method="post" action="{{ route('cash-book.store') }}">
            @csrf
            <input type="hidden" name="direction" value="{{ $dir }}">
            <div class="modal-head"><h3>{{ $title }}</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="grid-2">
                    <div class="field"><label class="lbl">Type *</label>
                        <select class="input" name="type" required>
                            @foreach ($types as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label class="lbl">Amount *</label><input class="input" type="number" step="0.01" min="0.01" name="amount" required></div>
                    <div class="field"><label class="lbl">Date *</label><input class="input" type="date" name="entry_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
                    <div class="field"><label class="lbl">Ref no</label><input class="input" name="ref_no" maxlength="60" placeholder="Slip / voucher no"></div>
                </div>
                <div class="field"><label class="lbl">Note</label><input class="input" name="note" maxlength="200" placeholder="{{ $dir === 'out' ? 'e.g. Tea, transport, electricity bill' : 'e.g. Float for the day' }}"></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn {{ $btn }}">Save</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection

@push('head')
<style>
    @media print {
        .sidebar, .topbar, .page-head .btn, .filters, .print-hide, .alert { display: none !important; }
        .shell { display: block !important; }
        .main { padding: 0 !important; }
    }
</style>
@endpush
