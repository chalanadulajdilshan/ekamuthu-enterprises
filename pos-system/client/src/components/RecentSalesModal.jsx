import { useState, useEffect } from 'react';
import { FiX, FiClock } from 'react-icons/fi';
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
    <div className="modal-overlay animate-fade-in" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="modal modal-lg glass shadow-xl">
        <div className="modal-header premium-gradient" style={{ border: 'none', color: 'white' }}>
          <div className="modal-title" style={{ fontSize: '18px' }}>
            <FiClock />
            Recent Sales Transactions
          </div>
          <button className="modal-close" onClick={onClose} style={{ color: 'rgba(255,255,255,0.8)' }}>
            <FiX />
          </button>
        </div>

        <div className="modal-body" style={{ padding: 0, maxHeight: '70vh', overflowY: 'auto' }}>
          {loading ? (
            <div className="loading-container">
              <div className="spinner" />
              <span>Loading ledger data...</span>
            </div>
          ) : sales.length === 0 ? (
            <div className="empty-state" style={{ padding: '60px' }}>
              <FiClock className="empty-state-icon" />
              <div className="empty-state-title">No transactions recorded yet</div>
              <p className="empty-state-sub">New sales will appear here in real-time.</p>
            </div>
          ) : (
            <table className="table">
              <thead style={{ position: 'sticky', top: 0, zIndex: 10 }}>
                <tr>
                  <th style={{ background: 'var(--bg-hover)', paddingLeft: '24px' }}>Invoice Ref</th>
                  <th style={{ background: 'var(--bg-hover)' }}>Processing Date</th>
                  <th style={{ background: 'var(--bg-hover)' }}>Customer Entity</th>
                  <th style={{ background: 'var(--bg-hover)', textAlign: 'right', paddingRight: '24px' }}>Grand Total</th>
                </tr>
              </thead>
              <tbody>
                {sales.map(sale => (
                  <tr key={sale.id}>
                    <td style={{ paddingLeft: '24px' }}>
                      <span className="badge badge-primary" style={{ fontWeight: 800 }}>{sale.invoice_no}</span>
                    </td>
                    <td style={{ color: 'var(--text-muted)', fontWeight: 500, fontSize: '13px' }}>
                      {new Date(sale.invoice_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}
                    </td>
                    <td style={{ fontWeight: 600 }}>{sale.customer_name || 'Counter Sale / Walk-in'}</td>
                    <td style={{ textAlign: 'right', fontWeight: 800, color: 'var(--success)', paddingRight: '24px', fontSize: '15px' }}>
                      Rs.&nbsp;{parseFloat(sale.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="modal-footer glass" style={{ borderTop: '1px solid var(--border)' }}>
          <button className="btn btn-secondary shadow-sm" style={{ marginLeft: 'auto' }} onClick={onClose}>
            Close Explorer
          </button>
        </div>
      </div>
    </div>
  );
};

export default RecentSalesModal;
