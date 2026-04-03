import { useEffect, useState } from 'react';
import { FiPackage, FiTruck, FiTag, FiActivity, FiTrendingUp, FiAlertCircle, FiArrowRight } from 'react-icons/fi';
import { getDashboardStats, getRecentSales } from '../services/api';
import RecentSalesModal from './RecentSalesModal';

const Dashboard = ({ onNavigate }) => {
  const [stats, setStats] = useState(null);
  const [recentSales, setRecentSales] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showRecentModal, setShowRecentModal] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [statsRes, salesRes] = await Promise.all([
          getDashboardStats(),
          getRecentSales(),
        ]);
        setStats(statsRes.data.data);
        setRecentSales(salesRes.data.data.slice(0, 5));
      } catch (err) {
        console.error('Dashboard fetch error:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
    const interval = setInterval(fetchData, 60000);
    return () => clearInterval(interval);
  }, []);

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner" />
        <span>Loading dashboard...</span>
      </div>
    );
  }

  return (
    <div className="page">
      {/* Page Header */}
      <div className="page-header">
        <h1 className="page-title">Welcome back</h1>
        <p className="page-subtitle">Here's what's happening with your business today.</p>
      </div>

      {/* Stats */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-card-top">
            <span className="stat-card-label">Transactions Today</span>
            <div className="stat-card-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
              <FiActivity />
            </div>
          </div>
          <div className="stat-card-value">{stats?.today_sales_count || 0}</div>
          <div className="stat-card-sub">Sales invoices processed</div>
        </div>

        <div className="stat-card">
          <div className="stat-card-top">
            <span className="stat-card-label">Revenue Today</span>
            <div className="stat-card-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
              <FiTrendingUp />
            </div>
          </div>
          <div className="stat-card-value" style={{ fontSize: '20px' }}>
            Rs.&nbsp;{parseFloat(stats?.today_sales_total || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
          </div>
          <div className="stat-card-sub">Total collected</div>
        </div>

        <div className="stat-card">
          <div className="stat-card-top">
            <span className="stat-card-label">Active Products</span>
            <div className="stat-card-icon" style={{ background: 'var(--info-light)', color: 'var(--info)' }}>
              <FiPackage />
            </div>
          </div>
          <div className="stat-card-value">{stats?.total_products || 0}</div>
          <div className="stat-card-sub">Items in catalog</div>
        </div>

        <div className="stat-card">
          <div className="stat-card-top">
            <span className="stat-card-label">Low Stock Alerts</span>
            <div className="stat-card-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
              <FiAlertCircle />
            </div>
          </div>
          <div
            className="stat-card-value"
            style={{ color: stats?.low_stock_count > 0 ? 'var(--warning)' : undefined }}
          >
            {stats?.low_stock_count || 0}
          </div>
          <div className="stat-card-sub">Items need restocking</div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="quick-actions">
        <button className="quick-action-card" onClick={() => onNavigate('itemMaster')}>
          <div className="quick-action-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
            <FiPackage />
          </div>
          <div>
            <div className="quick-action-label">Manage Products</div>
            <div className="quick-action-sub">Add, edit or view inventory items</div>
          </div>
        </button>

        <button className="quick-action-card" onClick={() => onNavigate('supplierMaster')}>
          <div className="quick-action-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
            <FiTruck />
          </div>
          <div>
            <div className="quick-action-label">Manage Suppliers</div>
            <div className="quick-action-sub">Vendor database and credit terms</div>
          </div>
        </button>

        <button className="quick-action-card" onClick={() => onNavigate('brandMaster')}>
          <div className="quick-action-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
            <FiTag />
          </div>
          <div>
            <div className="quick-action-label">Manage Brands</div>
            <div className="quick-action-sub">Product labels and origins</div>
          </div>
        </button>
      </div>

      {/* Recent Transactions */}
      <div className="card">
        <div className="card-header">
          <div className="card-title">
            <FiActivity />
            Recent Transactions
          </div>
          <button className="btn btn-secondary btn-sm" onClick={() => setShowRecentModal(true)}>
            View All <FiArrowRight />
          </button>
        </div>
        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Invoice No.</th>
                <th>Time</th>
                <th>Customer</th>
                <th style={{ textAlign: 'right' }}>Total</th>
              </tr>
            </thead>
            <tbody>
              {recentSales.length > 0 ? (
                recentSales.map(sale => (
                  <tr key={sale.id}>
                    <td>
                      <span className="badge badge-primary">{sale.invoice_no}</span>
                    </td>
                    <td style={{ color: 'var(--text-muted)' }}>
                      {new Date(sale.invoice_date).toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </td>
                    <td>{sale.customer_name || 'Walk-in Customer'}</td>
                    <td style={{ textAlign: 'right', fontWeight: 700, color: 'var(--success)' }}>
                      Rs.&nbsp;{parseFloat(sale.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="4">
                    <div className="empty-state" style={{ padding: '28px' }}>
                      <div className="empty-state-title">No transactions today</div>
                    </div>
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
