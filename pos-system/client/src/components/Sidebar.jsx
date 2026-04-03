import { FiHome, FiPackage, FiTruck, FiTag, FiShoppingBag, FiExternalLink } from 'react-icons/fi';

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: FiHome },
  { id: 'itemMaster', label: 'Products', icon: FiPackage },
  { id: 'supplierMaster', label: 'Suppliers', icon: FiTruck },
  { id: 'brandMaster', label: 'Brands', icon: FiTag },
];

const Sidebar = ({ currentView, onNavigate }) => {
  return (
    <div className="sidebar">
      <div className="sidebar-logo">
        <div className="sidebar-logo-icon">
          <FiShoppingBag />
        </div>
        <div>
          <div className="sidebar-logo-text">Ekamuthu</div>
          <div className="sidebar-logo-sub">Enterprises</div>
        </div>
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
        <a href="/" className="sidebar-nav-item" style={{ textDecoration: 'none' }}>
          <FiExternalLink />
          <span>Main System</span>
        </a>
      </div>
    </div>
  );
};

export default Sidebar;
