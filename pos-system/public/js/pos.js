/* ==========================================================================
   POS screen controller
   - Product grid is fetched page-by-page from the server (never the whole catalogue),
     debounced, with in-flight requests aborted and recent pages cached in memory.
   - Cart lives in localStorage so a refresh / power cut does not lose the sale.
   - The server recomputes every price & total at checkout; the client is display-only.
   ========================================================================== */
(function () {
  const root = document.getElementById('pos');
  if (!root) return;

  const cfg = {
    productsUrl: root.dataset.productsUrl,
    lookupUrl: root.dataset.lookupUrl,
    stockUrl: root.dataset.stockUrl,
    checkoutUrl: root.dataset.checkoutUrl,
    heldUrl: root.dataset.heldUrl,
    lastReceiptUrl: root.dataset.lastReceiptUrl,
    customersUrl: root.dataset.customersUrl,
    customerStoreUrl: root.dataset.customerStoreUrl,
    taxRate: parseFloat(root.dataset.taxRate) || 0,
    allowNegative: root.dataset.allowNegative === '1',
    cur: root.dataset.currency,
    storeKey: 'pos.cart.' + root.dataset.branch,
  };

  const $ = (id) => document.getElementById(id);
  const r2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
  const r3 = (n) => Math.round((Number(n) + Number.EPSILON) * 1000) / 1000;
  const fmtQty = (n) => (Number.isInteger(+n) ? String(+n) : String(r3(n)));

  // ------------------------------------------------------------------ state
  let state = loadState();
  let lastSaleReceipt = null;

  function blank() {
    return { items: [], customer: null, discType: 'percent', discVal: 0, heldId: null };
  }
  function loadState() {
    try { return Object.assign(blank(), JSON.parse(localStorage.getItem(cfg.storeKey)) || {}); } catch (_) { return blank(); }
  }
  function save() {
    try { localStorage.setItem(cfg.storeKey, JSON.stringify(state)); } catch (_) { /* storage full / private mode */ }
  }

  // ------------------------------------------------------------------ product grid
  const grid = $('grid');
  const pager = $('pager');
  const search = $('search');
  // On phones/tablets, focusing the box after every tap would keep popping up the on-screen keyboard.
  const touch = window.matchMedia('(pointer: coarse)').matches;
  const focusSearch = () => { if (!touch) search.focus(); };
  if (touch) search.removeAttribute('autofocus');
  const view = { q: '', cat: '', page: 1 };
  const cache = new Map(); // key -> {t, data}
  let inflight = null;
  let lastData = null;

  function skeleton() {
    grid.innerHTML = Array.from({ length: 10 }, () =>
      '<div class="pcard skel"><div class="pimg"></div><div class="pname">&nbsp;</div><div class="pprice">&nbsp;</div></div>').join('');
  }

  async function loadProducts({ force = false } = {}) {
    const key = `${view.q}|${view.cat}|${view.page}`;
    const hit = cache.get(key);
    if (!force && hit && Date.now() - hit.t < 30000) { renderGrid(hit.data); return hit.data; }

    if (inflight) inflight.abort();
    inflight = new AbortController();
    const timer = setTimeout(skeleton, 150); // only flash skeleton on slow responses
    const params = new URLSearchParams({ q: view.q, category: view.cat, page: view.page });
    try {
      const data = await api(`${cfg.productsUrl}?${params}`, { signal: inflight.signal });
      cache.set(key, { t: Date.now(), data });
      if (cache.size > 60) cache.delete(cache.keys().next().value);
      renderGrid(data);
      setOnline(true);
      return data;
    } catch (e) {
      if (e.name !== 'AbortError') { setOnline(false); toast(e.message, 'err'); }
    } finally {
      clearTimeout(timer);
    }
  }

  // Live stock = branch stock from the server minus what is already in this cart.
  const inCart = (id) => state.items.find((i) => i.id === id)?.qty || 0;
  const live = (p) => r3(p.stock - inCart(p.id));
  const isLow = (left, reorder) => left <= (Number(reorder) || 0);

  function stockBadge(p) {
    const left = live(p);
    const ro = Number(p.reorder) || 0;
    const low = left > 0 && isLow(left, ro);
    const pill = left <= 0
      ? `<span class="pstock none">Out of stock${p.stock > 0 ? ' (in cart)' : ''}</span>`
      : `<span class="pstock ${low ? 'low' : ''}">Stock: ${fmtQty(left)} ${esc(p.unit || '')}</span>`;
    return `${pill}<span class="prol ${low ? 'low' : ''}">${low ? '<i class="fa-solid fa-triangle-exclamation"></i> ' : ''}Re-order level: ${fmtQty(ro)}</span>`;
  }

  function renderGrid(data) {
    lastData = data;
    if (!data.data.length) {
      grid.innerHTML = `<div class="empty" style="grid-column:1/-1"><i class="fa-solid fa-box-open"></i>No products found${view.q ? ` for “${esc(view.q)}”` : ''}</div>`;
    } else {
      grid.innerHTML = data.data.map((p, i) => `
        <div class="pcard ${live(p) <= 0 ? 'out' : ''}" data-i="${i}" title="${esc(p.code)}">
          <div class="pimg">${p.image ? `<img src="${esc(p.image)}" alt="" loading="lazy">` : `<i class="fa-solid ${esc(p.icon || 'fa-box')}"></i>`}</div>
          <div class="pname">${esc(p.name)}</div>
          <div class="pprice">${cfg.cur} ${money(p.price)}</div>
          ${stockBadge(p)}
        </div>`).join('');
    }
    renderPager(data.page, data.last_page);
  }

  // ------------------------------------------------------------------ live stock poll
  // Every 15s (only while the tab is visible) re-read stock + re-order level for the
  // products on screen and in the cart, so sales on other tills / GRNs show up here.
  let polling = false;
  async function pollStock() {
    if (polling || document.hidden || !cfg.stockUrl) return;
    const ids = new Set(state.items.map((i) => i.id));
    (lastData?.data || []).forEach((p) => ids.add(p.id));
    if (!ids.size) return;
    polling = true;
    try {
      const fresh = await api(`${cfg.stockUrl}?ids=${[...ids].join(',')}`);
      let gridChanged = false;
      let cartChanged = false;
      (lastData?.data || []).forEach((p) => {
        const f = fresh[p.id];
        if (f && (f.stock !== p.stock || f.reorder !== p.reorder)) { p.stock = f.stock; p.reorder = f.reorder; gridChanged = true; }
      });
      state.items.forEach((it) => {
        const f = fresh[it.id];
        if (f && (f.stock !== it.stock || f.reorder !== it.reorder)) { it.stock = f.stock; it.reorder = f.reorder; cartChanged = true; }
      });
      if (gridChanged && lastData) renderGrid(lastData);
      if (cartChanged) {
        save();
        // Don't rebuild the cart while the cashier is typing a quantity in it.
        if (!cartBody.contains(document.activeElement)) renderCart();
      }
      setOnline(true);
    } catch (_) { /* next tick will retry */ } finally {
      polling = false;
    }
  }
  setInterval(pollStock, 15000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) pollStock(); });

  function renderPager(page, last) {
    if (last <= 1) { pager.innerHTML = ''; return; }
    const win = 5;
    let from = Math.max(1, page - Math.floor(win / 2));
    const to = Math.min(last, from + win - 1);
    from = Math.max(1, to - win + 1);
    let h = `<a class="arrow ${page <= 1 ? 'dis' : ''}" data-p="${page - 1}"><i class="fa-solid fa-chevron-left"></i></a>`;
    if (from > 1) h += `<a data-p="1">1</a>${from > 2 ? '<span class="dis">…</span>' : ''}`;
    for (let p = from; p <= to; p++) h += p === page ? `<span class="on">${p}</span>` : `<a data-p="${p}">${p}</a>`;
    if (to < last) h += `${to < last - 1 ? '<span class="dis">…</span>' : ''}<a data-p="${last}">${last}</a>`;
    h += `<a class="arrow ${page >= last ? 'dis' : ''}" data-p="${page + 1}"><i class="fa-solid fa-chevron-right"></i></a>`;
    pager.innerHTML = h;
  }

  pager.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-p]');
    if (!a || a.classList.contains('dis')) return;
    view.page = +a.dataset.p;
    loadProducts();
    grid.parentElement.scrollTop = 0;
  });

  grid.addEventListener('click', (e) => {
    const c = e.target.closest('.pcard[data-i]');
    if (c && lastData) addToCart(lastData.data[+c.dataset.i]);
  });

  $('cats').addEventListener('click', (e) => {
    const b = e.target.closest('.cat');
    if (!b) return;
    document.querySelectorAll('.cat.on').forEach((x) => x.classList.remove('on'));
    b.classList.add('on');
    view.cat = b.dataset.cat;
    view.page = 1;
    loadProducts();
  });

  const onSearch = debounce(() => {
    view.q = search.value.trim();
    view.page = 1;
    loadProducts();
  }, 220);

  search.addEventListener('input', () => {
    $('searchClear').classList.toggle('hidden', !search.value);
    onSearch();
  });
  $('searchClear').addEventListener('click', () => { search.value = ''; search.dispatchEvent(new Event('input')); focusSearch(); });

  // Enter = barcode / exact code. Falls back to "single search result" and then to the first result.
  search.addEventListener('keydown', async (e) => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const code = search.value.trim();
    if (!code) return;
    try {
      const res = await api(`${cfg.lookupUrl}?code=${encodeURIComponent(code)}`);
      addToCart(res.product);
      search.value = '';
      $('searchClear').classList.add('hidden');
      if (view.q) { view.q = ''; view.page = 1; loadProducts(); }
    } catch (err) {
      if (err.status !== 404) { toast(err.message, 'err'); return; }
      if (await searchFallback(code)) return;
    }
  });

  // Camera scan (phones / webcams): exact barcode lookup only, camera stays open for the next item.
  async function addScanned(code) {
    try {
      const res = await api(`${cfg.lookupUrl}?code=${encodeURIComponent(code)}`);
      const p = res.product;
      if (!addToCart(p)) return { ok: false, msg: p.stock <= 0 ? `${p.name} is out of stock` : `Not enough stock: ${p.name}` };
      const it = state.items.find((i) => i.id === p.id);
      return { ok: true, msg: `✓ ${p.name} × ${fmtQty(it?.qty ?? 1)}  ·  ${state.items.length} item(s), ${cfg.cur} ${money(totals().total)}` };
    } catch (err) {
      return { ok: false, msg: err.status === 404 ? `No product for barcode ${code}` : err.message };
    }
  }
  function openCamera() {
    window.Scanner?.open({ title: 'Scan items', continuous: true, onScan: addScanned });
  }
  $('btnCam').addEventListener('click', openCamera);

  // Not an exact code: show matching products; add straight away when there is exactly one.
  async function searchFallback(code) {
    view.q = code; view.page = 1;
    const data = await loadProducts({ force: true });
    if (data && data.data.length === 1) {
      addToCart(data.data[0]);
      search.value = '';
    } else if (!data || !data.data.length) {
      beep(true);
      toast(`No product for “${code}”`, 'err');
      search.select();
    }
    return true;
  }

  // "Scan Barcode": phones open the camera; PCs get the box ready for the USB scanner.
  $('btnScan').addEventListener('click', () => {
    if (touch) { openCamera(); return; }
    search.focus(); search.select();
    $('btnScan').classList.add('listening');
    setTimeout(() => $('btnScan').classList.remove('listening'), 1500);
  });

  // ------------------------------------------------------------------ cart
  const cartBody = $('cartBody');

  function addToCart(p, qty = 1) {
    const ex = state.items.find((i) => i.id === p.id);
    const newQty = r3((ex ? ex.qty : 0) + qty);
    if (!cfg.allowNegative && newQty > p.stock) {
      beep(true);
      toast(p.stock <= 0 ? `${p.name} is out of stock` : `Only ${fmtQty(p.stock)} ${p.unit || ''} of ${p.name} in stock`, 'err');
      return false;
    }
    const ro = Number(p.reorder) || 0;
    const wasLow = isLow(r3(p.stock - (ex ? ex.qty : 0)), ro);
    if (ex) { ex.qty = newQty; ex.stock = p.stock; ex.price = p.price; ex.reorder = ro; }
    else state.items.push({ id: p.id, code: p.code, name: p.name, unit: p.unit, price: p.price, stock: p.stock, reorder: ro, qty: r3(qty) });
    beep();
    commit(p.id);
    // Warn once when this sale takes the item down to its re-order level.
    const left = r3(p.stock - newQty);
    if (!wasLow && isLow(left, ro)) toast(`${p.name}: ${fmtQty(left)} ${p.unit || ''} left – at re-order level (${fmtQty(ro)})`, 'warn', 3500);
    return true;
  }

  function setQty(id, qty) {
    const it = state.items.find((i) => i.id === id);
    if (!it) return;
    qty = r3(qty);
    if (!(qty > 0)) { removeItem(id); return; }
    if (!cfg.allowNegative && qty > it.stock) {
      toast(`Only ${fmtQty(it.stock)} in stock`, 'err');
      qty = it.stock;
    }
    it.qty = qty;
    commit();
  }

  function removeItem(id) {
    state.items = state.items.filter((i) => i.id !== id);
    commit();
  }

  function totals() {
    const sub = r2(state.items.reduce((s, i) => s + r2(i.qty * i.price), 0));
    const v = Math.max(0, Number(state.discVal) || 0);
    const disc = state.discType === 'percent' ? r2(sub * Math.min(v, 100) / 100) : r2(Math.min(v, sub));
    const tax = r2((sub - disc) * cfg.taxRate / 100);
    return { sub, disc, tax, total: r2(sub - disc + tax) };
  }

  function commit(flashId) {
    save();
    renderCart(flashId);
    if (lastData) renderGrid(lastData); // grid shows stock left after this cart
  }

  function cartStock(it) {
    const left = r3(it.stock - it.qty);
    const ro = Number(it.reorder) || 0;
    const cls = left < 0 ? 'none' : (isLow(left, ro) ? 'low' : '');
    return `<small class="stk ${cls}">Stock: ${fmtQty(it.stock)} → ${fmtQty(left)} left · ROL ${fmtQty(ro)}</small>`;
  }

  function renderCart(flashId) {
    $('cartEmpty').classList.toggle('hidden', state.items.length > 0);
    cartBody.innerHTML = state.items.map((it, i) => `
      <tr data-id="${it.id}" class="${it.id === flashId ? 'flash' : ''}">
        <td class="idx"><span>${i + 1}</span></td>
        <td class="iname">${esc(it.name)}<small>${esc(it.code)}</small>${cartStock(it)}</td>
        <td style="text-align:center">
          <div class="qty">
            <button type="button" data-a="dec" aria-label="Decrease">−</button>
            <input type="text" inputmode="decimal" value="${fmtQty(it.qty)}" data-a="qty" aria-label="Quantity">
            <button type="button" data-a="inc" aria-label="Increase">+</button>
          </div>
        </td>
        <td class="num">${money(it.price)}</td>
        <td class="num"><b>${money(r2(it.qty * it.price))}</b></td>
        <td style="width:36px"><button type="button" class="del" data-a="del" title="Remove"><i class="fa-regular fa-trash-can"></i></button></td>
      </tr>`).join('');

    if (flashId) cartBody.querySelector(`tr[data-id="${flashId}"]`)?.scrollIntoView({ block: 'nearest' });

    const t = totals();
    $('tSub').textContent = `${cfg.cur} ${money(t.sub)}`;
    $('tTax').textContent = `${cfg.cur} ${money(t.tax)}`;
    $('tTotal').textContent = `${cfg.cur} ${money(t.total)}`;
    $('btnPay').disabled = state.items.length === 0;
    $('mbarCount').textContent = state.items.length;
    $('mbarTotal').textContent = `${cfg.cur} ${money(t.total)}`;
    updateMbar();

    $('discPct').classList.toggle('on', state.discType === 'percent');
    $('discAmt').classList.toggle('on', state.discType === 'amount');
    if (document.activeElement !== $('discVal')) $('discVal').value = Number(state.discVal || 0).toFixed(2);

    renderCustomer();
  }

  cartBody.addEventListener('click', (e) => {
    const b = e.target.closest('[data-a]');
    if (!b || b.tagName === 'INPUT') return;
    const id = +b.closest('tr').dataset.id;
    const it = state.items.find((i) => i.id === id);
    if (b.dataset.a === 'inc') setQty(id, it.qty + 1);
    if (b.dataset.a === 'dec') setQty(id, it.qty - 1);
    if (b.dataset.a === 'del') removeItem(id);
  });
  cartBody.addEventListener('change', (e) => {
    if (e.target.dataset.a !== 'qty') return;
    setQty(+e.target.closest('tr').dataset.id, parseFloat(e.target.value));
  });
  cartBody.addEventListener('keydown', (e) => {
    if (e.target.dataset.a === 'qty' && e.key === 'Enter') { e.target.blur(); focusSearch(); }
  });
  cartBody.addEventListener('focusin', (e) => { if (e.target.dataset.a === 'qty') e.target.select(); });

  // Discount
  $('discPct').addEventListener('click', () => { state.discType = 'percent'; commit(); });
  $('discAmt').addEventListener('click', () => { state.discType = 'amount'; commit(); });
  $('discVal').addEventListener('input', (e) => { state.discVal = parseFloat(e.target.value) || 0; save(); renderCart(); });
  $('discVal').addEventListener('focus', (e) => e.target.select());
  $('discVal').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.target.blur(); focusSearch(); } });
  $('discVal').addEventListener('blur', () => renderCart());
  $('btnDisc').addEventListener('click', focusDiscount);
  function focusDiscount() { $('discVal').focus(); }

  // Clear
  $('btnClear').addEventListener('click', clearSale);
  function clearSale(ask = true) {
    if (ask && state.items.length && !confirm('Clear the current sale?')) return;
    state = blank();
    commit();
    focusSearch();
  }

  // ------------------------------------------------------------------ customer picker
  const custPop = $('custPop');
  const custList = $('custList');
  let custResults = [];
  let custHl = 0;

  function renderCustomer() {
    const c = state.customer;
    $('custBtn').textContent = c ? `${c.name}${c.phone ? ' · ' + c.phone : ''}` : 'Walk-in Customer';
    const due = c && Number(c.balance) > 0;
    $('custDue').classList.toggle('hidden', !due);
    if (due) $('custDue').textContent = `Outstanding: ${cfg.cur} ${money(c.balance)}`;
  }

  const loadCustomers = debounce(async () => {
    try {
      custResults = await api(`${cfg.customersUrl}?q=${encodeURIComponent($('custSearch').value.trim())}`);
      custHl = 0;
      drawCustomers();
    } catch (e) { toast(e.message, 'err'); }
  }, 200);

  function drawCustomers() {
    const rows = [{ id: null, name: 'Walk-in Customer' }, ...custResults];
    custList.innerHTML = rows.map((c, i) => `<li data-i="${i}" class="${i === custHl ? 'hl' : ''}"><span>${esc(c.name)}</span><small>${esc(c.phone || '')}</small></li>`).join('');
  }

  function pickCustomer(i) {
    const rows = [null, ...custResults];
    state.customer = rows[i] || null;
    custPop.classList.remove('open');
    commit();
  }

  $('custBtn').addEventListener('click', () => {
    custPop.classList.toggle('open');
    if (custPop.classList.contains('open')) { $('custSearch').value = ''; loadCustomers(); $('custSearch').focus(); }
  });
  $('custSearch').addEventListener('input', loadCustomers);
  $('custSearch').addEventListener('keydown', (e) => {
    const n = custResults.length + 1;
    if (e.key === 'ArrowDown') { custHl = (custHl + 1) % n; drawCustomers(); e.preventDefault(); }
    if (e.key === 'ArrowUp') { custHl = (custHl - 1 + n) % n; drawCustomers(); e.preventDefault(); }
    if (e.key === 'Enter') { pickCustomer(custHl); e.preventDefault(); }
    if (e.key === 'Escape') custPop.classList.remove('open');
  });
  custList.addEventListener('click', (e) => { const li = e.target.closest('li'); if (li) pickCustomer(+li.dataset.i); });
  document.addEventListener('click', (e) => { if (!e.target.closest('.cust-pick')) custPop.classList.remove('open'); });

  $('custForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(e.target));
    try {
      const c = await api(cfg.customerStoreUrl, { method: 'POST', body });
      state.customer = c;
      commit();
      closeModal('mCustomer');
      e.target.reset();
      toast(`Customer ${c.name} added`);
    } catch (err) { toast(err.message, 'err'); }
  });

  // ------------------------------------------------------------------ payment
  let method = 'cash';

  function openPay() {
    if (!state.items.length) return;
    const t = totals();
    $('payTotal').textContent = `${cfg.cur} ${money(t.total)}`;
    $('payNote').value = '';
    try { $('autoPrint').checked = localStorage.getItem('pos.autoPrint') !== '0'; } catch (_) { /* ignore */ }
    setMethod('cash');
    $('tender').value = '';
    const opts = new Set([t.total]);
    [100, 500, 1000, 5000].forEach((s) => { const v = Math.ceil(t.total / s) * s; if (v > t.total) opts.add(v); });
    $('quickCash').innerHTML = [...opts].slice(0, 5).map((v, i) => `<button type="button" class="btn btn-sm" data-v="${v}">${i === 0 ? 'Exact' : money(v)}</button>`).join('');
    updateChange();
    openModal('mPay');
    setTimeout(() => $('tender').focus(), 40);
  }

  function setMethod(m) {
    method = m;
    document.querySelectorAll('#methods button').forEach((b) => b.classList.toggle('on', b.dataset.m === m));
    const needsTender = m === 'cash' || m === 'credit';
    $('tenderWrap').classList.toggle('hidden', !needsTender);
    $('tenderLbl').textContent = m === 'credit' ? 'Amount paid now (rest goes to customer account)' : 'Cash Received';
    $('quickCash').classList.toggle('hidden', m !== 'cash');
    if (m === 'credit' && !state.customer) toast('Select a customer for credit sales', 'err');
    updateChange();
  }

  function updateChange() {
    const t = totals().total;
    const paid = parseFloat($('tender').value) || 0;
    const box = $('changeBox');
    if (method === 'credit') {
      box.classList.remove('neg');
      box.firstElementChild.textContent = 'To customer account';
      $('changeVal').textContent = `${cfg.cur} ${money(Math.max(t - paid, 0))}`;
      return;
    }
    box.firstElementChild.textContent = paid >= t || !$('tender').value ? 'Change' : 'Balance due';
    box.classList.toggle('neg', !!$('tender').value && paid < t);
    $('changeVal').textContent = `${cfg.cur} ${money($('tender').value ? Math.abs(paid - t) : 0)}`;
  }

  $('methods').addEventListener('click', (e) => { const b = e.target.closest('button'); if (b) setMethod(b.dataset.m); });
  $('tender').addEventListener('input', updateChange);
  $('quickCash').addEventListener('click', (e) => { const b = e.target.closest('button'); if (b) { $('tender').value = b.dataset.v; updateChange(); } });
  $('mPay').addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target.id !== 'payNote') { e.preventDefault(); confirmPay(); } });
  $('btnConfirmPay').addEventListener('click', confirmPay);
  $('autoPrint').addEventListener('change', (e) => { try { localStorage.setItem('pos.autoPrint', e.target.checked ? '1' : '0'); } catch (_) { /* ignore */ } });

  let paying = false;
  async function confirmPay() {
    if (paying) return;
    const t = totals().total;
    let paid = parseFloat($('tender').value);
    if (method === 'cash') {
      if (!$('tender').value) paid = t; // empty = exact cash
      if (paid + 0.001 < t) { toast('Cash received is less than total', 'err'); $('tender').focus(); return; }
    }
    if (method === 'credit' && !state.customer) { toast('Select a customer for credit sales', 'err'); return; }

    paying = true;
    $('btnConfirmPay').disabled = true;
    try {
      const res = await api(cfg.checkoutUrl, {
        method: 'POST',
        body: {
          items: state.items.map((i) => ({ product_id: i.id, qty: i.qty })),
          customer_id: state.customer?.id || null,
          discount_type: state.discType,
          discount_value: Number(state.discVal) || 0,
          payment_method: method,
          paid_amount: method === 'credit' ? (paid || 0) : (method === 'cash' ? paid : t),
          note: $('payNote').value || null,
          held_id: state.heldId,
        },
      });
      closeModal('mPay');
      lastSaleReceipt = res.receipt_url;
      $('billNo').textContent = res.next_bill;
      toast(res.change > 0 ? `${res.bill_no} saved · Change ${cfg.cur} ${money(res.change)}` : `${res.bill_no} saved`, 'ok', 4000);
      if ($('autoPrint').checked) printReceipt(res.receipt_url);
      state = blank();
      commit();
      cache.clear();
      loadProducts({ force: true }); // refresh stock badges
      refreshHeldCount();
      focusSearch();
    } catch (err) {
      toast(err.message, 'err', 5000);
    } finally {
      paying = false;
      $('btnConfirmPay').disabled = false;
    }
  }
  $('btnPay').addEventListener('click', openPay);

  // ------------------------------------------------------------------ printing
  function printReceipt(url) {
    $('printFrame').src = url + (url.includes('?') ? '&' : '?') + 'print=1&_=' + Date.now();
  }
  function printLast() {
    printReceipt(lastSaleReceipt || cfg.lastReceiptUrl);
  }
  $('btnPrint').addEventListener('click', printLast);

  // ------------------------------------------------------------------ hold / recall
  async function holdOrRecall() {
    if (!state.items.length) { openHeld(); return; }
    const label = prompt('Hold this bill – optional reference (e.g. customer name):', state.customer?.name || '');
    if (label === null) return;
    try {
      await api(cfg.heldUrl, {
        method: 'POST',
        body: {
          items: state.items.map((i) => ({ id: i.id, qty: i.qty })),
          customer_id: state.customer?.id || null,
          customer_name: state.customer?.name || null,
          discount_type: state.discType,
          discount_value: state.discVal,
          label: label || null,
          total: totals().total,
          held_id: state.heldId,
        },
      });
      toast('Bill held. Press F7 on an empty sale to recall.');
      clearSale(false);
      refreshHeldCount();
    } catch (e) { toast(e.message, 'err'); }
  }

  async function openHeld() {
    try {
      const list = await api(cfg.heldUrl);
      $('heldList').innerHTML = list.length ? list.map((h) => `
        <li>
          <div class="info"><b>${esc(h.label || h.customer)}</b><small>${h.item_count} items · ${h.time}</small></div>
          <b class="num">${cfg.cur} ${money(h.total)}</b>
          <button class="btn btn-primary btn-sm" data-recall="${h.id}">Recall</button>
          <button class="btn btn-sm btn-ghost" data-drop="${h.id}" title="Delete"><i class="fa-regular fa-trash-can" style="color:var(--red-600)"></i></button>
        </li>`).join('') : '<li class="muted" style="justify-content:center">No held bills</li>';
      openModal('mHeld');
    } catch (e) { toast(e.message, 'err'); }
  }

  $('heldList').addEventListener('click', async (e) => {
    const r = e.target.closest('[data-recall]');
    const d = e.target.closest('[data-drop]');
    try {
      if (r) {
        if (state.items.length && !confirm('Replace the current sale with the held bill?')) return;
        const h = await api(`${cfg.heldUrl}/${r.dataset.recall}`);
        state = {
          items: h.items, heldId: h.id, discType: h.discount_type || 'percent', discVal: h.discount_value || 0,
          customer: h.customer_id ? { id: h.customer_id, name: h.customer_name || 'Customer' } : null,
        };
        commit();
        closeModal('mHeld');
        toast('Held bill recalled');
      }
      if (d && confirm('Delete this held bill?')) {
        await api(`${cfg.heldUrl}/${d.dataset.drop}`, { method: 'DELETE' });
        d.closest('li').remove();
        refreshHeldCount();
      }
    } catch (err) { toast(err.message, 'err'); }
  });

  async function refreshHeldCount() {
    try {
      const list = await api(cfg.heldUrl);
      $('heldCount').textContent = list.length;
      $('heldCount').classList.toggle('hidden', !list.length);
    } catch (_) { /* non-critical */ }
  }
  $('btnHold').addEventListener('click', holdOrRecall);

  // ------------------------------------------------------------------ keyboard
  document.addEventListener('keydown', (e) => {
    const modalOpen = document.querySelector('.modal-bg.open');
    const map = { F2: () => { focusSearch(); search.select(); }, F4: () => clearSale(), F6: focusDiscount, F7: holdOrRecall, F8: openPay, F12: printLast };
    if (map[e.key]) {
      e.preventDefault();
      if (modalOpen && e.key !== 'F12') return;
      map[e.key]();
      return;
    }
    // Barcode scanners "type" into whatever has focus – route stray keystrokes into the search box.
    const tag = document.activeElement?.tagName;
    if (!modalOpen && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey && !['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) {
      focusSearch();
    }
  });

  // ------------------------------------------------------------------ misc
  let audioCtx;
  function beep(err = false) {
    try {
      audioCtx ??= new (window.AudioContext || window.webkitAudioContext)();
      const o = audioCtx.createOscillator(); const g = audioCtx.createGain();
      o.frequency.value = err ? 220 : 1200; g.gain.value = 0.05;
      o.connect(g); g.connect(audioCtx.destination); o.start(); o.stop(audioCtx.currentTime + (err ? 0.25 : 0.07));
    } catch (_) { /* audio unavailable */ }
  }

  function setOnline(ok) {
    const s = $('netStatus');
    s.classList.toggle('off', !ok);
    s.lastChild.textContent = ok ? ' POS Ready' : ' Connection problem';
  }
  window.addEventListener('offline', () => setOnline(false));
  window.addEventListener('online', () => { setOnline(true); loadProducts({ force: true }); });

  // ------------------------------------------------------------------ mobile cart bar
  // Shown on narrow screens (CSS) when the cart has items and the cart panel is scrolled out of view.
  let cartVisible = false;
  function updateMbar() {
    $('mbar').classList.toggle('away', cartVisible || state.items.length === 0);
  }
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(([en]) => { cartVisible = en.isIntersecting; updateMbar(); }, { threshold: 0.15 })
      .observe(document.querySelector('.pos-right'));
  }
  $('mbar').addEventListener('click', () => document.querySelector('.pos-right').scrollIntoView({ behavior: 'smooth', block: 'start' }));

  // ------------------------------------------------------------------ boot
  renderCart();
  loadProducts();
  refreshHeldCount();
})();
