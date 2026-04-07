import React, { useState, useEffect } from 'react';
import { FiBox, FiSearch } from 'react-icons/fi';
import { getProducts } from '../services/api';

const LiveStock = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    fetchStock();
  }, []);

  const fetchStock = async () => {
    try {
      setLoading(true);
      // We pass { all: true } so we get accurate stock for everything, 
      // even if something was temporarily deactivated but has remaining stock.
      const res = await getProducts({ all: true });
      const data = res.data?.data || (Array.isArray(res.data) ? res.data : []);
      // Filter out items that might not have a valid available_qty or items that aren't actually physical stock items if needed
      setProducts(data);
    } catch (err) {
      setError('Failed to fetch stock: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const filteredProducts = products.filter(product => {
    const term = searchTerm.toLowerCase();
    const nameMatch = (product.name || '').toLowerCase().includes(term);
    const codeMatch = (product.code || '').toLowerCase().includes(term);
    const categoryMatch = (product.category_name || '').toLowerCase().includes(term);
    return nameMatch || codeMatch || categoryMatch;
  });

  const totalStockValue = filteredProducts.reduce((acc, p) => acc + (parseFloat(p.available_qty || 0) * parseFloat(p.list_price || 0)), 0);
  const totalStockItems = filteredProducts.reduce((acc, p) => acc + parseFloat(p.available_qty || 0), 0);

  return (
    <div className="page animate-fade-in">
      <div className="page-header-row animate-fade-in" style={{ alignItems: 'center', marginBottom: '32px' }}>
        <div>
          <h1 className="page-title" style={{ fontSize: 28 }}>Stock Analytics & Inventory</h1>
          <p className="page-subtitle">Real-time tracking of current physical stock and holding value</p>
        </div>
      </div>

      <div className="stats-grid mb-5">
        <div className="stat-card glass shadow-lg" style={{ borderLeft: '5px solid var(--primary)' }}>
          <div className="stat-card-top">
            <span className="stat-card-label">Warehouse Inventory</span>
          </div>
          <div className="stat-card-value">{totalStockItems.toLocaleString()}</div>
          <div className="stat-card-sub">Total units available in stock</div>
        </div>

        <div className="stat-card glass shadow-lg" style={{ borderLeft: '5px solid var(--success)' }}>
          <div className="stat-card-top">
            <span className="stat-card-label">Net Asset Value</span>
          </div>
          <div className="stat-card-value" style={{ color: 'var(--success)' }}>
            Rs. {totalStockValue.toLocaleString(undefined, { minimumFractionDigits: 2 })}
          </div>
          <div className="stat-card-sub">Estimated holding cost value</div>
        </div>
      </div>

      <div className="card shadow-md animate-fade-in">
        <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div className="card-title"><FiBox style={{ marginRight: '8px' }} /> Default Warehouse Stock</div>
          <div className="search-bar" style={{ width: '400px' }}>
            <FiSearch className="search-icon" />
            <input 
              type="text" 
              className="search-input" 
              placeholder="Search by code, product name, or category..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
        </div>
        
        <div className="table-responsive" style={{ maxHeight: 'calc(100vh - 400px)', overflowY: 'auto' }}>
          <table className="table">
            <thead style={{ position: 'sticky', top: 0, backgroundColor: 'var(--bg-surface)', zIndex: 1, boxShadow: '0 2px 4px rgba(0,0,0,0.05)' }}>
              <tr>
                <th style={{ paddingLeft: 24 }}>Reference</th>
                <th>Product Name</th>
                <th>Category</th>
                <th className="text-center">Status</th>
                <th className="text-center">Min Level</th>
                <th className="text-center" style={{ width: '120px' }}>Quantity</th>
                <th className="text-right">Unit List</th>
                <th className="text-right" style={{ paddingRight: 24 }}>Holding Val</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan="8" className="text-center py-5"><div className="spinner" style={{ margin: 'auto' }} /></td></tr>
              ) : error ? (
                <tr><td colSpan="8" className="text-center py-4 text-danger">{error}</td></tr>
              ) : filteredProducts.length === 0 ? (
                <tr><td colSpan="8" className="text-center py-4 text-secondary">No stock records found matching your search.</td></tr>
              ) : (
                filteredProducts.map((p, index) => {
                  const qty = parseFloat(p.available_qty || 0);
                  const isLow = qty <= parseFloat(p.re_order_level || 0) && qty > 0;
                  const isOut = qty <= 0;
                  
                  return (
                  <tr key={p.id || index} style={{ backgroundColor: isOut ? 'rgba(239, 68, 68, 0.05)' : isLow ? 'rgba(245, 158, 11, 0.05)' : 'transparent' }}>
                    <td style={{ paddingLeft: 24 }}><span className="badge badge-primary">{p.code}</span></td>
                    <td style={{ fontWeight: 600 }}>{p.name}</td>
                    <td style={{ fontSize: 13, color: 'var(--text-muted)' }}>{p.category_name || '-'}</td>
                    <td className="text-center">
                        <span className={`badge ${p.is_active === 1 || p.is_active === '1' ? 'badge-success' : 'badge-danger'}`} style={{ padding: '4px 8px' }}>
                           {p.is_active === 1 || p.is_active === '1' ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td className="text-center" style={{ color: 'var(--text-muted)', fontSize: 13 }}>{p.re_order_level || 0}</td>
                    <td className="text-center">
                       <span className={`badge w-100 ${isOut ? 'badge-danger' : isLow ? 'badge-warning' : 'badge-success'}`} style={{ fontSize: '14px', padding: '6px', fontWeight: 700 }}>
                           {qty}
                       </span>
                    </td>
                    <td className="text-right font-mono">Rs. {parseFloat(p.list_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td className="text-right fw-bold text-primary" style={{ paddingRight: 24 }}>Rs. {(qty * parseFloat(p.list_price || 0)).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                  </tr>
                )})
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default LiveStock;
