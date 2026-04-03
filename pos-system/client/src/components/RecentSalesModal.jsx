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
    <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="modal modal-lg">
        <div className="modal-header">
          <div className="modal-title">
            <FiClock />
            Recent Sales
          </div>
          <button className="modal-close" onClick={onClose}>
            <FiX />
          </button>
        </div>

        <div className="modal-body" style={{ padding: 0, maxHeight: '60vh', overflowY: 'auto' }}>
          {loading ? (
            <div className="loading-container">
              <div className="spinner" />
              <span>Loading...</span>
            </div>
          ) : sales.length === 0 ? (
            <div className="empty-state">
              <div className="empty-state-title">No recent sales</div>
            </div>
          ) : (
            <table className="table">
              <thead>
                <tr>
                  <th>Invoice #</th>
                  <th>Date</th>
                  <th>Customer</th>
                  <th style={{ textAlign: 'right' }}>Amount</th>
                </tr>
              </thead>
              <tbody>
                {sales.map(sale => (
                  <tr key={sale.id}>
                    <td>
                      <span className="badge badge-primary">{sale.invoice_no}</span>
                    </td>
                    <td style={{ color: 'var(--text-muted)', fontSize: 12 }}>
                      {new Date(sale.invoice_date).toLocaleDateString('en-GB')}
                    </td>
                    <td>{sale.customer_name || 'Walk-in'}</td>
                    <td style={{ textAlign: 'right', fontWeight: 700, color: 'var(--success)' }}>
                      Rs.&nbsp;{parseFloat(sale.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="modal-footer">
          <button className="btn btn-secondary" style={{ marginLeft: 'auto' }} onClick={onClose}>
            <FiX /> Close
          </button>
        </div>
      </div>
    </div>
  );
};

export default RecentSalesModal;
