@extends('layouts.app')
@section('title', 'Categories')

@section('content')
<div class="page-head">
    <a href="{{ route('products.index') }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i></a>
    <h2>Categories</h2>
    <div class="spacer"></div>
    <button class="btn btn-primary" data-open="mNew"><i class="fa-solid fa-plus"></i> New Category</button>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>Icon</th><th>Name</th><th class="num">Order</th><th class="num">Products</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($categories as $c)
            <tr>
                <td><i class="fa-solid {{ $c->icon }}" style="font-size:20px;color:var(--navy-700)"></i></td>
                <td><b>{{ $c->name }}</b></td>
                <td class="num">{{ $c->sort_order }}</td>
                <td class="num">{{ number_format($c->products_count) }}</td>
                <td>{!! $c->is_active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Hidden</span>' !!}</td>
                <td style="text-align:right;white-space:nowrap">
                    <button class="btn btn-sm" data-edit="mEdit" data-action="{{ route('categories.update', $c) }}"
                            data-json="{{ json_encode($c->only(['name', 'icon', 'sort_order', 'is_active'])) }}"><i class="fa-solid fa-pen"></i></button>
                    <form method="post" action="{{ route('categories.destroy', $c) }}" style="display:inline" data-confirm="Delete {{ $c->name }}?">
                        @csrf @method('DELETE')<button class="btn btn-sm"><i class="fa-regular fa-trash-can" style="color:var(--red-600)"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@foreach (['mNew' => ['New Category', route('categories.store'), 'POST'], 'mEdit' => ['Edit Category', '', 'PUT']] as $id => [$title, $action, $verb])
<div class="modal-bg" id="{{ $id }}">
    <div class="modal">
        <form method="post" action="{{ $action }}">
            @csrf @if ($verb === 'PUT') @method('PUT') @endif
            <div class="modal-head"><h3>{{ $title }}</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="field"><label class="lbl">Name *</label><input class="input" name="name" required maxlength="80"></div>
                <div class="grid-2">
                    <div class="field">
                        <label class="lbl">Icon (Font Awesome) *</label>
                        <input class="input" name="icon" value="fa-box" required list="icons">
                    </div>
                    <div class="field"><label class="lbl">Sort order</label><input class="input" type="number" name="sort_order" value="0" min="0"></div>
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" checked> Show on POS</label>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
@endforeach
<datalist id="icons">
    @foreach (['fa-trowel-bricks', 'fa-faucet-drip', 'fa-bolt', 'fa-screwdriver-wrench', 'fa-paint-roller', 'fa-hammer', 'fa-toolbox', 'fa-plug', 'fa-lightbulb', 'fa-wrench', 'fa-ruler', 'fa-box', 'fa-ellipsis', 'fa-fire', 'fa-shower', 'fa-door-open', 'fa-key', 'fa-helmet-safety'] as $i)
        <option value="{{ $i }}">
    @endforeach
</datalist>
@endsection
