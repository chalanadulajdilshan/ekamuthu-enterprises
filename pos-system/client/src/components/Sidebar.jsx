import { FiHome, FiPackage, FiTruck, FiTag, FiShoppingBag, FiExternalLink, FiSettings, FiShoppingCart, FiBox } from 'react-icons/fi';

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: FiHome },
  { id: 'invoice', label: 'Sales Invoice', icon: FiShoppingCart },
  { id: 'itemMaster', label: 'Products', icon: FiPackage },
  { id: 'supplierMaster', label: 'Suppliers', icon: FiTruck },
  { id: 'brandMaster', label: 'Brands', icon: FiTag },
  { id: 'liveStock', label: 'Live Stock', icon: FiBox },
  { id: 'grn', label: 'GRN (Receipt)', icon: FiPackage },
];

const Sidebar = ({ currentView, onNavigate, companyData }) => {
  const logoUrl = companyData?.image_name && companyData.image_name.trim() !== ''
    ? `http://localhost/ekamuthu-enterprises/uploads/company-logos/${companyData.image_name}`
    : null;

  // Split name for nicer layout
  const nameParts = companyData?.name?.split(' ') || ['Ekamuthu', 'Enterprises'];
  const nameFirst = nameParts[0];
  const nameRest  = nameParts.slice(1).join(' ');

  return (
    <div className="sidebar">
      <div className="sidebar-logo">
        {logoUrl ? (
          <img src={logoUrl} alt="Logo" className="sidebar-logo-img" />
        ) : (
          <>
            <div className="sidebar-logo-icon">
              <FiShoppingBag />
            </div>
            <div>
              <div className="sidebar-logo-text">{nameFirst}</div>
              <div className="sidebar-logo-sub">{nameRest}</div>
            </div>
          </>
        )}
      </div>

      <nav className="sidebar-nav">
        <div className="sidebar-section-label">Main Menu</div>
        {navItems.map(item => (
          <button
            key={item.id}
            className={`sidebar-nav-item ${currentView === item.id ? 'active' : ''}`}
            onClick={() => onNavigate(item.id)}
          >
            <item.icon />
            <span>{item.label}</span>
          </button>
        ))}
      </nav>

      <div className="sidebar-footer">
        <button
          className={`sidebar-nav-item ${currentView === 'settings' ? 'active' : ''}`}
          onClick={() => onNavigate('settings')}
        >
          <FiSettings />
          <span>Settings</span>
        </button>
        <a href="/" className="sidebar-nav-item" style={{ textDecoration: 'none' }}>
          <FiExternalLink />
          <span>Main System</span>
        </a>
      </div>
    </div>
  );
};

export default Sidebar;
