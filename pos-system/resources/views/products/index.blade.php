@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div class="page-head">
    <h2>Products</h2>
    <div class="spacer"></div>
    <a href="{{ route('categories.index') }}" class="btn"><i class="fa-solid fa-tags"></i> Categories</a>
    <a href="{{ route('products.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Product</a>
</div>

<div class="card card-pad">
    <form class="filters" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Name, code or barcode..." style="min-width:260px">
        <select class="input" name="category">
            <option value="">All categories</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select class="input" name="status">
            <option value="">Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            <option value="all" @selected(request('status') === 'all')>All</option>
        </select>
        <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
        @if (request()->hasAny(['q', 'category', 'status']))<a class="btn btn-ghost" href="{{ route('products.index') }}">Reset</a>@endif
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Barcode</th><th class="num">Cost</th><th class="num">Price</th><th class="num">Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                <tr>
                    <td><b>{{ $p->code }}</b></td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->category }}</td>
                    <td class="muted">{{ $p->barcode }}</td>
                    <td class="num">{{ number_format($p->cost_price, 2) }}</td>
                    <td class="num"><b>{{ number_format($p->sell_price, 2) }}</b></td>
                    <td class="num">{{ qty($p->stock, 3) }} <small class="muted">{{ $p->unit }}</small></td>
                    <td>{!! $p->is_active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' !!}</td>
                    <td style="white-space:nowrap;text-align:right">
                        <a class="btn btn-sm" href="{{ route('inventory.movements', $p->id) }}" title="Stock history"><i class="fa-solid fa-clock-rotate-left"></i></a>
                        <a class="btn btn-sm" href="{{ route('products.edit', $p->id) }}"><i class="fa-solid fa-pen"></i></a>
                        <form method="post" action="{{ route('products.destroy', $p->id) }}" style="display:inline" data-confirm="Delete {{ $p->name }}?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm"><i class="fa-regular fa-trash-can" style="color:var(--red-600)"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</div>
@endsection
