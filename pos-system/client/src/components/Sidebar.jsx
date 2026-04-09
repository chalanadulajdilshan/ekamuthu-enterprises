import { FiHome, FiPackage, FiTruck, FiTag, FiShoppingBag, FiExternalLink, FiSettings, FiShoppingCart, FiBox, FiTrendingUp } from 'react-icons/fi';

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: FiHome },
  { id: 'invoice', label: 'Point of Sale', icon: FiShoppingCart },
  { id: 'itemMaster', label: 'Inventory Master', icon: FiPackage },
  { id: 'supplierMaster', label: 'Supplier Hub', icon: FiTruck },
  { id: 'brandMaster', label: 'Brand Registry', icon: FiTag },
  { id: 'liveStock', label: 'Live Inventory', icon: FiBox },
  { id: 'grn', label: 'GRN', icon: FiPackage },
  { id: 'salesReport', label: 'Sales Performance', icon: FiTrendingUp },
];

const Sidebar = ({ currentView, onNavigate, companyData }) => {
  // Using window.location.protocol and hostname instead of origin to omit the port (e.g. 5173)
  // This ensures the URL points to the XAMPP web root (port 80)
  const logoUrl = companyData?.image_name && companyData.image_name.trim() !== ''
    ? `${window.location.protocol}//${window.location.hostname}/ekamuthu-enterprises/uploads/company-logos/${companyData.image_name}`
    : null;

  // Split name for nicer layout
  const nameParts = companyData?.name?.split(' ') || ['Ekamuthu', 'Enterprises'];
  const nameFirst = nameParts[0];
  const nameRest = nameParts.slice(1).join(' ');

  return (
    <div className="sidebar shadow-2xl">
      <div className="sidebar-logo">
        {logoUrl ? (
          <img src={logoUrl} alt="Logo" className="sidebar-logo-img" style={{ borderRadius: 12, boxShadow: '0 8px 16px rgba(0,0,0,0.2)' }} />
        ) : (
          <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
            <div className="sidebar-logo-icon premium-gradient">
              <FiShoppingBag />
            </div>
            <div style={{ lineHeight: 1.2 }}>
              <div className="sidebar-logo-text" style={{ fontSize: 18, fontWeight: 900 }}>{nameFirst}</div>
              <div className="sidebar-logo-sub" style={{ fontSize: 11, fontWeight: 700, opacity: 0.8, letterSpacing: 0.5 }}>{nameRest}</div>
            </div>
          </div>
        )}
      </div>

      <nav className="sidebar-nav">
        <div className="sidebar-section-label" style={{ marginTop: 10 }}>Enterprise Navigation</div>
        {navItems.map(item => {
          const isActive = currentView === item.id || (item.id === 'invoice' && currentView === 'invoice_new') || (item.id === 'grn' && currentView === 'grn_new');
          return (
            <button
              key={item.id}
              className={`sidebar-nav-item ${isActive ? 'active' : ''}`}
              onClick={() => onNavigate(item.id)}
              style={{ margin: '4px 0' }}
            >
              <item.icon />
              <span>{item.label}</span>
            </button>
          );
        })}
      </nav>

      <div className="sidebar-footer">
        <div className="sidebar-section-label">System</div>
        <button
          className={`sidebar-nav-item ${currentView === 'settings' ? 'active' : ''}`}
          onClick={() => onNavigate('settings')}
        >
          <FiSettings />
          <span>Control Panel</span>
        </button>
        <a href="/ekamuthu-enterprises/" className="sidebar-nav-item" style={{ textDecoration: 'none' }}>
          <FiExternalLink />
          <span>Return to Base</span>
        </a>
      </div>
    </div>
  );
};

export default Sidebar;
