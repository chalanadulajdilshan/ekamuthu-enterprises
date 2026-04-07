import React, { useState, useEffect } from 'react';
import { 
  FiPackage, FiUser, FiCalendar, FiFileText, FiPlus, FiTrash2, 
  FiSave, FiArrowLeft, FiSearch, FiShoppingCart, FiCreditCard,
  FiMapPin, FiTruck
} from 'react-icons/fi';
import { 
  getSuppliers, getDepartments, getProducts, createGrn, getNextGrnNo 
} from '../services/api';
import SearchableSelectModal from './SearchableSelectModal';

const GRN = ({ onBack }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  
  // Master Data
  const [suppliers, setSuppliers] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [products, setProducts] = useState([]);
  
  // Modal States
  const [showSupplierModal, setShowSupplierModal] = useState(false);
  const [showDepartmentModal, setShowDepartmentModal] = useState(false);
  const [showProductModal, setShowProductModal] = useState(false);
  const [showPaymentTypeModal, setShowPaymentTypeModal] = useState(false);
  
  // Form State
  const [formData, setFormData] = useState({
    arn_no: '',
    supplier_id: '',
    department_id: '',
    entry_date: new Date().toISOString().split('T')[0],
    invoice_no: '',
    invoice_date: new Date().toISOString().split('T')[0],
    payment_type: '1', // 1: Cash, 2: Credit
    remark: '',
    items: []
  });

  // Current Item State (for the entry line)
  const [currentItem, setCurrentItem] = useState({
    item_id: '',
    item_code: '',
    item_name: '',
    quantity: 1,
    list_price: 0,
    discount_1: 0,
    discount_2: 0,
    discount_3: 0,
    discount_4: 0,
    discount_5: 0,
    actual_cost: 0,
    invoice_price: 0,
    unit_total: 0
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      const [suppRes, deptRes, prodRes, nextNoRes] = await Promise.all([
        getSuppliers(),
        getDepartments(),
        getProducts({ all: true }),
        getNextGrnNo()
      ]);
      
      setSuppliers(suppRes.data?.data || (Array.isArray(suppRes.data) ? suppRes.data : []));
      setDepartments(deptRes.data?.data || (Array.isArray(deptRes.data) ? deptRes.data : []));
      setProducts(prodRes.data?.data || (Array.isArray(prodRes.data) ? prodRes.data : []));
      
      const nextNo = nextNoRes.data?.data || nextNoRes.data;
      setFormData(prev => ({ ...prev, arn_no: nextNo }));
    } catch (err) {
      console.error('GRN fetch error:', err);
      setError('Failed to load initial data. ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  // Calculate actual cost and unit total when current item changes
  useEffect(() => {
    const listPrice = parseFloat(currentItem.list_price) || 0;
    const q = parseFloat(currentItem.quantity) || 0;
    
    // Apply cascading discounts
    let discountedPrice = listPrice;
    [1, 2, 3, 4, 5].forEach(i => {
      const d = parseFloat(currentItem[`discount_${i}`]) || 0;
      discountedPrice = discountedPrice * (1 - d / 100);
    });

    setCurrentItem(prev => ({
      ...prev,
      actual_cost: discountedPrice.toFixed(2),
      unit_total: (discountedPrice * q).toFixed(2)
    }));
  }, [
    currentItem.list_price, currentItem.quantity, 
    currentItem.discount_1, currentItem.discount_2, currentItem.discount_3, 
    currentItem.discount_4, currentItem.discount_5
  ]);

  const handleProductSelect = (product) => {
    if (product) {
      setCurrentItem(prev => ({
        ...prev,
        item_id: product.id,
        item_code: product.code,
        item_name: product.name,
        list_price: product.list_price || 0,
        invoice_price: product.invoice_price || 0
      }));
    }
  };

  const getSupplierName = () => {
    const s = suppliers.find(s => s.id === parseInt(formData.supplier_id));
    return s ? `${s.code} - ${s.name}` : 'Select Supplier';
  };

  const getDepartmentName = () => {
    const d = departments.find(d => d.id === parseInt(formData.department_id));
    return d ? d.name : 'Select Department';
  };

  const getPaymentTypeName = () => {
    const types = [
      { id: '1', name: 'Cash Purchase' },
      { id: '2', name: 'Credit Purchase' }
    ];
    const t = types.find(t => t.id === String(formData.payment_type));
    return t ? t.name : 'Select Payment Type';
  };

  const addItem = () => {
    if (!currentItem.item_id || !currentItem.quantity) {
      setError(`Cannot add item: Missing Product ID (${currentItem.item_id}) or Quantity (${currentItem.quantity}). Please re-select the product.`);
      return;
    }
    setFormData(prev => ({
      ...prev,
      items: [...prev.items, { ...currentItem, id: Date.now() }]
    }));
    // Reset current item entry
    setCurrentItem({
      item_id: '',
      item_code: '',
      item_name: '',
      quantity: 1,
      list_price: 0,
      discount_1: 0,
      discount_2: 0,
      discount_3: 0,
      discount_4: 0,
      discount_5: 0,
      actual_cost: 0,
      invoice_price: 0,
      unit_total: 0
    });
    setError('');
  };

  const removeItem = (id) => {
    setFormData(prev => ({
      ...prev,
      items: prev.items.filter(item => item.id !== id)
    }));
  };

  const calculateTotals = () => {
    const subTotal = formData.items.reduce((acc, item) => acc + (parseFloat(item.list_price) * parseFloat(item.quantity)), 0);
    const grandTotal = formData.items.reduce((acc, item) => acc + parseFloat(item.unit_total), 0);
    const totalDiscount = subTotal - grandTotal;
    
    return {
      subTotal: subTotal.toFixed(2),
      totalDiscount: totalDiscount.toFixed(2),
      grandTotal: grandTotal.toFixed(2),
      totalQty: formData.items.reduce((acc, item) => acc + parseFloat(item.quantity), 0)
    };
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (formData.items.length === 0) {
      setError('Please add at least one item');
      return;
    }
    if (!formData.supplier_id || !formData.department_id) {
      setError('Please select supplier and department');
      return;
    }

    try {
      setLoading(true);
      setError('');
      const { subTotal, totalDiscount, grandTotal } = calculateTotals();
      
      const payload = {
        ...formData,
        sub_total: subTotal,
        total_discount: totalDiscount,
        total_arn_value: grandTotal
      };
      
      await createGrn(payload);
      setSuccess('GRN Saved successfully and stock updated!');
      setTimeout(() => onBack(), 2000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to save GRN');
    } finally {
      setLoading(false);
    }
  };

  const totals = calculateTotals();

  return (
    <div className="page animate-fade-in">
      {/* Header */}
      <div className="page-header-row" style={{ alignItems: 'center', marginBottom: '24px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
          <button className="btn btn-secondary" onClick={onBack} style={{ padding: '8px' }}>
            <FiArrowLeft />
          </button>
          <div>
            <h1 className="page-title">Goods Received Note (GRN/ARN)</h1>
            <p className="page-subtitle">Stock replenishment & Purchase recording</p>
          </div>
        </div>
        <div className="page-actions">
           <span className="badge badge-primary" style={{ fontSize: '14px', padding: '6px 12px' }}>ARN No: {formData.arn_no}</span>
        </div>
      </div>

      <form onSubmit={handleSubmit}>
        {error && <div className="alert alert-danger mb-4" style={{ padding: '15px', borderRadius: 'var(--radius)', backgroundColor: '#fee2e2', color: '#991b1b', border: '1px solid #f87171' }}><strong>Error:</strong> {error}</div>}
        {success && <div className="alert alert-success mb-4" style={{ padding: '15px', borderRadius: 'var(--radius)', backgroundColor: '#dcfce7', color: '#166534', border: '1px solid #4ade80' }}><strong>Success:</strong> {success}</div>}
        
        {/* Main Info Card */}
        <div className="card mb-4 shadow-sm">
          <div className="card-header">
            <div className="card-title"><FiFileText /> General Information</div>
          </div>
          <div className="card-body">
            <div className="form-grid form-grid-4" style={{ rowGap: '20px' }}>
              <div className="form-group">
                <label className="form-label">ARN Number</label>
                <input type="text" value={formData.arn_no} readOnly className="form-input" style={{ fontWeight: 700, color: 'var(--primary)' }} />
              </div>
              <div className="form-group">
                <label className="form-label">Supplier</label>
                <button 
                  type="button"
                  className="selection-trigger" 
                  onClick={() => setShowSupplierModal(true)}
                >
                  <span className={formData.supplier_id ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                    <FiTruck style={{ marginRight: 8 }} />
                    {getSupplierName()}
                  </span>
                  <FiSearch className="trigger-icon" />
                </button>
              </div>

              <div className="form-group">
                <label className="form-label">Department / Location</label>
                <button 
                  type="button"
                  className="selection-trigger" 
                  onClick={() => setShowDepartmentModal(true)}
                >
                  <span className={formData.department_id ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                    <FiMapPin style={{ marginRight: 8 }} />
                    {getDepartmentName()}
                  </span>
                  <FiSearch className="trigger-icon" />
                </button>
              </div>
              <div className="form-group">
                <label className="form-label">Entry Date</label>
                <input 
                  type="date" 
                  className="form-input" 
                  value={formData.entry_date}
                  onChange={(e) => setFormData({...formData, entry_date: e.target.value})}
                />
              </div>

              <div className="form-group">
                <label className="form-label">Invoice Number</label>
                <input 
                  type="text" 
                  placeholder="Supplier Invoice #"
                  className="form-input"
                  value={formData.invoice_no}
                  onChange={(e) => setFormData({...formData, invoice_no: e.target.value})}
                />
              </div>
              <div className="form-group">
                <label className="form-label">Invoice Date</label>
                <input 
                  type="date" 
                  className="form-input"
                  value={formData.invoice_date}
                  onChange={(e) => setFormData({...formData, invoice_date: e.target.value})}
                />
              </div>
              <div className="form-group">
                <label className="form-label">Payment Type</label>
                <button 
                  type="button"
                  className="selection-trigger" 
                  onClick={() => setShowPaymentTypeModal(true)}
                >
                  <span className={formData.payment_type ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                    <FiCreditCard style={{ marginRight: 8 }} />
                    {getPaymentTypeName()}
                  </span>
                  <FiSearch className="trigger-icon" />
                </button>
              </div>
              <div className="form-group">
                <label className="form-label">Remarks</label>
                <input 
                  type="text" 
                  className="form-input"
                  placeholder="Notes..."
                  value={formData.remark}
                  onChange={(e) => setFormData({...formData, remark: e.target.value})}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Item Entry Section */}
        <div className="card mb-4 border-primary" style={{ borderLeft: '4px solid var(--primary)' }}>
          <div className="card-header">
            <div className="card-title text-primary"><FiShoppingCart /> Add Items to Stock</div>
          </div>
          <div className="card-body">
            <div className="form-grid form-grid-4" style={{ rowGap: '20px' }}>
              <div className="form-group span-2">
                <label className="form-label">Select Product</label>
                <button 
                  type="button"
                  className="selection-trigger" 
                  onClick={() => setShowProductModal(true)}
                  style={{ height: '42px' }}
                >
                  <span className={currentItem.item_id ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                    <FiShoppingCart style={{ marginRight: 8 }} />
                    {currentItem.item_id ? `${currentItem.item_code} - ${currentItem.item_name}` : 'Search/Select Product Item'}
                  </span>
                  <FiSearch className="trigger-icon" />
                </button>
              </div>
              <div className="form-group">
                <label className="form-label">Received Qty</label>
                <input 
                  type="number" 
                  className="form-input" 
                  value={currentItem.quantity}
                  onChange={(e) => setCurrentItem({...currentItem, quantity: e.target.value})}
                />
              </div>
              <div className="form-group">
                <label className="form-label">List Price (Cost)</label>
                <input 
                  type="number" 
                  className="form-input"
                  value={currentItem.list_price}
                  onChange={(e) => setCurrentItem({...currentItem, list_price: e.target.value})}
                />
              </div>
            </div>

            <div className="form-grid mt-3" style={{ gridTemplateColumns: 'repeat(5, 1fr)', gap: '12px' }}>
              {[1, 2, 3, 4, 5].map(i => (
                <div key={i} className="form-group">
                  <label className="form-label" style={{ fontSize: '10px' }}>Dis {i} %</label>
                  <input 
                    type="number" 
                    className="form-input"
                    style={{ padding: '6px' }}
                    value={currentItem[`discount_${i}`]}
                    onChange={(e) => setCurrentItem({...currentItem, [`discount_${i}`]: e.target.value})}
                  />
                </div>
              ))}
            </div>

            <div className="form-grid form-grid-4 mt-3" style={{ alignItems: 'flex-end' }}>
              <div className="form-group">
                <label className="form-label">Actual Cost (Per Unit)</label>
                <input type="text" readOnly className="form-input fw-bold" style={{ background: 'var(--bg-hover)', color: 'var(--primary)' }} value={currentItem.actual_cost} />
              </div>
              <div className="form-group">
                <label className="form-label">New Selling Price</label>
                <input 
                  type="number" 
                  className="form-input"
                  value={currentItem.invoice_price}
                  onChange={(e) => setCurrentItem({...currentItem, invoice_price: e.target.value})}
                />
              </div>
              <div className="form-group">
                <label className="form-label">Unit Total</label>
                <input type="text" readOnly className="form-input fw-bold" style={{ background: 'var(--info-light)', color: 'var(--info)' }} value={currentItem.unit_total} />
              </div>
              <div className="form-group">
                <button type="button" className="btn btn-primary w-100" style={{ height: '42px' }} onClick={addItem}>
                  <FiPlus /> Add Item
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Added Items Table */}
        <div className="card mb-4 overflow-hidden">
          <div className="table-container">
            <table className="table">
              <thead>
                <tr>
                  <th>Item Code</th>
                  <th>Description</th>
                  <th style={{ textAlign: 'center' }}>Qty</th>
                  <th style={{ textAlign: 'right' }}>List Price</th>
                  <th style={{ textAlign: 'center' }}>Dis %</th>
                  <th style={{ textAlign: 'right' }}>Actual Cost</th>
                  <th style={{ textAlign: 'right' }}>Total</th>
                  <th style={{ textAlign: 'center' }}>Action</th>
                </tr>
              </thead>
              <tbody>
                {formData.items.length === 0 ? (
                  <tr>
                    <td colSpan="8" style={{ textAlign: 'center', padding: '40px', color: 'var(--text-muted)' }}>
                      <div className="empty-state">
                        <div className="empty-state-title">No items added yet</div>
                        <p>Search and add items to receive stock.</p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  formData.items.map(item => (
                    <tr key={item.id}>
                      <td><span className="badge badge-primary">{item.item_code}</span></td>
                      <td style={{ fontWeight: 600 }}>{item.item_name}</td>
                      <td style={{ textAlign: 'center', fontWeight: 800 }}>{item.quantity}</td>
                      <td style={{ textAlign: 'right' }}>{parseFloat(item.list_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                      <td style={{ textAlign: 'center' }}>
                        <small className="text-muted">
                          {[1,2,3,4,5].filter(i => item[`discount_${i}`] > 0).map(i => `${item[`discount_${i}`]}%`).join(' + ') || '0%'}
                        </small>
                      </td>
                      <td style={{ textAlign: 'right', fontWeight: 700, color: 'var(--primary)' }}>{parseFloat(item.actual_cost).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                      <td style={{ textAlign: 'right', fontWeight: 800 }}>{parseFloat(item.unit_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                      <td style={{ textAlign: 'center' }}>
                        <button type="button" className="btn btn-danger btn-sm" style={{ padding: '6px' }} onClick={() => removeItem(item.id)}>
                          <FiTrash2 />
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Footer Summary & Submit */}
        <div className="form-grid form-grid-2 mb-5" style={{ alignItems: 'flex-start' }}>
          <div className="card h-100">
             <div className="card-body">
                
                <div className="form-group mb-4">
                  <label className="form-label">Final Remark</label>
                  <textarea className="form-textarea" placeholder="Overall notes for this ARN..." value={formData.remark} onChange={(e) => setFormData({...formData, remark: e.target.value})}></textarea>
                </div>

                <div style={{ display: 'flex', gap: '12px' }}>
                  <button 
                    type="submit" 
                    className="btn btn-primary btn-lg" 
                    disabled={loading || formData.items.length === 0}
                    style={{ flex: 2 }}
                  >
                    <FiSave /> {loading ? 'Saving...' : 'Confirm & Save GRN'}
                  </button>
                  <button type="button" className="btn btn-secondary btn-lg" onClick={onBack} style={{ flex: 1 }}>
                    Cancel
                  </button>
                </div>
             </div>
          </div>

          <div className="card" style={{ background: 'var(--bg)' }}>
            <div className="card-body">
              <div className="summary-row">
                <span>Sub Total:</span>
                <span className="fw-bold">{parseFloat(totals.subTotal).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
              </div>
              <div className="summary-row" style={{ color: 'var(--danger)' }}>
                <span>Total Discount:</span>
                <span className="fw-bold">-{parseFloat(totals.totalDiscount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
              </div>
              <div className="summary-row">
                <span>Total Items:</span>
                <span>{formData.items.length} (Sum: {totals.totalQty})</span>
              </div>
              <div className="summary-total mt-3 pt-3" style={{ borderTop: '2px dashed var(--border)' }}>
                <span>Grand Total:</span>
                <span style={{ fontSize: '24px', fontWeight: 800, color: 'var(--primary)' }}>
                  LKR {parseFloat(totals.grandTotal).toLocaleString(undefined, {minimumFractionDigits: 2})}
                </span>
              </div>
            </div>
          </div>
        </div>
      </form>

      {/* Modals */}
      <SearchableSelectModal 
        isOpen={showSupplierModal}
        onClose={() => setShowSupplierModal(false)}
        onSelect={(s) => setFormData({...formData, supplier_id: s.id})}
        data={suppliers}
        title="Select Supplier"
        searchPlaceholder="Type supplier name or code..."
        renderItem="name"
      />

      <SearchableSelectModal 
        isOpen={showDepartmentModal}
        onClose={() => setShowDepartmentModal(false)}
        onSelect={(d) => setFormData({...formData, department_id: d.id})}
        data={departments}
        title="Select Department"
        searchPlaceholder="Type department name..."
        renderItem="name"
      />

      <SearchableSelectModal 
        isOpen={showProductModal}
        onClose={() => setShowProductModal(false)}
        onSelect={handleProductSelect}
        data={products}
        title="Select Product Item"
        searchPlaceholder="Type product name or code..."
        renderItem={(p) => (
          <>
            <span className="item-code">{p.code}</span>
            <span className="item-name">{p.name}</span>
            <span style={{ fontSize: '11px', color: 'var(--text-muted)', marginLeft: 'auto' }}>
              Stock: {p.available_qty}
            </span>
          </>
        )}
      />

      <SearchableSelectModal
        isOpen={showPaymentTypeModal}
        onClose={() => setShowPaymentTypeModal(false)}
        onSelect={(t) => setFormData({...formData, payment_type: t.id})}
        data={[
          { id: '1', name: 'Cash Purchase' },
          { id: '2', name: 'Credit Purchase' }
        ]}
        title="Select Payment Type"
        renderItem="name"
      />
      
      <style>{`
        .summary-row {
          display: flex;
          justify-content: space-between;
          padding: 8px 0;
          font-size: 14px;
          color: var(--text-secondary);
        }
        .summary-total {
          display: flex;
          justify-content: space-between;
          align-items: center;
        }
        .border-primary { border-color: var(--primary) !important; }
        .bg-light { background-color: var(--bg-hover) !important; }
        .text-primary { color: var(--primary) !important; }
        .fw-bold { font-weight: 700; }
        .mb-4 { margin-bottom: 24px; }
        .mt-3 { margin-top: 16px; }
        .mt-4 { margin-top: 24px; }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
      `}</style>
    </div>
  );
};

export default GRN;
