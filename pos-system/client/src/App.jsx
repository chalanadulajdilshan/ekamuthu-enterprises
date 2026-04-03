import { useState, useEffect } from 'react';
import { FiSun, FiMoon } from 'react-icons/fi';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import ItemMaster from './components/ItemMaster';
import SupplierMaster from './components/SupplierMaster';
import BrandMaster from './components/BrandMaster';
import ReceiptModal from './components/ReceiptModal';

const PAGE_META = {
  dashboard:      { name: 'Dashboard',       breadcrumb: 'Overview' },
  itemMaster:     { name: 'Item Master',      breadcrumb: 'Products → Item Master' },
  supplierMaster: { name: 'Supplier Master',  breadcrumb: 'Masters → Suppliers' },
  brandMaster:    { name: 'Brand Master',     breadcrumb: 'Masters → Brands' },
};

function App() {
  const [currentView, setCurrentView] = useState(() => {
    return localStorage.getItem('pos_current_view') || 'dashboard';
  });
  const [theme, setTheme] = useState(() => {
    return localStorage.getItem('pos_theme') || 'light';
  });
  const [receiptData, setReceiptData] = useState(null);

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('pos_theme', theme);
  }, [theme]);

  useEffect(() => {
    localStorage.setItem('pos_current_view', currentView);
  }, [currentView]);

  const toggleTheme = () => setTheme(prev => prev === 'light' ? 'dark' : 'light');
  const meta = PAGE_META[currentView] || PAGE_META.dashboard;

  return (
    <div className="app-layout">
      <Sidebar currentView={currentView} onNavigate={setCurrentView} />

      <div className="app-main">
        {/* Top Bar */}
        <div className="topbar">
          <div className="topbar-title">
            <div className="topbar-page-name">{meta.name}</div>
            <div className="topbar-breadcrumb">{meta.breadcrumb}</div>
          </div>
          <div className="topbar-actions">
            <button className="topbar-btn" onClick={toggleTheme} title="Toggle theme">
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
        </div>
      </div>

      {receiptData && (
        <ReceiptModal data={receiptData} onClose={() => setReceiptData(null)} />
      )}
    </div>
  );
}

export default App;
