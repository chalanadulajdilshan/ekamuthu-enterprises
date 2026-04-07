import { useEffect, useState, useMemo } from 'react';
import { FiPackage, FiTruck, FiTag, FiActivity, FiTrendingUp, FiAlertCircle, FiArrowRight, FiShoppingCart, FiPieChart } from 'react-icons/fi';
import { getDashboardStats, getRecentSales } from '../services/api';
import RecentSalesModal from './RecentSalesModal';

// --- Custom Premium SVG Charts (Lightweight & Performance-focused) ---

const AreaChart = ({ data = [], color = '#6366F1' }) => {
  if (!data || data.length === 0) return <div className="empty-chart">No data available</div>;

  const max = Math.max(...data.map(d => d.total), 10);
  const height = 180;
  const width = 450;
  const padding = 20;

  const points = data.map((d, i) => {
    // Fix: Handle single data point case to avoid Infinity (division by zero)
    const x = data.length > 1 
      ? (i / (data.length - 1)) * (width - padding * 2) + padding
      : width / 2;
    const y = height - ((d.total / max) * (height - padding * 2)) - padding;
    return { x, y, val: d.total, date: d.date };
  });

  const path = data.length > 1 
    ? `M ${points[0].x} ${points[0].y} ` + points.slice(1).map(p => `L ${p.x} ${p.y}`).join(' ')
    : points.length > 0 ? `M ${points[0].x - 10} ${points[0].y} L ${points[0].x + 10} ${points[0].y}` : '';
  
  const areaPath = data.length > 1
    ? `${path} L ${points[points.length - 1].x} ${height} L ${points[0].x} ${height} Z`
    : path ? `${path} L ${points[0].x + 10} ${height} L ${points[0].x - 10} ${height} Z` : '';

  return (
    <div className="chart-wrapper">
      <svg viewBox={`0 0 ${width} ${height}`} className="premium-chart-svg">
        <defs>
          <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={color} stopOpacity="0.3" />
            <stop offset="100%" stopColor={color} stopOpacity="0" />
          </linearGradient>
        </defs>
        
        {/* Grid Lines */}
        {[0, 0.25, 0.5, 0.75, 1].map((p, i) => (
          <line 
            key={i} 
            x1={padding} 
            y1={height - (p * (height - padding * 2)) - padding} 
            x2={width - padding} 
            y2={height - (p * (height - padding * 2)) - padding} 
            stroke="var(--border)" 
            strokeDasharray="4 4" 
            strokeWidth="1"
          />
        ))}

        {/* Area */}
        <path d={areaPath} fill="url(#chartGradient)" />
        
        {/* Line */}
        <path d={path} fill="none" stroke={color} strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
        
        {/* Points */}
        {points.map((p, i) => (
          <g key={i} className="chart-point-group">
            <circle cx={p.x} cy={p.y} r="5" fill="white" stroke={color} strokeWidth="2" />
            <text x={p.x} y={height - 5} textAnchor="middle" fontSize="10" fill="var(--text-muted)">
              {new Date(p.date).toLocaleDateString('en-US', { weekday: 'short' })}
            </text>
          </g>
        ))}
      </svg>
    </div>
  );
};

const DonutChart = ({ data = [] }) => {
  if (!data || data.length === 0) return <div className="empty-chart">No data available</div>;

  const total = data.reduce((sum, d) => sum + parseFloat(d.total), 0);
  const colors = ['#6366F1', '#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6'];
  
  let currentAngle = 0;
  const size = 180;
  const center = size / 2;
  const radius = 60;
  const innerRadius = 40;

  const slices = data.map((d, i) => {
    const angle = (parseFloat(d.total) / total) * 360;
    const startAngle = currentAngle;
    currentAngle += angle;
    
    // Convert to radians
    const startRad = (startAngle - 90) * Math.PI / 180;
    const endRad = (currentAngle - 90) * Math.PI / 180;
    
    const x1 = center + radius * Math.cos(startRad);
    const y1 = center + radius * Math.sin(startRad);
    const x2 = center + radius * Math.cos(endRad);
    const y2 = center + radius * Math.sin(endRad);

    const xi1 = center + innerRadius * Math.cos(startRad);
    const yi1 = center + innerRadius * Math.sin(startRad);
    const xi2 = center + innerRadius * Math.cos(endRad);
    const yi2 = center + innerRadius * Math.sin(endRad);

    const largeArcFlag = angle > 180 ? 1 : 0;

    const path = [
      `M ${x1} ${y1}`,
      `A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2}`,
      `L ${xi2} ${yi2}`,
      `A ${innerRadius} ${innerRadius} 0 ${largeArcFlag} 0 ${xi1} ${yi1}`,
      'Z'
    ].join(' ');

    return { path, color: colors[i % colors.length], ...d };
  });

  return (
    <div className="donut-chart-container">
      <svg width={size} height={size}>
        {slices.map((s, i) => (
          <path key={i} d={s.path} fill={s.color} className="chart-slice">
            <title>{`${s.name}: Rs. ${parseFloat(s.total).toLocaleString()}`}</title>
          </path>
        ))}
        <text x={center} y={center} textAnchor="middle" dominantBaseline="middle" className="donut-center-text">
          <tspan x={center} dy="-5" fontSize="10" fill="var(--text-muted)">Total</tspan>
          <tspan x={center} dy="20" fontSize="14" fontWeight="800" fill="var(--text-primary)">
            {total > 1000 ? (total / 1000).toFixed(1) + 'k' : total.toFixed(0)}
          </tspan>
        </text>
      </svg>
      <div className="donut-legend">
        {slices.map((s, i) => (
          <div key={i} className="legend-item">
            <span className="legend-color" style={{ background: s.color }} />
            <span className="legend-label">{s.name}</span>
            <span className="legend-value">{((s.total/total)*100).toFixed(0)}%</span>
          </div>
        ))}
      </div>
    </div>
  );
};

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
    <div className="page animate-fade-in">
      {/* Page Header */}
      <div className="page-header" style={{ marginBottom: 40 }}>
        <h1 className="page-title" style={{ fontSize: 32, marginBottom: 8 }}>Welcome back</h1>
        <p className="page-subtitle" style={{ fontSize: 16 }}>Here's an overview of your business performance today.</p>
      </div>

      {/* Stats */}
      <div className="stats-grid">
        <div className="stat-card glass shadow-lg">
          <div className="stat-card-top">
            <span className="stat-card-label">Transactions Today</span>
            <div className="stat-card-icon premium-gradient">
              <FiActivity />
            </div>
          </div>
          <div className="stat-card-value">{stats?.today_sales_count || 0}</div>
          <div className="stat-card-sub">Invoices completed today</div>
        </div>

        <div className="stat-card glass shadow-lg">
          <div className="stat-card-top">
            <span className="stat-card-label">Total Revenue</span>
            <div className="stat-card-icon" style={{ background: 'linear-gradient(135deg, #10B981 0%, #059669 100%)', color: 'white' }}>
              <FiTrendingUp />
            </div>
          </div>
          <div className="stat-card-value" style={{ color: 'var(--success)' }}>
            Rs.&nbsp;{parseFloat(stats?.today_sales_total || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
          </div>
          <div className="stat-card-sub">Net revenue today</div>
        </div>

        <div className="stat-card glass shadow-lg">
          <div className="stat-card-top">
            <span className="stat-card-label">Overall Catalog</span>
            <div className="stat-card-icon" style={{ background: 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)', color: 'white' }}>
              <FiPackage />
            </div>
          </div>
          <div className="stat-card-value">{stats?.total_products || 0}</div>
          <div className="stat-card-sub">Active items in stock</div>
        </div>

        <div className="stat-card glass shadow-lg">
          <div className="stat-card-top">
            <span className="stat-card-label">Inventory Alerts</span>
            <div className="stat-card-icon" style={{ background: 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)', color: 'white' }}>
              <FiAlertCircle />
            </div>
          </div>
          <div
            className="stat-card-value"
            style={{ color: stats?.low_stock_count > 0 ? 'var(--warning)' : undefined }}
          >
            {stats?.low_stock_count || 0}
          </div>
          <div className="stat-card-sub">Critical low stock items</div>
        </div>
      </div>

      {/* Analytics Charts Section */}
      <div className="dashboard-charts-row">
        <div className="card shadow-xl chart-card">
          <div className="card-header">
            <div className="card-title">
              <FiTrendingUp /> Sales Trend (Last 7 Days)
            </div>
          </div>
          <div className="card-body">
            <AreaChart data={stats?.daily_sales} />
          </div>
        </div>

        <div className="card shadow-xl chart-card">
          <div className="card-header">
            <div className="card-title">
              <FiPieChart /> Sales by Department
            </div>
          </div>
          <div className="card-body">
            <DonutChart data={stats?.department_sales} />
          </div>
        </div>
      </div>

      <div className="card-section-title" style={{ marginTop: 20 }}>Quick Operations</div>
      {/* Quick Actions */}
      <div className="quick-actions" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))' }}>
        <button className="quick-action-card glass" onClick={() => onNavigate('invoice_new')}>
          <div className="quick-action-icon premium-gradient">
            <FiShoppingCart />
          </div>
          <div>
            <div className="quick-action-label">New Sale Invoice</div>
            <div className="quick-action-sub">Instantly create a new POS sale</div>
          </div>
        </button>

        <button className="quick-action-card glass" onClick={() => onNavigate('grn_new')}>
          <div className="quick-action-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
            <FiTruck />
          </div>
          <div>
            <div className="quick-action-label">New Purchase (GRN)</div>
            <div className="quick-action-sub">Add stock from new arrivals</div>
          </div>
        </button>

        <button className="quick-action-card glass" onClick={() => onNavigate('itemMaster')}>
          <div className="quick-action-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
            <FiPackage />
          </div>
          <div>
            <div className="quick-action-label">Inventory Master</div>
            <div className="quick-action-sub">Update product pricing & data</div>
          </div>
        </button>
      </div>

      {/* Recent Transactions */}
      <div className="card shadow-xl" style={{ marginTop: 32 }}>
        <div className="card-header">
          <div className="card-title">
            <FiActivity />
            Recent Sales Activity
          </div>
          <button className="btn btn-secondary btn-sm" onClick={() => setShowRecentModal(true)}>
            Explorer All Transactions <FiArrowRight />
          </button>
        </div>
        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Invoice Ref</th>
                <th>Processing Time</th>
                <th>Customer Entity</th>
                <th style={{ textAlign: 'right' }}>Grand Total</th>
              </tr>
            </thead>
            <tbody>
              {recentSales.length > 0 ? (
                recentSales.map(sale => (
                  <tr key={sale.id}>
                    <td>
                      <span className="badge badge-primary">{sale.invoice_no}</span>
                    </td>
                    <td style={{ color: 'var(--text-muted)', fontWeight: 500 }}>
                      {new Date(sale.invoice_date).toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </td>
                    <td style={{ fontWeight: 600 }}>{sale.customer_name || 'Counter Sale / Walk-in'}</td>
                    <td style={{ textAlign: 'right', fontWeight: 800, color: 'var(--success)', fontSize: 16 }}>
                      Rs.&nbsp;{parseFloat(sale.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="4">
                    <div className="empty-state" style={{ padding: '60px' }}>
                      <div className="empty-state-title">No transactions recorded today yet</div>
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
