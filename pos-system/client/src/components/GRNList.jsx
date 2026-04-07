import React, { useState, useEffect } from 'react';
import { FiShoppingCart, FiPlus, FiEye, FiSearch } from 'react-icons/fi';
import { getGrns, getGrnDetails } from '../services/api';

const GRNList = ({ onNavigate }) => {
  const [grns, setGrns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

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

  const handleView = (id) => {
    onNavigate('grn_view', id);
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


    </div>
  );
};

export default GRNList;
