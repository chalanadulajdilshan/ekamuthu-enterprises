import { useEffect, useState } from 'react';
import { FiShoppingCart, FiTrendingUp, FiPackage, FiAlertCircle, FiActivity, FiArrowRight, FiArrowLeft, FiSun, FiMoon } from 'react-icons/fi';
import { getDashboardStats, getRecentSales } from '../services/api';
import RecentSalesModal from './RecentSalesModal';

const Dashboard = ({ onNavigate, theme, toggleTheme }) => {
  const [stats, setStats] = useState(null);
  const [recentSales, setRecentSales] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showRecentModal, setShowRecentModal] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [statsRes, salesRes] = await Promise.all([
          getDashboardStats(),
          getRecentSales()
        ]);
        setStats(statsRes.data.data);
        setRecentSales(salesRes.data.data.slice(0, 5)); // Show only top 5 recent
      } catch (err) {
        console.error('Error fetching dashboard data:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
    
    // Refresh stats every 60 seconds
    const interval = setInterval(fetchData, 60000);
    return () => clearInterval(interval);
  }, []);

  if (loading) {
    return (
      <div className="pos-dashboard">
        <div className="pos-loading">
          <div className="pos-spinner"></div>
          <span>Loading Dashboard...</span>
        </div>
      </div>
    );
  }

  return (
    <div className="pos-dashboard">
      <div className="pos-dashboard-top-nav">
         <div className="pos-logo">
            <div className="pos-logo-icon">
              <FiShoppingCart />
            </div>
            <div className="pos-logo-text">
              POS <span>Dashboard</span>
            </div>
          </div>
          <div style={{display: 'flex', gap: '12px'}}>
              <button className="pos-header-btn" onClick={toggleTheme} title="Toggle Theme">
                {theme === 'light' ? <FiMoon /> : <FiSun />}
              </button>
              <a href="/" className="pos-back-btn" title="Back to Main System">
                  <FiArrowLeft />
                  <span>Exit to Main App</span>
              </a>
          </div>
      </div>

      <div className="pos-dashboard-header">
        <div>
          <h1 className="pos-dashboard-title">Overview</h1>
          <p className="pos-dashboard-subtitle">Welcome to the POS System. Here's what's happening today.</p>
        </div>
        <div style={{display: 'flex', gap: '12px'}}>
          <button className="pos-checkout-btn" style={{ width: 'auto', padding: '12px 24px', background: 'var(--bg-card)', color: 'var(--text-primary)', border: '1px solid var(--border-color)', boxShadow: 'none' }} onClick={() => onNavigate('itemMaster')}>
            <FiPackage /> Manage Products
          </button>
          <button className="pos-checkout-btn" style={{ width: 'auto', padding: '12px 24px' }} onClick={() => onNavigate('pos')}>
            <FiShoppingCart /> Open POS Terminal
          </button>
        </div>
      </div>

      <div className="pos-dashboard-stats-grid">
        <div className="pos-dashboard-stat-card">
          <div className="pos-dashboard-stat-icon" style={{ background: 'rgba(99, 102, 241, 0.1)', color: 'var(--accent-primary)' }}>
            <FiActivity />
          </div>
          <div className="pos-dashboard-stat-info">
            <span className="pos-dashboard-stat-value">{stats?.today_sales_count || 0}</span>
            <span className="pos-dashboard-stat-label">Transactions Today</span>
          </div>
        </div>
        <div className="pos-dashboard-stat-card">
          <div className="pos-dashboard-stat-icon" style={{ background: 'rgba(34, 197, 94, 0.1)', color: 'var(--accent-success)' }}>
            <FiTrendingUp />
          </div>
          <div className="pos-dashboard-stat-info">
            <span className="pos-dashboard-stat-value">Rs. {parseFloat(stats?.today_sales_total || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
            <span className="pos-dashboard-stat-label">Revenue Today</span>
          </div>
        </div>
        <div className="pos-dashboard-stat-card">
          <div className="pos-dashboard-stat-icon" style={{ background: 'rgba(6, 182, 212, 0.1)', color: 'var(--accent-info)' }}>
            <FiPackage />
          </div>
          <div className="pos-dashboard-stat-info">
            <span className="pos-dashboard-stat-value">{stats?.total_products || 0}</span>
            <span className="pos-dashboard-stat-label">Active Products</span>
          </div>
        </div>
        <div className="pos-dashboard-stat-card">
          <div className="pos-dashboard-stat-icon" style={{ background: 'rgba(245, 158, 11, 0.1)', color: 'var(--accent-warning)' }}>
            <FiAlertCircle />
          </div>
          <div className="pos-dashboard-stat-info">
            <span className="pos-dashboard-stat-value">{stats?.low_stock_count || 0}</span>
            <span className="pos-dashboard-stat-label">Low Stock Alerts</span>
          </div>
        </div>
      </div>

      <div className="pos-dashboard-recent">
        <div className="pos-dashboard-recent-header">
          <h3>Recent Transactions</h3>
          <button className="pos-category-btn" onClick={() => setShowRecentModal(true)}>
            View All <FiArrowRight />
          </button>
        </div>
        <div className="pos-dashboard-recent-list">
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid var(--border-color)', textAlign: 'left' }}>
                <th style={{ padding: '12px 16px', color: 'var(--text-muted)', fontSize: '13px' }}>Invoice ID</th>
                <th style={{ padding: '12px 16px', color: 'var(--text-muted)', fontSize: '13px' }}>Time</th>
                <th style={{ padding: '12px 16px', color: 'var(--text-muted)', fontSize: '13px' }}>Customer</th>
                <th style={{ padding: '12px 16px', color: 'var(--text-muted)', fontSize: '13px', textAlign: 'right' }}>Total Amount</th>
              </tr>
            </thead>
            <tbody>
              {recentSales.map(sale => (
                <tr key={sale.id} style={{ borderBottom: '1px solid var(--border-color)' }}>
                  <td style={{ padding: '12px 16px', fontWeight: 600, color: 'var(--accent-primary)', fontSize: '14px' }}>
                    {sale.invoice_no}
                  </td>
                  <td style={{ padding: '12px 16px', color: 'var(--text-secondary)', fontSize: '13px' }}>
                    {new Date(sale.invoice_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                  </td>
                  <td style={{ padding: '12px 16px', fontSize: '14px' }}>
                    {sale.customer_name || 'Walk-in Customer'}
                  </td>
                  <td style={{ padding: '12px 16px', textAlign: 'right', fontWeight: 700, color: 'var(--accent-success)', fontSize: '14px' }}>
                    Rs. {parseFloat(sale.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                  </td>
                </tr>
              ))}
              {recentSales.length === 0 && (
                <tr>
                  <td colSpan="4" style={{ padding: '30px', textAlign: 'center', color: 'var(--text-muted)' }}>
                    No recent transactions today.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {showRecentModal && <RecentSalesModal onClose={() => setShowRecentModal(false)} />}
    </div>
  );
};

export default Dashboard;
