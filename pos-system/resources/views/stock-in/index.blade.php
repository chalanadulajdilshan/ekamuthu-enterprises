@extends('layouts.app')
@section('title', 'Stock In')
@section('heading', 'Stock In (Scan)')

@push('head')
<style>
    .si { max-width: 680px; margin: 0 auto; display: grid; gap: 14px; }
    .si h3 { margin: 0 0 12px; font-size: 17px; display: flex; align-items: center; gap: 8px; }
    .si-scan { display: flex; gap: 8px; }
    .si-scan .input { flex: 1; font-size: 20px; height: 54px; letter-spacing: .5px; }
    .si-scan .btn { height: 54px; padding: 0 18px; font-size: 16px; }
    #reader { margin-top: 12px; border-radius: 10px; overflow: hidden; display: none; }
    #reader.on { display: block; }
    .si-hint { color: var(--muted); font-size: 13px; margin-top: 8px; }
    .si-status { padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 15px; display: flex; gap: 10px; align-items: flex-start; }
    .si-status.found { background: var(--green-50); color: var(--green-600); }
    .si-status.new { background: var(--amber-50); color: var(--amber-600); }
    .si-status b { color: var(--ink); }
    .si-list { border: 1px solid var(--line); border-radius: 10px; max-height: 260px; overflow-y: auto; margin-top: 8px; }
    .si-list button { display: flex; width: 100%; text-align: left; gap: 10px; align-items: center; padding: 11px 12px; background: none; border: 0; border-bottom: 1px solid var(--line); cursor: pointer; font: inherit; color: var(--ink); }
    .si-list button:last-child { border-bottom: 0; }
    .si-list button:hover { background: var(--blue-50); }
    .si-list small { color: var(--muted); }
    .si-list .grow { flex: 1; min-width: 0; }
    .si-list .empty-row { padding: 14px; color: var(--muted); text-align: center; }
    .si-chosen { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 2px solid var(--blue-500); border-radius: 10px; background: var(--blue-50); }
    .si-chosen .grow { flex: 1; min-width: 0; }
    .si-chosen small { color: var(--muted); display: block; }
    .si-new { border: 1px dashed var(--line); border-radius: 10px; padding: 14px 14px 0; margin-top: 10px; }
    .si-qty { display: flex; align-items: stretch; gap: 8px; }
    .si-qty .input { text-align: center; font-size: 26px; font-weight: 700; height: 60px; flex: 1; }
    .si-qty .btn { width: 60px; height: 60px; font-size: 22px; justify-content: center; }
    .si-save { width: 100%; height: 58px; font-size: 18px; justify-content: center; }
    .si-recent { list-style: none; margin: 0; padding: 0; }
    .si-recent li { display: flex; gap: 10px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--line); }
    .si-recent li:last-child { border-bottom: 0; }
    .si-recent .grow { flex: 1; min-width: 0; }
    .si-recent small { color: var(--muted); display: block; }
    .hide { display: none !important; }
</style>
@endpush

@section('content')
<div class="si">
    {{-- 1. Scan --}}
    <div class="card card-pad">
        <h3><i class="fa-solid fa-barcode"></i> 1. Scan barcode</h3>
        <form class="si-scan" id="scanForm" autocomplete="off">
            <input class="input" id="code" placeholder="Scan or type barcode..." inputmode="text" autofocus>
            <button class="btn btn-primary" title="Find"><i class="fa-solid fa-magnifying-glass"></i></button>
            <button type="button" class="btn" id="btnCam" title="Use camera"><i class="fa-solid fa-camera"></i></button>
        </form>
        <div id="reader"></div>
        <div class="si-hint">Use a barcode scanner, the phone camera <i class="fa-solid fa-camera"></i>, or
            <a href="#" id="noBarcode">add an item without a barcode</a>.</div>
    </div>

    {{-- 2. Category / product / qty --}}
    <div class="card card-pad hide" id="entry">
        <div class="si-status" id="status"></div>

        <div class="field">
            <label class="lbl">Category</label>
            <select class="input" id="category">
                <option value="">— Select category —</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                <option value="__new">+ New category...</option>
            </select>
            <input class="input hide" id="newCategory" maxlength="80" placeholder="New category name" style="margin-top:8px">
        </div>

        <div class="field">
            <label class="lbl">Product</label>
            <div id="chosen" class="si-chosen hide">
                <i class="fa-solid fa-box" style="color:var(--blue-600);font-size:20px"></i>
                <div class="grow"><b id="chosenName"></b><small id="chosenMeta"></small></div>
                <button type="button" class="btn btn-sm" id="btnChange">Change</button>
            </div>
            <div id="picker">
                <input class="input" id="pq" placeholder="Search product in this category..." autocomplete="off">
                <div class="si-list" id="plist"><div class="empty-row">Select a category to list products.</div></div>
                <button type="button" class="btn btn-sm" id="btnNewProduct" style="margin-top:8px"><i class="fa-solid fa-plus"></i> New product</button>
            </div>
            <div class="si-new hide" id="newProduct">
                <div class="field"><label class="lbl">New product name</label><input class="input" id="npName" maxlength="150"></div>
                <div class="grid-2">
                    <div class="field"><label class="lbl">Selling price</label><input class="input" id="npSell" type="number" step="0.01" min="0" inputmode="decimal"></div>
                    <div class="field"><label class="lbl">Cost price <small class="muted">(optional)</small></label><input class="input" id="npCost" type="number" step="0.01" min="0" inputmode="decimal"></div>
                </div>
                <div class="field"><label class="lbl">Unit</label><input class="input" id="npUnit" value="pcs" maxlength="15" list="units">
                    <datalist id="units"><option>pcs</option><option>kg</option><option>m</option><option>ft</option><option>l</option><option>box</option><option>roll</option><option>set</option></datalist></div>
                <button type="button" class="btn btn-sm btn-ghost" id="btnCancelNew" style="margin-bottom:12px">← Pick an existing product instead</button>
            </div>
        </div>

        <div class="field">
            <label class="lbl">Quantity to add <span id="curStock" class="muted"></span></label>
            <div class="si-qty">
                <button type="button" class="btn" data-step="-1"><i class="fa-solid fa-minus"></i></button>
                <input class="input" id="qty" type="number" step="0.001" min="0" value="1" inputmode="decimal">
                <button type="button" class="btn" data-step="1"><i class="fa-solid fa-plus"></i></button>
            </div>
        </div>

        <div class="field"><label class="lbl">Note <small class="muted">(optional)</small></label><input class="input" id="note" maxlength="150" placeholder="e.g. From main store, Supplier delivery"></div>

        <button type="button" class="btn btn-success si-save" id="btnSave"><i class="fa-solid fa-check"></i> Add to stock</button>
        <button type="button" class="btn btn-ghost" id="btnCancel" style="width:100%;justify-content:center;margin-top:8px">Cancel</button>
    </div>

    {{-- 3. Today's entries --}}
    <div class="card card-pad">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> My stock-in today · {{ $currentBranch->name }}</h3>
        <ul class="si-recent" id="recent"></ul>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const URLS = {
        lookup: @json(route('stock-in.lookup')),
        products: @json(route('stock-in.products')),
        store: @json(route('stock-in.store')),
    };
    const fmt = (n) => Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 3 });
    const st = { barcode: null, product: null, isNew: false, busy: false, camWanted: false };
    let cam = null;

    // ---------- recent list ----------
    function renderRecent(rows) {
        $('recent').innerHTML = rows.length ? rows.map((r) => `
            <li><i class="fa-solid fa-circle-plus" style="color:var(--green-600)"></i>
                <div class="grow"><b>${esc(r.name)}</b><small>${esc(r.category)} · ${esc(r.code)} · ${esc(r.time)}</small></div>
                <div style="text-align:right"><b style="color:var(--green-600)">+${fmt(r.qty)}</b><small>now ${fmt(r.balance)} ${esc(r.unit)}</small></div></li>`).join('')
            : '<li class="muted" style="justify-content:center">Nothing added yet today.</li>';
    }
    renderRecent(@json($recent));

    // ---------- scanning ----------
    async function lookup(code) {
        code = (code || '').trim();
        if (!code || st.busy) return;
        st.busy = true;
        try {
            const res = await fetch(`${URLS.lookup}?code=${encodeURIComponent(code)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (res.status === 401 || res.status === 419) return location.reload();
            const data = await res.json();
            openEntry(code, data.found ? data.product : null);
        } catch (e) {
            toast('Lookup failed: ' + e.message, 'err');
        } finally {
            st.busy = false;
        }
    }

    $('scanForm').addEventListener('submit', (e) => { e.preventDefault(); lookup($('code').value); });
    $('noBarcode').addEventListener('click', (e) => { e.preventDefault(); openEntry(null, null); });

    $('btnCam').addEventListener('click', () => (cam ? stopCam(false) : startCam()));

    async function startCam() {
        if (!window.Html5Qrcode) return toast('Camera scanner could not load.', 'err');
        $('reader').classList.add('on');
        st.camWanted = true;
        cam = new Html5Qrcode('reader', { verbose: false });
        try {
            await cam.start({ facingMode: 'environment' }, { fps: 10, qrbox: (w, h) => ({ width: Math.min(w * 0.9, 340), height: Math.min(h * 0.5, 160) }) },
                (text) => { beep(); stopCam(true); $('code').value = text; lookup(text); },
                () => {});
            $('btnCam').classList.add('btn-primary');
        } catch (e) {
            stopCam(false);
            toast(location.protocol !== 'https:' ? 'Camera needs an https:// link.' : 'Camera not available: ' + e, 'err', 4000);
        }
    }

    async function stopCam(keepWanted) {
        st.camWanted = keepWanted;
        const c = cam; cam = null;
        $('btnCam').classList.remove('btn-primary');
        try { if (c?.isScanning) await c.stop(); c?.clear(); } catch (_) {}
        $('reader').classList.remove('on');
    }

    function beep() {
        try {
            const a = new (window.AudioContext || window.webkitAudioContext)(); const o = a.createOscillator();
            o.frequency.value = 1200; o.connect(a.destination); o.start(); setTimeout(() => { o.stop(); a.close(); }, 120);
        } catch (_) {}
        navigator.vibrate?.(80);
    }

    // ---------- entry form ----------
    function openEntry(code, product) {
        st.barcode = code;
        $('entry').classList.remove('hide');
        $('qty').value = 1; $('note').value = '';
        $('newCategory').classList.add('hide'); $('newCategory').value = '';
        ['npName', 'npSell', 'npCost'].forEach((id) => ($(id).value = '')); $('npUnit').value = 'pcs';

        if (product) {
            $('status').className = 'si-status found';
            $('status').innerHTML = `<i class="fa-solid fa-circle-check"></i><div>Found: <b>${esc(product.name)}</b><br><small>${esc(code)}</small></div>`;
            $('category').value = product.category_id;
            choose(product);
            setTimeout(() => { $('qty').focus(); $('qty').select(); }, 50);
        } else {
            $('status').className = 'si-status new';
            $('status').innerHTML = code
                ? `<i class="fa-solid fa-circle-question"></i><div>New barcode <b>${esc(code)}</b><br><small>Pick the category and product it belongs to (the barcode will be saved to it), or add a new product.</small></div>`
                : `<i class="fa-solid fa-hand-pointer"></i><div>No barcode: pick the category and product.</div>`;
            choose(null);
            $('category').value = '';
            loadProducts();
            $('category').focus();
        }
        $('entry').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function choose(p) {
        st.product = p; st.isNew = false;
        $('newProduct').classList.add('hide');
        $('chosen').classList.toggle('hide', !p);
        $('picker').classList.toggle('hide', !!p);
        if (p) {
            $('chosenName').textContent = p.name;
            $('chosenMeta').textContent = `${p.code}${p.barcode ? ' · ' + p.barcode : ''}`;
            $('curStock').textContent = `(in stock now: ${fmt(p.stock)} ${p.unit})`;
        } else {
            $('curStock').textContent = '';
        }
    }

    function newProductMode(on) {
        st.isNew = on; st.product = null;
        $('newProduct').classList.toggle('hide', !on);
        $('picker').classList.toggle('hide', on);
        $('chosen').classList.add('hide');
        $('curStock').textContent = '';
        if (on) $('npName').focus();
    }

    let ctrl;
    async function loadProducts() {
        const cat = $('category').value;
        if (!cat || cat === '__new') {
            $('plist').innerHTML = `<div class="empty-row">${cat === '__new' ? 'New category: add a new product below.' : 'Select a category to list products.'}</div>`;
            return;
        }
        ctrl?.abort(); ctrl = new AbortController();
        try {
            const data = await api(`${URLS.products}?category=${cat}&q=${encodeURIComponent($('pq').value)}`, { signal: ctrl.signal });
            $('plist').innerHTML = data.products.length
                ? data.products.map((p, i) => `<button type="button" data-i="${i}"><i class="fa-solid fa-box" style="color:var(--muted)"></i>
                    <div class="grow"><b>${esc(p.name)}</b><br><small>${esc(p.code)}${p.barcode ? ' · ' + esc(p.barcode) : ''}</small></div>
                    <small>${fmt(p.stock)} ${esc(p.unit)}</small></button>`).join('')
                : '<div class="empty-row">No products found.</div>';
            $('plist').querySelectorAll('[data-i]').forEach((b) => b.addEventListener('click', () => {
                choose(data.products[b.dataset.i]); $('qty').focus(); $('qty').select();
            }));
        } catch (e) { if (e.name !== 'AbortError') toast(e.message, 'err'); }
    }

    $('category').addEventListener('change', () => {
        const isNewCat = $('category').value === '__new';
        $('newCategory').classList.toggle('hide', !isNewCat);
        if (isNewCat) { newProductMode(true); $('newCategory').focus(); return; }
        if (st.product && String(st.product.category_id) !== $('category').value) choose(null);
        if (!st.isNew) { $('pq').value = ''; loadProducts(); }
    });
    $('pq').addEventListener('input', debounce(loadProducts, 250));
    $('btnChange').addEventListener('click', () => { choose(null); $('pq').value = ''; loadProducts(); $('pq').focus(); });
    $('btnNewProduct').addEventListener('click', () => newProductMode(true));
    $('btnCancelNew').addEventListener('click', () => {
        if ($('category').value === '__new') { $('category').value = ''; $('newCategory').classList.add('hide'); }
        newProductMode(false); loadProducts();
    });
    document.querySelectorAll('[data-step]').forEach((b) => b.addEventListener('click', () => {
        $('qty').value = Math.max(0, (parseFloat($('qty').value) || 0) + Number(b.dataset.step));
    }));
    $('qty').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); save(); } });
    $('btnCancel').addEventListener('click', reset);
    $('btnSave').addEventListener('click', save);

    async function save() {
        const qty = parseFloat($('qty').value);
        if (!(qty > 0)) return toast('Enter a quantity.', 'err');
        const body = { barcode: st.barcode, qty, note: $('note').value };

        if (st.isNew) {
            const cat = $('category').value;
            if (cat === '__new') {
                if (!$('newCategory').value.trim()) return toast('Enter the new category name.', 'err');
                body.new_category = $('newCategory').value.trim();
            } else if (!cat) return toast('Select a category.', 'err');
            else body.category_id = cat;
            if (!$('npName').value.trim()) return toast('Enter the product name.', 'err');
            if ($('npSell').value === '') return toast('Enter the selling price.', 'err');
            body.new_product = { name: $('npName').value.trim(), unit: $('npUnit').value, sell_price: $('npSell').value, cost_price: $('npCost').value || 0 };
        } else if (st.product) {
            body.product_id = st.product.id;
        } else {
            return toast('Select a product.', 'err');
        }

        $('btnSave').disabled = true;
        try {
            const data = await api(URLS.store, { method: 'POST', body });
            toast(data.message, 'ok', 3500);
            renderRecent(data.recent);
            reset();
        } catch (e) {
            toast(e.message, 'err', 4000);
        } finally {
            $('btnSave').disabled = false;
        }
    }

    function reset() {
        $('entry').classList.add('hide');
        st.barcode = null; st.product = null; st.isNew = false;
        $('code').value = '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (st.camWanted) startCam(); else $('code').focus();
    }
})();
</script>
@endpush
