/* Shared helpers for every page */
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  window.money = (n) => Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  window.esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /** fetch wrapper: JSON in/out, CSRF, Laravel validation errors surfaced as Error.message */
  window.api = async (url, { method = 'GET', body, signal } = {}) => {
    const res = await fetch(url, {
      method,
      signal,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: body ? JSON.stringify(body) : undefined,
      credentials: 'same-origin',
    });
    let data = null;
    try { data = await res.json(); } catch (_) { /* empty body */ }
    if (!res.ok) {
      if (res.status === 419 || res.status === 401) { location.reload(); }
      const msg = data?.errors ? Object.values(data.errors)[0][0] : (data?.message || `Request failed (${res.status})`);
      const err = new Error(msg); err.status = res.status; err.data = data; throw err;
    }
    return data;
  };

  window.toast = (msg, type = 'ok', ms = 2600) => {
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<i class="fa-solid ${type === 'err' ? 'fa-circle-exclamation' : (type === 'warn' ? 'fa-triangle-exclamation' : 'fa-circle-check')}"></i><span>${esc(msg)}</span>`;
    document.getElementById('toasts').appendChild(el);
    setTimeout(() => el.remove(), ms);
  };

  window.debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

  // Modals: <div class="modal-bg" id="x">, open with data-open="x", close with [data-close] or Esc / backdrop
  window.openModal = (id) => { const m = document.getElementById(id); m?.classList.add('open'); setTimeout(() => m?.querySelector('[autofocus], input:not([type=hidden]), select')?.focus(), 30); };
  window.closeModal = (id) => document.getElementById(id)?.classList.remove('open');
  document.addEventListener('click', (e) => {
    const o = e.target.closest('[data-open]');
    if (o) { e.preventDefault(); openModal(o.dataset.open); return; }
    const c = e.target.closest('[data-close]');
    if (c) { c.closest('.modal-bg')?.classList.remove('open'); return; }
    if (e.target.classList.contains('modal-bg')) e.target.classList.remove('open');

    const d = e.target.closest('[data-dropdown]');
    document.querySelectorAll('.dropdown.open').forEach((x) => { if (!d || x !== d.nextElementSibling) x.classList.remove('open'); });
    if (d) d.nextElementSibling.classList.toggle('open');
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-bg.open').forEach((m) => m.classList.remove('open'));
  });

  // Fill an edit modal from a row's data-json: <button data-edit="modalId" data-action="url" data-json='{...}'>
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-edit]');
    if (!b) return;
    const m = document.getElementById(b.dataset.edit);
    const f = m.querySelector('form');
    const data = JSON.parse(b.dataset.json || '{}');
    f.action = b.dataset.action;
    f.querySelectorAll('[name]').forEach((inp) => {
      if (inp.type === 'hidden' || !(inp.name in data)) return;
      if (inp.type === 'checkbox') inp.checked = !!data[inp.name];
      else inp.value = data[inp.name] ?? '';
    });
    openModal(b.dataset.edit);
  });

  // Confirm before destructive submits: <form data-confirm="Sure?">
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });
})();
