<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcodes · {{ $purchase->ref_no }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style id="pageStyle"></style>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, "Segoe UI", Arial, sans-serif; color: #111; background: #eef1f5; }
        .toolbar { background: #fff; border-bottom: 1px solid #d8dde4; padding: 14px 18px; }
        .toolbar h2 { margin: 0 0 10px; font-size: 18px; }
        .toolbar .row { display: flex; flex-wrap: wrap; gap: 10px 18px; align-items: center; margin-bottom: 10px; }
        .toolbar label { font-size: 14px; display: inline-flex; gap: 6px; align-items: center; }
        .toolbar select, .toolbar input[type=number] { padding: 6px 8px; font-size: 14px; border: 1px solid #c4cad3; border-radius: 6px; }
        .btn { padding: 8px 16px; font-size: 14px; border-radius: 6px; border: 1px solid #c4cad3; background: #fff; cursor: pointer; text-decoration: none; color: #111; display: inline-flex; gap: 6px; align-items: center; }
        .btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .items { width: 100%; max-width: 900px; border-collapse: collapse; font-size: 14px; background: #fff; }
        .items th, .items td { border-bottom: 1px solid #e3e7ec; padding: 6px 8px; text-align: left; }
        .items td.n, .items th.n { text-align: right; }
        .items input { width: 80px; text-align: right; }
        .muted { color: #6b7280; }
        .warn { color: #b45309; font-size: 12px; }
        .ok { background: #dcfce7; color: #166534; padding: 8px 12px; border-radius: 6px; margin-bottom: 10px; font-size: 14px; }
        #summary { font-size: 14px; }

        /* ---- labels ---- */
        #sheet { padding: 16px; display: flex; flex-wrap: wrap; gap: 6px; align-content: flex-start; }
        .label { background: #fff; overflow: hidden; display: flex; flex-direction: column; align-items: center;
                 justify-content: center; text-align: center; padding: 1mm 1.5mm; line-height: 1.1; outline: 1px dashed #c4cad3; }
        .label .store { font-size: 6.5pt; font-weight: 600; white-space: nowrap; overflow: hidden; max-width: 100%; }
        .label .name { font-size: 7.5pt; max-width: 100%; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word; }
        .label svg { width: 100%; flex: 1 1 auto; min-height: 0; display: block; }
        .label .code { font-size: 7pt; letter-spacing: .5px; font-family: "Courier New", monospace; }
        .label .price { font-size: 10pt; font-weight: 700; }
        .label .bad { flex: 1; display: flex; align-items: center; font-size: 8pt; color: #b91c1c; }

        @media screen and (max-width: 720px) {
            .toolbar { padding: 12px; }
            .toolbar .row { gap: 10px; }
            .toolbar label { flex: 1 1 100%; }
            .toolbar select { flex: 1; font-size: 16px; }
            .items { display: block; overflow-x: auto; white-space: nowrap; }
            .items input { font-size: 16px; }
            .btn { flex: 1 1 auto; justify-content: center; padding: 11px 14px; }
            #sheet { padding: 12px; justify-content: center; }
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            #sheet { padding: 0; gap: 0; }
            .label { outline: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    <h2><i class="fa-solid fa-barcode"></i> Barcode labels · {{ $purchase->ref_no }}</h2>

    <div class="row">
        <label>Label size
            <select id="size">
                <option value="roll:50x25">Label printer 50 × 25 mm</option>
                <option value="roll:38x25">Label printer 38 × 25 mm</option>
                <option value="roll:40x30">Label printer 40 × 30 mm</option>
                <option value="roll:60x40">Label printer 60 × 40 mm</option>
                <option value="a4:38x21">A4 sheet 38 × 21 mm (5 × 13 = 65)</option>
                <option value="a4:64x34">A4 sheet 64 × 34 mm (3 × 8 = 24)</option>
            </select>
        </label>
        <label><input type="checkbox" id="optStore" checked> Store name</label>
        <label><input type="checkbox" id="optName" checked> Product name</label>
        <label><input type="checkbox" id="optPrice" checked> Price</label>
    </div>

    <table class="items">
        <thead><tr><th>Product</th><th>Barcode</th><th class="n">Received</th><th class="n">Price</th><th class="n">Labels</th></tr></thead>
        <tbody>
        @foreach ($purchase->items as $i => $it)
            @php($p = $it->product)
            <tr>
                <td><b>{{ $p->code }}</b> {{ $p->name }}</td>
                <td>{{ $p->barcode ?: $p->code }} @unless ($p->barcode)<div class="warn">No barcode set; using product code</div>@endunless</td>
                <td class="n">{{ qty($it->qty) }} {{ $p->unit }}</td>
                <td class="n">{{ money($p->sell_price) }}</td>
                <td class="n"><input type="number" min="0" max="999" step="1" class="cnt" data-i="{{ $i }}" value="{{ min((int) ceil((float) $it->qty), 999) }}"></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="row" style="margin:12px 0 0">
        <a class="btn" href="{{ $back ?? route('purchases.show', $purchase) }}"><i class="fa-solid fa-arrow-left"></i> {{ $backText ?? 'Back to GRN' }}</a>
        <button class="btn" type="button" id="allOne">1 label each</button>
        <button class="btn btn-primary" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print labels</button>
        <span id="summary" class="muted"></span>
    </div>
</div>

<div id="sheet"></div>

<script src="{{ asset('vendor/jsbarcode/JsBarcode.all.min.js') }}"></script>
<script>
(function () {
    const items = @json($labels);
    const store = @json($settings['store_name'] ?? '');
    const $ = (id) => document.getElementById(id);
    const sheet = $('sheet');
    const KEY = 'pos.labels.v1';

    // Restore the last-used options (per browser).
    try {
        const saved = JSON.parse(localStorage.getItem(KEY) || '{}');
        if (saved.size && [...$('size').options].some(o => o.value === saved.size)) $('size').value = saved.size;
        ['optStore', 'optName', 'optPrice'].forEach(k => { if (k in saved) $(k).checked = !!saved[k]; });
    } catch (e) {}

    function save() {
        try {
            localStorage.setItem(KEY, JSON.stringify({
                size: $('size').value, optStore: $('optStore').checked, optName: $('optName').checked, optPrice: $('optPrice').checked,
            }));
        } catch (e) {}
    }

    function el(tag, cls, text) {
        const e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text != null) e.textContent = text;
        return e;
    }

    function render() {
        const [mode, dims] = $('size').value.split(':');
        const [w, h] = dims.split('x').map(Number);
        const roll = mode === 'roll';

        // Roll printers: each label is its own page. A4: labels flow in a grid on the sheet.
        $('pageStyle').textContent = roll
            ? `@page { size: ${w}mm ${h}mm; margin: 0; } @media print { .label { page-break-after: always; break-after: page; } }`
            : `@page { size: A4; margin: 8mm 5mm; }`;

        const showStore = $('optStore').checked && store;
        const showName = $('optName').checked;
        const showPrice = $('optPrice').checked;
        // Leave the barcode as much height as the text lines allow.
        const barH = Math.max(h - 3 - (showStore ? 3 : 0) - (showName ? 6 : 0) - (showPrice ? 4.5 : 0) - 3, 6);

        sheet.innerHTML = '';
        const frag = document.createDocumentFragment();
        let total = 0;
        document.querySelectorAll('.cnt').forEach((inp) => {
            const n = Math.min(Math.max(parseInt(inp.value, 10) || 0, 0), 999);
            const it = items[+inp.dataset.i];
            if (!n || !it) return;

            // Build one label, then clone it n times.
            const lab = el('div', 'label');
            lab.style.width = w + 'mm';
            lab.style.height = h + 'mm';
            if (showStore) lab.appendChild(el('div', 'store', store));
            if (showName) lab.appendChild(el('div', 'name', it.name));
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            try {
                JsBarcode(svg, it.value, { format: 'CODE128', margin: 0, height: 40, width: 2, displayValue: false });
                svg.setAttribute('viewBox', `0 0 ${svg.getAttribute('width')} ${svg.getAttribute('height')}`);
                svg.setAttribute('preserveAspectRatio', 'none');
                svg.removeAttribute('width');
                svg.removeAttribute('height');
                svg.style.height = barH + 'mm';
                lab.appendChild(svg);
                lab.appendChild(el('div', 'code', it.value));
            } catch (e) {
                lab.appendChild(el('div', 'bad', 'Invalid barcode: ' + it.value));
            }
            if (showPrice) lab.appendChild(el('div', 'price', 'Rs. ' + it.price));

            for (let k = 0; k < n; k++) frag.appendChild(k ? lab.cloneNode(true) : lab);
            total += n;
        });
        sheet.appendChild(frag);
        $('summary').textContent = total + ' label' + (total === 1 ? '' : 's') + (roll ? '' : ` · ~${Math.ceil(total / (w === 38 ? 65 : 24))} A4 sheet(s)`);
    }

    ['size', 'optStore', 'optName', 'optPrice'].forEach(id => $(id).addEventListener('change', () => { save(); render(); }));
    document.querySelectorAll('.cnt').forEach(inp => inp.addEventListener('input', render));
    $('allOne').addEventListener('click', () => { document.querySelectorAll('.cnt').forEach(i => { i.value = 1; }); render(); });
    render();
})();
</script>
</body>
</html>
