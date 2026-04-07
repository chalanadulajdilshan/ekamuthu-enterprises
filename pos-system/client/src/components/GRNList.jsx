import React, { useState, useEffect } from 'react';
import { FiShoppingCart, FiPlus, FiEye, FiSearch } from 'react-icons/fi';
import { getGrns, getGrnDetails } from '../services/api';

const GRNList = ({ onNavigate }) => {
  const [grns, setGrns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  
  // Modal State
  const [viewModalData, setViewModalData] = useState(null);
  const [loadingDetails, setLoadingDetails] = useState(false);

  useEffect(() => {
    fetchGrns();
  }, []);

  const fetchGrns = async () => {
    try {
      setLoading(true);
      const res = await getGrns();
      setGrns(res.data?.data || (Array.isArray(res.data) ? res.data : []));
    } catch (err) {
      setError('Failed to fetch GRNs: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const handleView = async (id) => {
    try {
      setLoadingDetails(true);
      const res = await getGrnDetails(id);
      setViewModalData(res.data?.data);
    } catch (err) {
      alert('Failed to load GRN details');
    } finally {
      setLoadingDetails(false);
    }
  };

  const filteredGrns = grns.filter(grn => 
    (grn.grn_no || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
    (grn.supplier_name || '').toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="page animate-fade-in">
      <div className="page-header-row" style={{ alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <h1 className="page-title">Goods Received Notes (GRN)</h1>
          <p className="page-subtitle">View and manage stock receipt logs</p>
        </div>
        <div className="page-actions">
          <button className="btn btn-primary" onClick={() => onNavigate('grn_new')}>
            <FiPlus style={{ marginRight: '8px' }} /> Create New GRN
          </button>
        </div>
      </div>

      <div className="card shadow-sm">
        <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div className="card-title"><FiShoppingCart style={{ marginRight: '8px' }} /> Recent GRNs</div>
          <div className="search-bar" style={{ width: '300px' }}>
            <FiSearch className="search-icon" />
            <input 
              type="text" 
              className="search-input" 
              placeholder="Search by GRN No or Supplier..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
        </div>
        
        <div className="table-responsive">
          <table className="table">
            <thead>
              <tr>
                <th>GRN No</th>
                <th>Entry Date</th>
                <th>Supplier</th>
                <th>Department</th>
                <th className="text-right">Total Amount</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan="6" className="text-center py-4">Loading GRNs...</td></tr>
              ) : error ? (
                <tr><td colSpan="6" className="text-center py-4 text-danger">{error}</td></tr>
              ) : filteredGrns.length === 0 ? (
                <tr><td colSpan="6" className="text-center py-4 text-secondary">No GRNs found.</td></tr>
              ) : (
                filteredGrns.map(grn => (
                  <tr key={grn.id}>
                    <td className="fw-bold text-primary">{grn.grn_no}</td>
                    <td>{new Date(grn.entry_date).toLocaleDateString()}</td>
                    <td>{grn.supplier_name}</td>
                    <td>{grn.department_name}</td>
                    <td className="text-right font-mono fw-bold">Rs. {parseFloat(grn.total_amount).toFixed(2)}</td>
                    <td className="text-center">
                      <button className="btn btn-secondary btn-icon" onClick={() => handleView(grn.id)} title="View Details">
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
          <div className="modal-container" style={{ maxWidth: '900px' }} onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2 className="modal-title">GRN Details: {viewModalData.grn.grn_no}</h2>
              <button className="modal-close" onClick={() => setViewModalData(null)}>&times;</button>
            </div>
            <div className="modal-body">
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '20px' }}>
                <div className="card" style={{ background: 'var(--bg-surface)' }}>
                  <div className="card-body">
                    <h4 className="text-secondary mb-2" style={{ fontSize: '13px', textTransform: 'uppercase' }}>Supplier Info</h4>
                    <div className="fw-bold">[{viewModalData.grn.supplier_code}] {viewModalData.grn.supplier_name}</div>
                    <div className="text-sm mt-2">Department: {viewModalData.grn.department_name}</div>
                  </div>
                </div>
                <div className="card" style={{ background: 'var(--bg-surface)' }}>
                  <div className="card-body text-right">
                    <h4 className="text-secondary mb-2" style={{ fontSize: '13px', textTransform: 'uppercase' }}>GRN Summary</h4>
                    <div><strong>Entry Date:</strong> {new Date(viewModalData.grn.entry_date).toLocaleDateString()}</div>
                    <div><strong>Supplier Invoice:</strong> {viewModalData.grn.invoice_no}</div>
                    <div className="text-primary mt-2" style={{ fontSize: '18px', fontWeight: 'bold' }}>Total Value: Rs {parseFloat(viewModalData.grn.total_amount).toFixed(2)}</div>
                  </div>
                </div>
              </div>

              <h4 className="fw-bold mb-2">Received Items</h4>
              <div className="table-responsive" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                <table className="table" style={{ fontSize: '14px' }}>
                  <thead style={{ position: 'sticky', top: 0, backgroundColor: 'var(--bg-surface)', zIndex: 1 }}>
                    <tr>
                      <th>Product Code</th>
                      <th className="text-right">List Price</th>
                      <th className="text-center">Qty</th>
                      <th className="text-right">Actual Cost</th>
                      <th className="text-right">Selling Price</th>
                      <th className="text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {viewModalData.items.map(item => (
                      <tr key={item.id}>
                        <td className="fw-bold">{item.item_code || 'Product ' + item.item_id}</td>
                        <td className="text-right font-mono">{parseFloat(item.list_price).toFixed(2)}</td>
                        <td className="text-center">{item.qty}</td>
                        <td className="text-right font-mono">{parseFloat(item.actual_cost).toFixed(2)}</td>
                        <td className="text-right font-mono text-success">{parseFloat(item.selling_price).toFixed(2)}</td>
                        <td className="text-right font-mono fw-bold text-primary">{parseFloat(item.unit_total).toFixed(2)}</td>
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

export default GRNList;
