import { useState, useEffect } from 'react';
import { FiSun, FiMoon } from 'react-icons/fi';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import ItemMaster from './components/ItemMaster';
import SupplierMaster from './components/SupplierMaster';
import BrandMaster from './components/BrandMaster';
import ReceiptModal from './components/ReceiptModal';
import Settings from './components/Settings';

const PAGE_META = {
  dashboard:      { name: 'Dashboard',       breadcrumb: 'Overview' },
  itemMaster:     { name: 'Item Master',      breadcrumb: 'Products → Item Master' },
  supplierMaster: { name: 'Supplier Master',  breadcrumb: 'Masters → Suppliers' },
  brandMaster:    { name: 'Brand Master',     breadcrumb: 'Masters → Brands' },
  settings:       { name: 'Settings',         breadcrumb: 'System → Settings' },
};

// Utility: adjust hex color brightness
function adjustBrightness(hex, amount) {
  const num = parseInt(hex.replace('#', ''), 16);
  const r = Math.min(255, Math.max(0, (num >> 16) + amount));
  const g = Math.min(255, Math.max(0, ((num >> 8) & 0xff) + amount));
  const b = Math.min(255, Math.max(0, (num & 0xff) + amount));
  return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('');
}

function applyStoredSettings() {
  const preset = localStorage.getItem('pos_color_preset');
  const customPrimary = localStorage.getItem('pos_custom_primary') || '#4F46E5';
  const customSidebar = localStorage.getItem('pos_custom_sidebar') || '#0F172A';
  const fontSize = localStorage.getItem('pos_font_size') || 'md';
  const radius = localStorage.getItem('pos_radius') || 'rounded';

  const PRESETS = {
    indigo:  { primary: '#4F46E5', sidebar: '#0F172A' },
    blue:    { primary: '#0EA5E9', sidebar: '#0C1A2E' },
    emerald: { primary: '#10B981', sidebar: '#0A1F18' },
    violet:  { primary: '#8B5CF6', sidebar: '#120E22' },
    rose:    { primary: '#F43F5E', sidebar: '#1E0B10' },
    amber:   { primary: '#F59E0B', sidebar: '#1A1205' },
    cyan:    { primary: '#06B6D4', sidebar: '#08161A' },
    teal:    { primary: '#14B8A6', sidebar: '#091817' },
  };

  const root = document.documentElement;
  const colors = preset && preset !== 'custom' && PRESETS[preset] ? PRESETS[preset] : { primary: customPrimary, sidebar: customSidebar };

  root.style.setProperty('--primary', colors.primary);
  root.style.setProperty('--primary-hover', adjustBrightness(colors.primary, -20));
  root.style.setProperty('--primary-light', colors.primary + '22');
  root.style.setProperty('--primary-glow', `0 4px 14px ${colors.primary}44`);
  root.style.setProperty('--sidebar-active-text', colors.primary);
  root.style.setProperty('--sidebar-active-border', colors.primary);
  root.style.setProperty('--sidebar-active-bg', colors.primary + '22');
  root.style.setProperty('--border-focus', colors.primary);
  root.style.setProperty('--sidebar-bg', colors.sidebar);

  const fontSizeMap = { sm: '13px', md: '14px', lg: '15px' };
  root.style.setProperty('font-size', fontSizeMap[fontSize] || '14px');

  const radiusMap = { sharp: '4px', rounded: '8px', pill: '12px' };
  const radiusLgMap = { sharp: '6px', rounded: '12px', pill: '16px' };
  root.style.setProperty('--radius', radiusMap[radius] || '8px');
  root.style.setProperty('--radius-lg', radiusLgMap[radius] || '12px');
}

function App() {
  const [currentView, setCurrentView] = useState(() => {
    return localStorage.getItem('pos_current_view') || 'dashboard';
  });
  const [theme, setTheme] = useState(() => {
    return localStorage.getItem('pos_theme') || 'light';
  });
  const [receiptData, setReceiptData] = useState(null);
  const [companyData, setCompanyData] = useState(null);

  // Apply stored settings on first load
  useEffect(() => {
    applyStoredSettings();
  }, []);

  useEffect(() => {
    fetch('http://localhost:3001/api/company')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setCompanyData(data.data);
        }
      })
      .catch(err => console.error('Error fetching company data:', err));
  }, []);

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('pos_theme', theme);
  }, [theme]);

  useEffect(() => {
    localStorage.setItem('pos_current_view', currentView);
  }, [currentView]);

  const handleThemeChange = (newTheme) => {
    setTheme(newTheme);
  };

  const meta = PAGE_META[currentView] || PAGE_META.dashboard;

  return (
    <div className="app-layout">
      <Sidebar currentView={currentView} onNavigate={setCurrentView} companyData={companyData} />

      <div className="app-main">
        {/* Top Bar */}
        <div className="topbar">
          <div className="topbar-title">
            <div className="topbar-page-name">{meta.name}</div>
            <div className="topbar-breadcrumb">{meta.breadcrumb}</div>
          </div>
          <div className="topbar-actions">
            <button
              className="topbar-btn"
              onClick={() => handleThemeChange(theme === 'light' ? 'dark' : 'light')}
              title="Toggle theme"
            >
              {theme === 'light' ? <FiMoon size={16} /> : <FiSun size={16} />}
            </button>
          </div>
        </div>

        {/* Page Content */}
        <div className="app-content">
          {currentView === 'dashboard'      && <Dashboard onNavigate={setCurrentView} />}
          {currentView === 'itemMaster'     && <ItemMaster />}
          {currentView === 'supplierMaster' && <SupplierMaster />}
          {currentView === 'brandMaster'    && <BrandMaster />}
          {currentView === 'settings'       && <Settings theme={theme} onThemeChange={handleThemeChange} />}
        </div>
      </div>

      {receiptData && (
        <ReceiptModal data={receiptData} onClose={() => setReceiptData(null)} />
      )}
    </div>
  );
}

export default App;
