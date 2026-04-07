import React, { useState, useEffect } from 'react';
import { FiFileText, FiPlus, FiEye, FiSearch } from 'react-icons/fi';
import { getRecentSales, getSaleDetails } from '../services/api';

const InvoiceList = ({ onNavigate }) => {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  
  // Modal State
  const [viewModalData, setViewModalData] = useState(null);
  const [loadingDetails, setLoadingDetails] = useState(false);

  useEffect(() => {
    fetchInvoices();
  }, []);

  const fetchInvoices = async () => {
    try {
      setLoading(true);
      const res = await getRecentSales();
      setInvoices(res.data?.data || (Array.isArray(res.data) ? res.data : []));
    } catch (err) {
      setError('Failed to fetch invoices: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const handleView = async (id) => {
    try {
      setLoadingDetails(true);
      const res = await getSaleDetails(id);
      setViewModalData(res.data?.data);
    } catch (err) {
      alert('Failed to load invoice details');
    } finally {
      setLoadingDetails(false);
    }
  };

  const filteredInvoices = invoices.filter(inv => 
    (inv.invoice_no || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
    (inv.customer_name || '').toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="page animate-fade-in">
      <div className="page-header-row animate-fade-in" style={{ alignItems: 'center', marginBottom: '32px' }}>
        <div>
          <h1 className="page-title" style={{ fontSize: 28 }}>Sales Invoices</h1>
          <p className="page-subtitle">View and manage all POS sale transactions</p>
        </div>
        <div className="page-actions">
          <button className="btn btn-primary" onClick={() => onNavigate('invoice_new')}>
            <FiPlus style={{ marginRight: '8px' }} /> Create New Invoice
          </button>
        </div>
      </div>

      <div className="card shadow-md animate-fade-in">
        <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div className="card-title"><FiFileText style={{ marginRight: '8px' }} /> Recent Sales Transactions</div>
          <div className="search-bar" style={{ width: '350px' }}>
            <FiSearch className="search-icon" />
            <input 
              type="text" 
              className="search-input" 
              placeholder="Search by Invoice No or Customer..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
        </div>
        
        <div className="table-responsive">
          <table className="table">
            <thead>
              <tr>
                <th style={{ paddingLeft: 24 }}>Invoice ID</th>
                <th>Transaction Date</th>
                <th>Customer Entity</th>
                <th>Payment</th>
                <th className="text-right">Grand Total</th>
                <th className="text-center" style={{ paddingRight: 24 }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan="6" className="text-center py-4">Loading sales ledger...</td></tr>
              ) : error ? (
                <tr><td colSpan="6" className="text-center py-4 text-danger">{error}</td></tr>
              ) : filteredInvoices.length === 0 ? (
                <tr><td colSpan="6" className="text-center py-4 text-secondary">No invoices found matching your criteria.</td></tr>
              ) : (
                filteredInvoices.map(inv => (
                  <tr key={inv.id}>
                    <td style={{ paddingLeft: 24 }}><span className="badge badge-primary">{inv.invoice_no}</span></td>
                    <td style={{ fontWeight: 500 }}>{new Date(inv.invoice_date).toLocaleDateString()}</td>
                    <td style={{ fontWeight: 600 }}>{inv.customer_name || 'Counter Sale / Walk-in'}</td>
                    <td>
                      <span className={`badge ${inv.payment_type == 2 ? 'badge-warning' : 'badge-success'}`}>
                        {inv.payment_type == 2 ? 'Credit' : 'Cash'}
                      </span>
                    </td>
                    <td className="text-right font-mono fw-bold" style={{ color: 'var(--success)', fontSize: 15 }}>
                      Rs. {parseFloat(inv.grand_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                    <td className="text-center" style={{ paddingRight: 24 }}>
                      <button className="btn btn-secondary btn-icon" onClick={() => handleView(inv.id)} title="View Detailed Receipt">
                        <FiEye />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {loadingDetails && (
        <div className="modal-overlay">
           <div className="modal-container" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '200px' }}>
              <div className="text-primary fw-bold">Loading Details...</div>
           </div>
        </div>
      )}

      {viewModalData && (
        <div className="modal-overlay" onClick={() => setViewModalData(null)}>
          <div className="modal-container" style={{ maxWidth: '800px' }} onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2 className="modal-title">Invoice Details: {viewModalData.invoice.invoice_no}</h2>
              <button className="modal-close" onClick={() => setViewModalData(null)}>&times;</button>
            </div>
            <div className="modal-body">
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '20px' }}>
                <div className="card" style={{ background: 'var(--bg-surface)' }}>
                  <div className="card-body">
                    <h4 className="text-secondary mb-2" style={{ fontSize: '13px', textTransform: 'uppercase' }}>Customer Info</h4>
                    <div className="fw-bold">{viewModalData.invoice.customer_name}</div>
                    <div>{viewModalData.invoice.customer_mobile || 'No Mobile'}</div>
                    <div className="text-sm text-secondary">{viewModalData.invoice.customer_address}</div>
                  </div>
                </div>
                <div className="card" style={{ background: 'var(--bg-surface)' }}>
                  <div className="card-body text-right">
                    <h4 className="text-secondary mb-2" style={{ fontSize: '13px', textTransform: 'uppercase' }}>Invoice Summary</h4>
                    <div><strong>Date:</strong> {new Date(viewModalData.invoice.invoice_date).toLocaleDateString()}</div>
                    <div><strong>Type:</strong> {viewModalData.invoice.payment_type == 2 ? 'Credit Sale' : 'Cash Sale'}</div>
                    <div className="text-primary mt-2" style={{ fontSize: '18px', fontWeight: 'bold' }}>Total: Rs {parseFloat(viewModalData.invoice.grand_total).toFixed(2)}</div>
                  </div>
                </div>
              </div>

              <h4 className="fw-bold mb-2">Order Items</h4>
              <div className="table-responsive" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                <table className="table" style={{ fontSize: '14px' }}>
                  <thead style={{ position: 'sticky', top: 0, backgroundColor: 'var(--bg-surface)', zIndex: 1 }}>
                    <tr>
                      <th>Product</th>
                      <th className="text-right">Price</th>
                      <th className="text-center">Qty</th>
                      <th className="text-right">Disc.</th>
                      <th className="text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {viewModalData.items.map(item => (
                      <tr key={item.id}>
                        <td>
                          <div className="fw-bold">{item.item_code}</div>
                          <div className="text-secondary" style={{ fontSize: '12px' }}>{item.item_name}</div>
                        </td>
                        <td className="text-right font-mono">{parseFloat(item.price).toFixed(2)}</td>
                        <td className="text-center">{item.quantity}</td>
                        <td className="text-right font-mono text-danger">{parseFloat(item.discount).toFixed(2)}</td>
                        <td className="text-right font-mono fw-bold">{parseFloat(item.total).toFixed(2)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
            <div className="modal-footer" style={{ borderTop: '1px solid var(--border-color)', display: 'flex', justifyContent: 'flex-end', paddingTop: '15px' }}>
              <button className="btn btn-secondary" onClick={() => setViewModalData(null)}>Close</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvoiceList;
