/* ==========================================================================
   Full-screen camera barcode scanner (phones / tablets / webcams)
   Shared by the POS and Stock In pages.

   Scanner.open({
     title: 'Scan items',
     continuous: true,               // keep the camera open for the next item
     onScan: async (code) => ({ ok: true, msg: 'Added: Cement' }),  // msg shown in the footer
     onClose: () => {},
   });

   The html5-qrcode library is loaded only the first time the camera is used.
   ========================================================================== */
(function () {
  const LIB = document.currentScript?.dataset.lib;
  let loading = null;
  let overlay = null;
  let cam = null;
  let opts = {};
  let last = { code: '', at: 0 };
  let busy = false;

  const isTouch = () => window.matchMedia('(pointer: coarse)').matches;

  function loadLib() {
    if (window.Html5Qrcode) return Promise.resolve();
    if (!loading) {
      loading = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = LIB;
        s.onload = resolve;
        s.onerror = () => { loading = null; reject(new Error('Camera scanner could not load. Check the connection.')); };
        document.head.appendChild(s);
      });
    }
    return loading;
  }

  function build() {
    overlay = document.createElement('div');
    overlay.className = 'scanner';
    overlay.innerHTML = `
      <div class="scanner-head">
        <i class="fa-solid fa-barcode"></i><b class="scanner-title"></b>
        <button type="button" class="scanner-x" aria-label="Close camera"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="scanner-view"><div id="scannerReader"></div></div>
      <div class="scanner-foot">
        <div class="scanner-msg"></div>
        <button type="button" class="btn btn-primary scanner-done"><i class="fa-solid fa-check"></i> Done</button>
      </div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('.scanner-x').addEventListener('click', () => close('user'));
    overlay.querySelector('.scanner-done').addEventListener('click', () => close('user'));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && overlay.classList.contains('open')) close('user'); });
  }

  function msg(text, type = '') {
    const m = overlay.querySelector('.scanner-msg');
    m.className = 'scanner-msg ' + type;
    m.textContent = text;
  }

  function feedback(ok) {
    try {
      const a = new (window.AudioContext || window.webkitAudioContext)();
      const o = a.createOscillator(); const g = a.createGain();
      o.frequency.value = ok ? 1200 : 220; g.gain.value = 0.08;
      o.connect(g); g.connect(a.destination); o.start();
      setTimeout(() => { o.stop(); a.close(); }, ok ? 90 : 250);
    } catch (_) { /* no audio */ }
    navigator.vibrate?.(ok ? 60 : [80, 60, 80]);
  }

  async function handle(code) {
    code = String(code || '').trim();
    const now = Date.now();
    // The camera sees the same barcode many times a second: ignore repeats for 2s.
    if (!code || busy || (code === last.code && now - last.at < 2000)) return;
    last = { code, at: now };

    if (!opts.continuous) {
      feedback(true);
      await close('scanned');
      opts.onScan?.(code);
      return;
    }

    busy = true;
    msg('Checking ' + code + '...');
    try {
      const r = (await opts.onScan?.(code)) || { ok: true, msg: code };
      feedback(r.ok !== false);
      msg(r.msg || code, r.ok === false ? 'err' : 'ok');
    } catch (e) {
      feedback(false);
      msg(e.message || 'Scan failed', 'err');
    } finally {
      // short pause so the same item isn't added twice while the phone is still pointed at it
      setTimeout(() => { busy = false; }, 700);
    }
  }

  async function open(options = {}) {
    opts = options;
    if (!overlay) build();
    overlay.querySelector('.scanner-title').textContent = opts.title || 'Scan barcode';
    overlay.querySelector('.scanner-done').classList.toggle('hidden', !opts.continuous);
    overlay.classList.add('open');
    document.body.classList.add('scanner-open');
    last = { code: '', at: 0 };
    busy = false;
    msg('Starting camera...');

    if (!window.isSecureContext) {
      msg('The camera only works on an https:// link.', 'err');
      return;
    }
    try {
      await loadLib();
      const F = window.Html5QrcodeSupportedFormats;
      cam = new window.Html5Qrcode('scannerReader', {
        verbose: false,
        formatsToSupport: [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.CODE_39, F.CODE_93, F.ITF, F.CODABAR, F.QR_CODE],
        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
      });
      await cam.start(
        { facingMode: 'environment' },
        { fps: 12, qrbox: (w, h) => ({ width: Math.floor(Math.min(w * 0.85, 420)), height: Math.floor(Math.min(h * 0.45, 200)) }) },
        handle,
        () => {},
      );
      msg(opts.continuous ? 'Point the camera at each barcode. Tap Done when finished.' : 'Point the camera at the barcode.');
    } catch (e) {
      const t = String(e?.message || e);
      msg(/permission|notallowed/i.test(t) ? 'Camera permission was denied. Allow camera access in the browser settings.' : 'Camera not available: ' + t, 'err');
    }
  }

  async function close(reason = 'user') {
    const c = cam; cam = null;
    try { if (c?.isScanning) await c.stop(); c?.clear(); } catch (_) { /* already stopped */ }
    overlay?.classList.remove('open');
    document.body.classList.remove('scanner-open');
    opts.onClose?.(reason);
  }

  window.Scanner = { open, close, isTouch };
})();
