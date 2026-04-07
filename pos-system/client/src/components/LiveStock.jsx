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
      <div className="page-header-row" style={{ alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <h1 className="page-title">Live Stock Value & Inventory</h1>
          <p className="page-subtitle">Real-time tracking of current physical stock and holding value</p>
        </div>
      </div>

      <div className="form-grid form-grid-2 mb-4">
         <div className="card shadow-sm" style={{ borderLeft: '4px solid var(--primary)' }}>
             <div className="card-body">
               <div className="text-secondary" style={{ fontSize: '14px', textTransform: 'uppercase', marginBottom: '8px', fontWeight: 'bold' }}>Total Physical Items in Stock</div>
               <div style={{ fontSize: '32px', fontWeight: 800, color: 'var(--text-color)' }}>{totalStockItems}</div>
             </div>
         </div>
         <div className="card shadow-sm" style={{ borderLeft: '4px solid var(--success)' }}>
             <div className="card-body">
               <div className="text-secondary" style={{ fontSize: '14px', textTransform: 'uppercase', marginBottom: '8px', fontWeight: 'bold' }}>Total Stock Holding Value (List Price)</div>
               <div style={{ fontSize: '32px', fontWeight: 800, color: 'var(--success)' }}>
                  Rs. {totalStockValue.toLocaleString(undefined, { minimumFractionDigits: 2 })}
               </div>
             </div>
         </div>
      </div>

      <div className="card shadow-sm">
        <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div className="card-title"><FiBox style={{ marginRight: '8px' }} /> Default Warehouse Stock</div>
          <div className="search-bar" style={{ width: '350px' }}>
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
        
        <div className="table-responsive" style={{ maxHeight: 'calc(100vh - 350px)', overflowY: 'auto' }}>
          <table className="table" style={{ fontSize: '14px' }}>
            <thead style={{ position: 'sticky', top: 0, backgroundColor: 'var(--bg-surface)', zIndex: 1, boxShadow: '0 1px 0px var(--border-color)' }}>
              <tr>
                <th>Item Code</th>
                <th>Product Name</th>
                <th>Category</th>
                <th className="text-center">Active Status</th>
                <th className="text-center">Re-order Level</th>
                <th className="text-center" style={{ width: '120px' }}>In Stock Qty</th>
                <th className="text-right">Unit List Price</th>
                <th className="text-right">Holding Value</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan="8" className="text-center py-4 text-primary fw-bold">Loading Live Inventory...</td></tr>
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
                  <tr key={p.id || index} style={{ backgroundColor: isOut ? '#fee2e255' : isLow ? '#fef3c755' : 'transparent' }}>
                    <td className="fw-bold">{p.code}</td>
                    <td style={{ fontWeight: 600 }}>{p.name}</td>
                    <td>{p.category_name || '-'}</td>
                    <td className="text-center">
                        <span className={`badge ${p.is_active === 1 || p.is_active === '1' ? 'badge-success' : 'badge-danger'}`} style={{ padding: '4px 8px' }}>
                           {p.is_active === 1 || p.is_active === '1' ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td className="text-center" style={{ color: 'var(--text-muted)' }}>{p.re_order_level || 0}</td>
                    <td className="text-center">
                       <span className={`badge w-100 ${isOut ? 'badge-danger' : isLow ? 'badge-warning' : 'badge-success'}`} style={{ fontSize: '14px', padding: '6px' }}>
                           {qty}
                       </span>
                    </td>
                    <td className="text-right">Rs. {parseFloat(p.list_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td className="text-right fw-bold text-primary">Rs. {(qty * parseFloat(p.list_price || 0)).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
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
