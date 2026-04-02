import { useState, useEffect } from 'react';
import { FiX, FiEye, FiClock } from 'react-icons/fi';
import { getRecentSales } from '../services/api';

const RecentSalesModal = ({ onClose }) => {
  const [sales, setSales] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchSales = async () => {
      try {
        const res = await getRecentSales();
        setSales(res.data.data || []);
      } catch (err) {
        console.error('Error fetching recent sales:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchSales();
  }, []);

  return (
    <div className="pos-modal-overlay" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pos-modal" style={{ maxWidth: 600 }}>
        <div className="pos-modal-header">
          <div className="pos-modal-title">
            <FiClock style={{ marginRight: 8 }} />
            Recent POS Sales
          </div>
          <button className="pos-modal-close" onClick={onClose}>
            <FiX />
          </button>
        </div>

        <div className="pos-modal-body" style={{ maxHeight: '60vh', overflowY: 'auto', padding: 0 }}>
          {loading ? (
            <div className="pos-loading" style={{ padding: 40 }}>
              <div className="pos-spinner"></div>
              <span>Loading...</span>
            </div>
          ) : sales.length === 0 ? (
            <div className="pos-empty" style={{ padding: 40 }}>
              <div className="pos-empty-text">No recent POS sales</div>
            </div>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr style={{ borderBottom: '1px solid var(--border-color)' }}>
                  <th style={thStyle}>Invoice #</th>
                  <th style={thStyle}>Date</th>
                  <th style={thStyle}>Customer</th>
                  <th style={{ ...thStyle, textAlign: 'right' }}>Amount</th>
                </tr>
              </thead>
              <tbody>
                {sales.map(sale => (
                  <tr key={sale.id} style={{ borderBottom: '1px solid var(--border-color)' }}>
                    <td style={tdStyle}>
                      <span style={{ 
                        color: 'var(--accent-primary)', 
                        fontWeight: 600, 
                        fontSize: 13 
                      }}>
                        {sale.invoice_no}
                      </span>
                    </td>
                    <td style={tdStyle}>
                      <span style={{ fontSize: 12, color: 'var(--text-secondary)' }}>
                        {new Date(sale.invoice_date).toLocaleDateString('en-GB')}
                      </span>
                    </td>
                    <td style={tdStyle}>
                      <span style={{ fontSize: 13 }}>
                        {sale.customer_name || 'Walk-in'}
                      </span>
                    </td>
                    <td style={{ ...tdStyle, textAlign: 'right' }}>
                      <span style={{ 
                        fontWeight: 700, 
                        color: 'var(--accent-success)', 
                        fontSize: 13 
                      }}>
                        Rs. {parseFloat(sale.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="pos-modal-footer">
          <button className="pos-modal-btn" onClick={onClose}>
            <FiX /> Close
          </button>
        </div>
      </div>
    </div>
  );
};

const thStyle = {
  padding: '12px 16px',
  textAlign: 'left',
  fontSize: 11,
  fontWeight: 600,
  textTransform: 'uppercase',
  letterSpacing: '0.5px',
  color: 'var(--text-muted)',
  background: 'var(--bg-primary)',
  position: 'sticky',
  top: 0,
};

const tdStyle = {
  padding: '10px 16px',
};

export default RecentSalesModal;
