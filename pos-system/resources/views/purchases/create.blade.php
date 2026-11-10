@extends('layouts.app')
@section('title', 'New Purchase')

@section('content')
<div class="page-head">
    <a href="{{ route('purchases.index') }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i></a>
    <h2>New Purchase / GRN · {{ $currentBranch->name }}</h2>
</div>

<form method="post" action="{{ route('purchases.store') }}" id="poForm">
    @csrf
    <div class="card card-pad" style="margin-bottom:16px">
        <div class="grid-3">
            <div class="field">
                <label class="lbl">Supplier *</label>
                <select class="input" name="supplier_id" required>
                    <option value="">Select supplier</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div class="field"><label class="lbl">Date *</label><input class="input" type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" required></div>
            <div class="field"><label class="lbl">Supplier invoice no</label><input class="input" name="invoice_no" value="{{ old('invoice_no') }}" maxlength="60"></div>
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom:16px">
        <div class="field" style="position:relative;max-width:520px">
            <label class="lbl">Add product</label>
            <input class="input" id="pSearch" placeholder="Search name / code / scan barcode..." autocomplete="off">
            <div class="cust-pop" id="pPop" style="top:calc(100% + 4px);bottom:auto"><ul id="pList"></ul></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Product</th><th class="num" style="width:120px">Qty</th><th class="num" style="width:140px">Unit cost</th><th class="num" style="width:140px">New sell price</th><th class="num">Line total</th><th></th></tr></thead>
                <tbody id="lines"><tr id="noLines"><td colspan="7" class="empty">Search above to add products</td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="card card-pad" style="max-width:520px;margin-left:auto">
        <div class="totals">
            <div class="row"><span>Subtotal</span><b id="sub">0.00</b></div>
            <div class="row"><span>Discount</span><input class="input" style="width:160px;text-align:right" type="number" step="0.01" min="0" name="discount" id="disc" value="{{ old('discount', 0) }}"></div>
            <div class="row" style="font-size:19px"><span>Total</span><b id="tot">0.00</b></div>
            <div class="row"><span>Paid now</span><input class="input" style="width:160px;text-align:right" type="number" step="0.01" min="0" name="paid" value="{{ old('paid', 0) }}"></div>
        </div>
        <div class="field"><input class="input" name="note" placeholder="Note" maxlength="255" value="{{ old('note') }}"></div>
        <button class="btn btn-success" style="width:100%;height:46px;font-size:16px"><i class="fa-solid fa-floppy-disk"></i> Save Purchase & Update Stock</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const searchUrl = @json(route('products.search'));
    const lines = document.getElementById('lines');
    const pSearch = document.getElementById('pSearch');
    const pPop = document.getElementById('pPop');
    const pList = document.getElementById('pList');
    let results = [];
    let n = 0;

    const find = debounce(async () => {
        const q = pSearch.value.trim();
        if (!q) { pPop.classList.remove('open'); return; }
        results = await api(`${searchUrl}?q=${encodeURIComponent(q)}`);
        pList.innerHTML = results.length
            ? results.map((p, i) => `<li data-i="${i}"><span><b>${esc(p.code)}</b> ${esc(p.name)}</span><small>cost ${money(p.cost_price)}</small></li>`).join('')
            : '<li class="muted">No match</li>';
        pPop.classList.add('open');
    }, 200);

    function add(p) {
        const ex = lines.querySelector(`tr[data-pid="${p.id}"]`);
        if (ex) { const q = ex.querySelector('.q'); q.value = (+q.value || 0) + 1; calc(); q.focus(); return; }
        document.getElementById('noLines')?.remove();
        const i = n++;
        lines.insertAdjacentHTML('beforeend', `
          <tr data-pid="${p.id}">
            <td class="idx"></td>
            <td><b>${esc(p.code)}</b> ${esc(p.name)} <small class="muted">${esc(p.unit)}</small><input type="hidden" name="items[${i}][product_id]" value="${p.id}"></td>
            <td><input class="input q num" type="number" step="0.001" min="0.001" name="items[${i}][qty]" value="1" required></td>
            <td><input class="input c num" type="number" step="0.01" min="0" name="items[${i}][unit_cost]" value="${p.cost_price}" required></td>
            <td><input class="input num" type="number" step="0.01" min="0" name="items[${i}][sell_price]" placeholder="${p.sell_price}"></td>
            <td class="num lt">0.00</td>
            <td><button type="button" class="del"><i class="fa-regular fa-trash-can"></i></button></td>
          </tr>`);
        calc();
        lines.lastElementChild.querySelector('.q').select();
    }

    function calc() {
        let sub = 0;
        lines.querySelectorAll('tr[data-pid]').forEach((tr, i) => {
            tr.querySelector('.idx').textContent = i + 1;
            const lt = (+tr.querySelector('.q').value || 0) * (+tr.querySelector('.c').value || 0);
            tr.querySelector('.lt').textContent = money(lt);
            sub += lt;
        });
        document.getElementById('sub').textContent = money(sub);
        document.getElementById('tot').textContent = money(Math.max(sub - (+document.getElementById('disc').value || 0), 0));
    }

    pSearch.addEventListener('input', find);
    pSearch.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); if (results[0]) { add(results[0]); pSearch.value = ''; pPop.classList.remove('open'); } }
    });
    pList.addEventListener('click', (e) => {
        const li = e.target.closest('li[data-i]');
        if (li) { add(results[+li.dataset.i]); pSearch.value = ''; pPop.classList.remove('open'); pSearch.focus(); }
    });
    lines.addEventListener('input', calc);
    lines.addEventListener('click', (e) => { if (e.target.closest('.del')) { e.target.closest('tr').remove(); calc(); } });
    document.getElementById('disc').addEventListener('input', calc);
    document.getElementById('poForm').addEventListener('submit', (e) => {
        if (!lines.querySelector('tr[data-pid]')) { e.preventDefault(); toast('Add at least one product', 'err'); }
    });
})();
</script>
@endpush
