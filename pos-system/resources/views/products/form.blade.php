@extends('layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'New Product')

@section('content')
<div class="page-head">
    <a href="{{ route('products.index') }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i></a>
    <h2>{{ $product->exists ? 'Edit: '.$product->name : 'New Product' }}</h2>
</div>

<form class="card card-pad" style="max-width:860px" method="post" enctype="multipart/form-data"
      action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="grid-3">
        <div class="field"><label class="lbl">Product code *</label><input class="input" name="code" value="{{ old('code', $product->code) }}" required maxlength="40"></div>
        <div class="field"><label class="lbl">Barcode</label><input class="input" name="barcode" value="{{ old('barcode', $product->barcode) }}" maxlength="64" placeholder="Scan or type"></div>
        <div class="field"><label class="lbl">Unit *</label><input class="input" name="unit" value="{{ old('unit', $product->unit) }}" required maxlength="15" list="units"></div>
    </div>
    <datalist id="units"><option>pcs</option><option>bag</option><option>box</option><option>kg</option><option>m</option><option>ft</option><option>l</option><option>roll</option><option>set</option><option>can</option><option>cube</option></datalist>

    <div class="grid-2">
        <div class="field"><label class="lbl">Name *</label><input class="input" name="name" value="{{ old('name', $product->name) }}" required maxlength="150"></div>
        <div class="field">
            <label class="lbl">Category *</label>
            <select class="input" name="category_id" required>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid-3">
        <div class="field"><label class="lbl">Cost price *</label><input class="input" type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required></div>
        <div class="field"><label class="lbl">Selling price *</label><input class="input" type="number" step="0.01" min="0" name="sell_price" value="{{ old('sell_price', $product->sell_price ?? 0) }}" required></div>
        <div class="field"><label class="lbl">Re-order level</label><input class="input" type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}"></div>
    </div>

    <div class="grid-2">
        @unless ($product->exists)
            <div class="field"><label class="lbl">Opening stock ({{ $currentBranch->name }})</label><input class="input" type="number" step="0.001" name="opening_stock" value="{{ old('opening_stock', 0) }}"></div>
        @endunless
        <div class="field">
            <label class="lbl">Image (max 2 MB)</label>
            <input class="input" type="file" name="image" accept="image/*">
            @if ($product->image)<img src="{{ $product->imageUrl() }}" alt="" style="height:60px;margin-top:8px;border-radius:6px">@endif
        </div>
    </div>

    <div class="field">
        <input type="hidden" name="is_active" value="0">
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> Active (available for sale)</label>
    </div>

    <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
</form>
@endsection
