import { useState, useEffect } from 'react';
import { FiCheck, FiSun, FiMoon, FiMonitor, FiDroplet, FiType, FiLayout, FiSave, FiRefreshCw } from 'react-icons/fi';

const PRESET_THEMES = [
  { id: 'indigo',   label: 'Indigo',     primary: '#4F46E5', sidebar: '#0F172A' },
  { id: 'blue',     label: 'Ocean Blue', primary: '#0EA5E9', sidebar: '#0C1A2E' },
  { id: 'emerald',  label: 'Emerald',    primary: '#10B981', sidebar: '#0A1F18' },
  { id: 'violet',   label: 'Violet',     primary: '#8B5CF6', sidebar: '#120E22' },
  { id: 'rose',     label: 'Rose',       primary: '#F43F5E', sidebar: '#1E0B10' },
  { id: 'amber',    label: 'Amber',      primary: '#F59E0B', sidebar: '#1A1205' },
  { id: 'cyan',     label: 'Cyan',       primary: '#06B6D4', sidebar: '#08161A' },
  { id: 'teal',     label: 'Teal',       primary: '#14B8A6', sidebar: '#091817' },
  { id: 'custom',   label: 'Custom',     primary: null,      sidebar: null },
];

const FONT_SIZES = [
  { id: 'sm',   label: 'Small',   size: '13px' },
  { id: 'md',   label: 'Medium',  size: '14px' },
  { id: 'lg',   label: 'Large',   size: '15px' },
];

const RADIUS_OPTIONS = [
  { id: 'sharp',    label: 'Sharp',    value: '4px' },
  { id: 'rounded',  label: 'Rounded',  value: '8px' },
  { id: 'pill',     label: 'Pill',     value: '12px' },
];

function hexToHSL(hex) {
  let r = parseInt(hex.slice(1, 3), 16) / 255;
  let g = parseInt(hex.slice(3, 5), 16) / 255;
  let b = parseInt(hex.slice(5, 7), 16) / 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b);
  let h, s, l = (max + min) / 2;
  if (max === min) { h = s = 0; }
  else {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
      case g: h = ((b - r) / d + 2) / 6; break;
      case b: h = ((r - g) / d + 4) / 6; break;
    }
  }
  return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
}

function adjustBrightness(hex, amount) {
  const num = parseInt(hex.replace('#', ''), 16);
  const r = Math.min(255, Math.max(0, (num >> 16) + amount));
  const g = Math.min(255, Math.max(0, ((num >> 8) & 0xff) + amount));
  const b = Math.min(255, Math.max(0, (num & 0xff) + amount));
  return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('');
}

function applyThemeColors(primary, sidebar) {
  const root = document.documentElement;
  if (primary) {
    root.style.setProperty('--primary', primary);
    root.style.setProperty('--primary-hover', adjustBrightness(primary, -20));
    root.style.setProperty('--primary-light', primary + '22');
    root.style.setProperty('--primary-glow', `0 4px 14px ${primary}44`);
    root.style.setProperty('--sidebar-active-text', primary);
    root.style.setProperty('--sidebar-active-border', primary);
    root.style.setProperty('--sidebar-active-bg', primary + '22');
    root.style.setProperty('--border-focus', primary);
  }
  if (sidebar) {
    root.style.setProperty('--sidebar-bg', sidebar);
  }
}

const Settings = ({ theme, onThemeChange }) => {
  const [activePreset, setActivePreset] = useState(
    () => localStorage.getItem('pos_color_preset') || 'indigo'
  );
  const [customPrimary, setCustomPrimary] = useState(
    () => localStorage.getItem('pos_custom_primary') || '#4F46E5'
  );
  const [customSidebar, setCustomSidebar] = useState(
    () => localStorage.getItem('pos_custom_sidebar') || '#0F172A'
  );
  const [fontSize, setFontSize] = useState(
    () => localStorage.getItem('pos_font_size') || 'md'
  );
  const [radius, setRadius] = useState(
    () => localStorage.getItem('pos_radius') || 'rounded'
  );
  const [saved, setSaved] = useState(false);

  // Re-apply saved settings on mount
  useEffect(() => {
    const preset = PRESET_THEMES.find(p => p.id === activePreset);
    if (preset && preset.id !== 'custom') {
      applyThemeColors(preset.primary, preset.sidebar);
    } else {
      applyThemeColors(customPrimary, customSidebar);
    }
    const fz = FONT_SIZES.find(f => f.id === fontSize);
    if (fz) document.documentElement.style.setProperty('font-size', fz.size);
    const rad = RADIUS_OPTIONS.find(r => r.id === radius);
    if (rad) document.documentElement.style.setProperty('--radius', rad.value);
  }, []);

  const handlePresetSelect = (preset) => {
    setActivePreset(preset.id);
    if (preset.id !== 'custom') {
      applyThemeColors(preset.primary, preset.sidebar);
    } else {
      applyThemeColors(customPrimary, customSidebar);
    }
  };

  const handleCustomPrimary = (val) => {
    setCustomPrimary(val);
    setActivePreset('custom');
    applyThemeColors(val, customSidebar);
  };

  const handleCustomSidebar = (val) => {
    setCustomSidebar(val);
    setActivePreset('custom');
    applyThemeColors(customPrimary, val);
  };

  const handleFontSize = (fz) => {
    setFontSize(fz.id);
    document.documentElement.style.setProperty('font-size', fz.size);
  };

  const handleRadius = (r) => {
    setRadius(r.id);
    document.documentElement.style.setProperty('--radius', r.value);
    document.documentElement.style.setProperty('--radius-lg', `calc(${r.value} + 4px)`);
  };

  const handleSave = () => {
    localStorage.setItem('pos_color_preset', activePreset);
    localStorage.setItem('pos_custom_primary', customPrimary);
    localStorage.setItem('pos_custom_sidebar', customSidebar);
    localStorage.setItem('pos_font_size', fontSize);
    localStorage.setItem('pos_radius', radius);
    setSaved(true);
    setTimeout(() => setSaved(false), 2000);
  };

  const handleReset = () => {
    localStorage.removeItem('pos_color_preset');
    localStorage.removeItem('pos_custom_primary');
    localStorage.removeItem('pos_custom_sidebar');
    localStorage.removeItem('pos_font_size');
    localStorage.removeItem('pos_radius');
    setActivePreset('indigo');
    setCustomPrimary('#4F46E5');
    setCustomSidebar('#0F172A');
    setFontSize('md');
    setRadius('rounded');
    applyThemeColors('#4F46E5', '#0F172A');
    document.documentElement.style.setProperty('font-size', '14px');
    document.documentElement.style.setProperty('--radius', '8px');
    document.documentElement.style.setProperty('--radius-lg', '12px');
  };

  const currentPreset = PRESET_THEMES.find(p => p.id === activePreset);
  const activePrimary = activePreset === 'custom' ? customPrimary : currentPreset?.primary;

  return (
    <div className="page settings-page">
      {/* Header */}
      <div className="page-header-row">
        <div className="page-header">
          <h1 className="page-title">Settings</h1>
          <p className="page-subtitle">Customize your POS system appearance and preferences</p>
        </div>
        <div className="page-actions">
          <button className="btn btn-secondary" onClick={handleReset}>
            <FiRefreshCw size={14} /> Reset Defaults
          </button>
          <button className={`btn btn-primary ${saved ? 'btn-saved' : ''}`} onClick={handleSave}>
            {saved ? <FiCheck size={14} /> : <FiSave size={14} />}
            {saved ? 'Saved!' : 'Save Settings'}
          </button>
        </div>
      </div>

      <div className="settings-grid">
        {/* ── Color Mode ── */}
        <div className="card settings-card">
          <div className="card-header">
            <div className="card-title"><FiSun size={15} /> Color Mode</div>
          </div>
          <div className="card-body">
            <div className="settings-mode-grid">
              {[
                { id: 'light', label: 'Light',  icon: FiSun,     desc: 'Clean white interface' },
                { id: 'dark',  label: 'Dark',   icon: FiMoon,    desc: 'Easy on the eyes' },
              ].map(m => (
                <button
                  key={m.id}
                  className={`settings-mode-card ${theme === m.id ? 'active' : ''}`}
                  onClick={() => onThemeChange(m.id)}
                >
                  <div className="settings-mode-icon" style={{ background: m.id === 'light' ? '#F1F5F9' : '#0F172A' }}>
                    <m.icon size={22} color={m.id === 'light' ? '#4F46E5' : '#818CF8'} />
                  </div>
                  <div className="settings-mode-label">{m.label}</div>
                  <div className="settings-mode-desc">{m.desc}</div>
                  {theme === m.id && <div className="settings-mode-check"><FiCheck size={12} /></div>}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* ── Color Theme ── */}
        <div className="card settings-card settings-card-wide">
          <div className="card-header">
            <div className="card-title"><FiDroplet size={15} /> Accent Color</div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <span style={{ fontSize: 12, color: 'var(--text-muted)' }}>Active:</span>
              <span className="settings-color-dot" style={{ background: activePrimary }} />
              <span style={{ fontSize: 12, fontWeight: 600, color: 'var(--text-secondary)', fontFamily: 'monospace' }}>{activePrimary}</span>
            </div>
          </div>
          <div className="card-body">
            <div className="settings-presets">
              {PRESET_THEMES.filter(p => p.id !== 'custom').map(preset => (
                <button
                  key={preset.id}
                  className={`settings-preset ${activePreset === preset.id ? 'active' : ''}`}
                  onClick={() => handlePresetSelect(preset)}
                  title={preset.label}
                >
                  <div className="settings-preset-swatch">
                    <div className="settings-preset-sidebar" style={{ background: preset.sidebar }} />
                    <div className="settings-preset-content">
                      <div className="settings-preset-btn" style={{ background: preset.primary }} />
                    </div>
                  </div>
                  <div className="settings-preset-label">{preset.label}</div>
                  {activePreset === preset.id && (
                    <div className="settings-preset-check" style={{ background: preset.primary }}>
                      <FiCheck size={10} color="#fff" />
                    </div>
                  )}
                </button>
              ))}

              {/* Custom preset */}
              <button
                className={`settings-preset ${activePreset === 'custom' ? 'active' : ''}`}
                onClick={() => handlePresetSelect({ id: 'custom' })}
              >
                <div className="settings-preset-swatch settings-preset-custom">
                  <div style={{ fontSize: 20 }}>🎨</div>
                </div>
                <div className="settings-preset-label">Custom</div>
                {activePreset === 'custom' && (
                  <div className="settings-preset-check" style={{ background: customPrimary }}>
                    <FiCheck size={10} color="#fff" />
                  </div>
                )}
              </button>
            </div>

            {/* Custom color pickers */}
            {activePreset === 'custom' && (
              <div className="settings-custom-colors">
                <div className="settings-color-picker-row">
                  <label className="form-label">Accent / Primary Color</label>
                  <div className="settings-color-input-wrap">
                    <input type="color" value={customPrimary} onChange={e => handleCustomPrimary(e.target.value)} className="settings-color-picker" />
                    <span className="settings-color-hex">{customPrimary}</span>
                  </div>
                </div>
                <div className="settings-color-picker-row">
                  <label className="form-label">Sidebar Background</label>
                  <div className="settings-color-input-wrap">
                    <input type="color" value={customSidebar} onChange={e => handleCustomSidebar(e.target.value)} className="settings-color-picker" />
                    <span className="settings-color-hex">{customSidebar}</span>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* ── Font Size ── */}
        <div className="card settings-card">
          <div className="card-header">
            <div className="card-title"><FiType size={15} /> Font Size</div>
          </div>
          <div className="card-body">
            <div className="settings-option-list">
              {FONT_SIZES.map(fz => (
                <button
                  key={fz.id}
                  className={`settings-option-item ${fontSize === fz.id ? 'active' : ''}`}
                  onClick={() => handleFontSize(fz)}
                >
                  <span style={{ fontSize: fz.size, fontWeight: 600 }}>Aa</span>
                  <div>
                    <div className="settings-option-label">{fz.label}</div>
                    <div className="settings-option-desc">{fz.size} base</div>
                  </div>
                  {fontSize === fz.id && <div className="settings-option-check"><FiCheck size={12} /></div>}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* ── Border Radius ── */}
        <div className="card settings-card">
          <div className="card-header">
            <div className="card-title"><FiLayout size={15} /> Border Style</div>
          </div>
          <div className="card-body">
            <div className="settings-option-list">
              {RADIUS_OPTIONS.map(r => (
                <button
                  key={r.id}
                  className={`settings-option-item ${radius === r.id ? 'active' : ''}`}
                  onClick={() => handleRadius(r)}
                >
                  <div className="settings-radius-preview" style={{ borderRadius: r.value, background: 'var(--primary)', opacity: 0.8 }} />
                  <div>
                    <div className="settings-option-label">{r.label}</div>
                    <div className="settings-option-desc">{r.value} radius</div>
                  </div>
                  {radius === r.id && <div className="settings-option-check"><FiCheck size={12} /></div>}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* ── Live Preview ── */}
        <div className="card settings-card settings-card-wide settings-preview-card">
          <div className="card-header">
            <div className="card-title"><FiMonitor size={15} /> Live Preview</div>
          </div>
          <div className="card-body">
            <div className="settings-preview">
              <div className="settings-preview-sidebar" style={{ background: activePreset === 'custom' ? customSidebar : currentPreset?.sidebar || '#0F172A' }}>
                <div className="settings-preview-logo" />
                {['Dashboard', 'Products', 'Suppliers'].map((item, i) => (
                  <div key={item} className="settings-preview-nav-item" style={{
                    background: i === 1 ? (activePrimary + '22') : 'transparent',
                    color: i === 1 ? activePrimary : 'rgba(255,255,255,0.4)',
                    borderRadius: radius === 'sharp' ? 4 : radius === 'pill' ? 12 : 8,
                  }}>
                    <div className="settings-preview-nav-dot" style={{ background: i === 1 ? activePrimary : 'rgba(255,255,255,0.3)' }} />
                    <span>{item}</span>
                  </div>
                ))}
              </div>
              <div className="settings-preview-main">
                <div className="settings-preview-topbar">
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
                    <div className="settings-preview-bar" style={{ width: 80 }} />
                    <div className="settings-preview-bar" style={{ width: 50, opacity: 0.5 }} />
                  </div>
                  <div style={{ display: 'flex', gap: 6 }}>
                    <div className="settings-preview-btn-outline" style={{ borderRadius: radius === 'sharp' ? 4 : radius === 'pill' ? 12 : 8 }} />
                    <div className="settings-preview-btn-outline" style={{ borderRadius: radius === 'sharp' ? 4 : radius === 'pill' ? 12 : 8 }} />
                  </div>
                </div>
                <div style={{ padding: '10px 12px', display: 'flex', flexDirection: 'column', gap: 8 }}>
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 6 }}>
                    {[1,2,3].map(i => (
                      <div key={i} className="settings-preview-stat" style={{ borderRadius: radius === 'sharp' ? 4 : radius === 'pill' ? 10 : 8 }}>
                        <div className="settings-preview-icon" style={{ background: activePrimary + '22' }}>
                          <div style={{ width: 10, height: 10, borderRadius: '50%', background: activePrimary }} />
                        </div>
                        <div>
                          <div className="settings-preview-bar" style={{ width: 40, marginBottom: 3 }} />
                          <div className="settings-preview-bar" style={{ width: 24, height: 8, opacity: 0.7 }} />
                        </div>
                      </div>
                    ))}
                  </div>
                  <div className="settings-preview-stat" style={{ borderRadius: radius === 'sharp' ? 4 : radius === 'pill' ? 10 : 8 }}>
                    <div style={{ flex: 1 }}>
                      <div className="settings-preview-bar" style={{ width: '60%', marginBottom: 6 }} />
                      <div style={{ display: 'grid', gap: 3 }}>
                        {[1,2,3].map(i => <div key={i} className="settings-preview-bar" style={{ width: '100%', height: 8, opacity: 0.5 }} />)}
                      </div>
                    </div>
                    <div style={{ alignSelf: 'flex-start' }}>
                      <div className="settings-preview-cta" style={{ background: activePrimary, borderRadius: radius === 'sharp' ? 4 : radius === 'pill' ? 12 : 8 }}>
                        Save
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Settings;
