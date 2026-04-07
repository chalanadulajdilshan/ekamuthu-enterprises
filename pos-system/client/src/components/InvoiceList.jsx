import React, { useState, useEffect } from 'react';
import { FiFileText, FiPlus, FiEye, FiSearch } from 'react-icons/fi';
import { getRecentSales, getSaleDetails } from '../services/api';

const InvoiceList = ({ onNavigate }) => {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

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

  const handleView = (id) => {
    onNavigate('invoice_view', id);
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


    </div>
  );
};

export default InvoiceList;
