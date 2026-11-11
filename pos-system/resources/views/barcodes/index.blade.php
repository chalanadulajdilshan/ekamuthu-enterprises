@extends('layouts.app')
@section('title', 'Print Barcodes')

@section('content')
<div class="page-head">
    <h2><i class="fa-solid fa-print"></i> Print Barcodes · select a GRN</h2>
</div>

<div class="card card-pad">
    <form class="filters" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="GRN no, invoice no or supplier" style="min-width:240px">
        <input class="input" type="date" name="from" value="{{ request('from') }}" aria-label="From">
        <input class="input" type="date" name="to" value="{{ request('to') }}" aria-label="To">
        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>GRN No</th><th>Date</th><th>Supplier</th><th>Invoice No</th><th class="num">Items</th><th class="num">Units</th><th></th></tr></thead>
            <tbody>
            @forelse ($purchases as $p)
                <tr>
                    <td><b>{{ $p->ref_no }}</b></td>
                    <td>{{ $p->purchase_date }}</td>
                    <td>{{ $p->supplier }}</td>
                    <td>{{ $p->invoice_no }}</td>
                    <td class="num">{{ $p->lines_ }}</td>
                    <td class="num">{{ qty($p->units) }}</td>
                    <td style="text-align:right">
                        <a class="btn btn-sm btn-primary" href="{{ route('barcodes.grn', $p->id) }}"><i class="fa-solid fa-barcode"></i> Print barcodes</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty"><i class="fa-solid fa-truck-ramp-box"></i>No GRNs found for {{ $currentBranch->name }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $purchases->links() }}
</div>
@endsection
