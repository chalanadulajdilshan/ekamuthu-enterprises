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
import Swal from 'sweetalert2';

const GRN = ({ onBack }) => {
  const [loading, setLoading] = useState(false);

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
    department_id: '1',
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
    const onErr = (e) => {
      console.error('[GRN window.error]', e.error || e.message, e);
    };
    const onRej = (e) => {
      console.error('[GRN unhandledrejection]', e.reason);
    };
    window.addEventListener('error', onErr);
    window.addEventListener('unhandledrejection', onRej);
    return () => {
      window.removeEventListener('error', onErr);
      window.removeEventListener('unhandledrejection', onRej);
    };
  }, []);

  useEffect(() => {
    fetchInitialData();
  }, []);

  const fetchInitialData = async () => {
    setLoading(true);
    const unwrap = (res) => res.data?.data || (Array.isArray(res.data) ? res.data : []);

    const results = await Promise.allSettled([
      getSuppliers(),
      getDepartments(),
      getProducts({ all: true }),
      getNextGrnNo()
    ]);
    const [suppR, deptR, prodR, nextR] = results;

    if (suppR.status === 'fulfilled') setSuppliers(unwrap(suppR.value));
    else console.error('GRN suppliers fetch failed:', suppR.reason);

    if (deptR.status === 'fulfilled') setDepartments(unwrap(deptR.value));
    else console.error('GRN departments fetch failed:', deptR.reason);

    if (prodR.status === 'fulfilled') {
      const list = unwrap(prodR.value);
      console.log('GRN products loaded:', list.length);
      setProducts(list);
    } else {
      console.error('GRN products fetch failed:', prodR.reason);
    }

    if (nextR.status === 'fulfilled') {
      const nextNo = nextR.value.data?.data || nextR.value.data;
      setFormData(prev => ({ ...prev, arn_no: nextNo }));
    } else {
      console.error('GRN next-no fetch failed:', nextR.reason);
    }

    const failed = results
      .map((r, i) => ({ r, name: ['suppliers', 'departments', 'products', 'next ARN no'][i] }))
      .filter(x => x.r.status === 'rejected');
    if (failed.length) {
      const msg = failed.map(f => `${f.name}: ${f.r.reason?.response?.data?.message || f.r.reason?.message || 'failed'}`).join(' | ');
      Swal.fire({
        icon: 'error',
        title: 'Initial Data Error',
        text: 'Some data failed to load: ' + msg
      });
    }

    setLoading(false);
  };

  // Calculate actual cost and unit total when current item changes
  useEffect(() => {
    const listPrice = parseFloat(currentItem.list_price) || 0;
    const q = parseFloat(currentItem.quantity) || 0;
    
    // Apply single discount
    const d = parseFloat(currentItem.discount_1) || 0;
    const discountedPrice = listPrice * (1 - d / 100);

    setCurrentItem(prev => ({
      ...prev,
      actual_cost: discountedPrice.toFixed(2),
      unit_total: (discountedPrice * q).toFixed(2)
    }));
  }, [
    currentItem.list_price, currentItem.quantity, currentItem.discount_1
  ]);

  const handleProductSelect = (product) => {
    console.log('[GRN] product selected:', product);
    if (!product) {
      Swal.fire({ icon: 'error', title: 'Product Select Error', text: 'Product selection failed.' });
      return;
    }
    // Accept several possible id/code/name field shapes from the API
    const pid = product.id ?? product.Id ?? product.item_id ?? product.ID;
    const pcode = product.code ?? product.item_code ?? product.Code ?? '';
    const pname = product.name ?? product.item_name ?? product.Name ?? '';
    if (pid == null || pid === '') {
      Swal.fire({ icon: 'error', title: 'Invalid Product', text: 'Selected product has no valid database ID.' });
      return;
    }
    setCurrentItem(prev => ({
      ...prev,
      item_id: pid,
      item_code: pcode,
      item_name: pname,
      list_price: Number(product.list_price ?? product.cost_price ?? 0) || 0,
      invoice_price: Number(product.invoice_price ?? product.retail_price ?? 0) || 0
    }));
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
    console.log('[GRN] addItem called. currentItem =', currentItem);
    const hasId = currentItem.item_id !== '' && currentItem.item_id != null;
    const qty = parseFloat(currentItem.quantity);
    if (!hasId) {
      Swal.fire({ icon: 'warning', title: 'Missing Item', text: 'Please select a product first.' });
      return;
    }
    if (!qty || qty <= 0) {
      Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid quantity greater than zero.' });
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
      Swal.fire({ icon: 'warning', title: 'Empty GRN', text: 'Please add at least one item before saving.' });
      return;
    }
    if (!formData.supplier_id || !formData.department_id) {
      Swal.fire({ icon: 'warning', title: 'Missing Information', text: 'Please select both a supplier and a department.' });
      return;
    }

    try {
      setLoading(true);
      const { subTotal, totalDiscount, grandTotal } = calculateTotals();

      const payload = {
        ...formData,
        sub_total: subTotal,
        total_discount: totalDiscount,
        total_arn_value: grandTotal
      };

      await createGrn(payload);
      Swal.fire({
        icon: 'success',
        title: 'GRN Saved!',
        text: 'Goods Received Note has been successfully recorded and stock levels updated.',
        timer: 2000,
        showConfirmButton: false
      });
      setTimeout(() => onBack(), 2000);
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Save Failed',
        text: err.response?.data?.message || 'Failed to save GRN'
      });
    } finally {
      setLoading(false);
    }
  };

  const totals = calculateTotals();

  return (
    <div className="page animate-fade-in">
      {/* Header */}
      <div className="page-header-row" style={{ alignItems: 'center', marginBottom: '24px', display: 'flex', justifyContent: 'space-between' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
          <button className="btn btn-secondary" onClick={onBack} style={{ padding: '8px' }}>
            <FiArrowLeft />
          </button>
          <div>
            <h1 className="page-title">Goods Received Note (GRN/ARN)</h1>
            <p className="page-subtitle">Stock replenishment & Purchase recording</p>
          </div>
        </div>
        <div className="page-actions" style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
          <span className="badge badge-primary" style={{ fontSize: '14px', padding: '10px 16px', borderRadius: '8px' }}>ARN No: {formData.arn_no}</span>
          <button 
            type="button" 
            className="btn btn-primary" 
            disabled={loading || formData.items.length === 0}
            onClick={handleSubmit}
            style={{ height: '48px', padding: '0 24px', fontWeight: 'bold', boxShadow: '0 4px 12px rgba(var(--primary-rgb), 0.3)' }}
          >
            {loading ? 'Saving...' : (
              <><FiSave style={{ marginRight: '8px', fontSize: '18px' }} /> SAVE GRN</>
            )}
          </button>
        </div>
      </div>



      <form onSubmit={handleSubmit}>
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
                  onChange={(e) => setFormData({ ...formData, entry_date: e.target.value })}
                />
              </div>

              <div className="form-group">
                <label className="form-label">Invoice Number</label>
                <input
                  type="text"
                  placeholder="Supplier Invoice #"
                  className="form-input"
                  value={formData.invoice_no}
                  onChange={(e) => setFormData({ ...formData, invoice_no: e.target.value })}
                />
              </div>
              <div className="form-group">
                <label className="form-label">Invoice Date</label>
                <input
                  type="date"
                  className="form-input"
                  value={formData.invoice_date}
                  onChange={(e) => setFormData({ ...formData, invoice_date: e.target.value })}
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
                  onChange={(e) => setFormData({ ...formData, remark: e.target.value })}
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
            {/* ROW 1: Product, Qty, List Price, Discount */}
            <div className="form-grid" style={{ gridTemplateColumns: 'minmax(250px, 2fr) 1fr 1fr 1fr', gap: '15px', alignItems: 'end', marginBottom: '20px' }}>
              <div className="form-group">
                <label className="form-label">
                  Select Product
                  <span style={{ marginLeft: 8, fontSize: 11, color: '#888' }}>
                    [debug: products loaded={products.length}, picked id={String(currentItem.item_id || '-')}]
                  </span>
                </label>
                <button
                  type="button"
                  className="selection-trigger"
                  onClick={() => {
                    console.log('[GRN] opening product modal. products.length=', products.length);
                    if (!products.length) {
                      Swal.fire({ icon: 'error', title: 'Empty Product List', text: 'No products were found in the database. Please check your inventory.' });
                    }
                    setShowProductModal(true);
                  }}
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
                  style={{ height: '42px' }}
                  value={currentItem.quantity}
                  onChange={(e) => setCurrentItem({ ...currentItem, quantity: e.target.value })}
                />
              </div>
              <div className="form-group">
                <label className="form-label">List Price (Cost)</label>
                <input
                  type="number"
                  className="form-input"
                  style={{ height: '42px' }}
                  value={currentItem.list_price}
                  onChange={(e) => setCurrentItem({ ...currentItem, list_price: e.target.value })}
                />
              </div>
              <div className="form-group">
                <label className="form-label">Discount %</label>
                <input
                  type="number"
                  className="form-input"
                  style={{ height: '42px' }}
                  value={currentItem.discount_1}
                  onChange={(e) => setCurrentItem({ ...currentItem, discount_1: e.target.value })}
                />
              </div>
            </div>

            {/* ROW 2: Actual Cost, Selling Price, Unit Total, Add Button */}
            <div className="form-grid" style={{ gridTemplateColumns: '1fr 1fr 1fr auto', gap: '15px', alignItems: 'end' }}>
              <div className="form-group">
                <label className="form-label">Actual Cost (Per Unit)</label>
                <input type="text" readOnly className="form-input fw-bold" style={{ height: '42px', background: 'var(--bg-hover)', color: 'var(--primary)' }} value={currentItem.actual_cost} />
              </div>
              <div className="form-group">
                <label className="form-label">New Selling Price</label>
                <input
                  type="number"
                  className="form-input"
                  style={{ height: '42px' }}
                  value={currentItem.invoice_price}
                  onChange={(e) => setCurrentItem({ ...currentItem, invoice_price: e.target.value })}
                />
              </div>
              <div className="form-group">
                <label className="form-label">Unit Total</label>
                <input type="text" readOnly className="form-input fw-bold" style={{ height: '42px', background: 'var(--info-light)', color: 'var(--info)' }} value={currentItem.unit_total} />
              </div>
              <div className="form-group">
                <button type="button" className="btn btn-primary" style={{ height: '42px', padding: '0 30px' }} onClick={addItem}>
                  <FiPlus style={{ marginRight: '8px' }} /> Add Item
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
                      <td style={{ textAlign: 'right' }}>{parseFloat(item.list_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                      <td style={{ textAlign: 'center' }}>
                        <small className="text-muted">
                          {item.discount_1 > 0 ? `${item.discount_1}%` : '0%'}
                        </small>
                      </td>
                      <td style={{ textAlign: 'right', fontWeight: 700, color: 'var(--primary)' }}>{parseFloat(item.actual_cost).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                      <td style={{ textAlign: 'right', fontWeight: 800 }}>{parseFloat(item.unit_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
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
        <div className="form-grid form-grid-2 mb-5" style={{ alignItems: 'flex-start', gap: 24 }}>
          <div className="card shadow-md h-100">
            <div className="card-body">
              <div className="form-group mb-4">
                <label className="form-label">Final Remark</label>
                <textarea className="form-textarea" rows={3} placeholder="Overall notes for this ARN..." value={formData.remark} onChange={(e) => setFormData({ ...formData, remark: e.target.value })}></textarea>
              </div>


            </div>
          </div>

          <div className="card shadow-lg" style={{ background: 'var(--bg-hover)', border: 'none' }}>
            <div className="card-body" style={{ padding: 32 }}>
              <div className="summary-row" style={{ fontSize: 15, marginBottom: 12 }}>
                <span>Sub Total:</span>
                <span className="fw-bold">{parseFloat(totals.subTotal).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
              </div>
              <div className="summary-row" style={{ color: 'var(--danger)', fontSize: 15, marginBottom: 12 }}>
                <span>Total Discount:</span>
                <span className="fw-bold">-{parseFloat(totals.totalDiscount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
              </div>
              <div className="summary-row" style={{ fontSize: 15, marginBottom: 24 }}>
                <span>Total Items:</span>
                <span className="fw-bold">{formData.items.length} units</span>
              </div>
              
              <div className="premium-gradient shadow-lg" style={{
                padding: '24px',
                borderRadius: '20px',
              }}>
                <div style={{ fontSize: '13px', opacity: 0.9, fontWeight: 700, letterSpacing: 1, marginBottom: 8 }}>GRAND TOTAL</div>
                <div className="font-mono" style={{ fontSize: '32px', fontWeight: '900' }}>
                  LKR {parseFloat(totals.grandTotal).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>

      {/* Modals */}
      <SearchableSelectModal
        isOpen={showSupplierModal}
        onClose={() => setShowSupplierModal(false)}
        onSelect={(s) => setFormData({ ...formData, supplier_id: s.id })}
        data={suppliers}
        title="Select Supplier"
        searchPlaceholder="Type supplier name or code..."
        renderItem="name"
      />

      <SearchableSelectModal
        isOpen={showDepartmentModal}
        onClose={() => setShowDepartmentModal(false)}
        onSelect={(d) => setFormData({ ...formData, department_id: d.id })}
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
        title="Select Product to Receive"
        searchPlaceholder="Search by name, code or barcode..."
        columns={[
          { label: 'Item Code', key: 'code', width: '120px' },
          { label: 'Product Name', key: 'name' },
          { 
            label: 'Av. Qty', 
            key: 'available_qty', 
            width: '100px',
            render: (p) => (
              <span className={`badge ${p.available_qty <= p.re_order_level ? 'badge-danger' : 'badge-success'}`}>
                {p.available_qty}
              </span>
            )
          },
          { 
            label: 'Last Cost', 
            key: 'list_price', 
            width: '120px', 
            render: (p) => <span className="text-secondary">Rs. {parseFloat(p.list_price || p.cost_price || 0).toFixed(2)}</span>
          },
          { 
            label: 'Retail Price', 
            key: 'invoice_price', 
            width: '120px',
            render: (p) => <span className="fw-bold text-primary">Rs. {parseFloat(p.invoice_price || p.retail_price || 0).toFixed(2)}</span>
          }
        ]}
      />

      <SearchableSelectModal
        isOpen={showPaymentTypeModal}
        onClose={() => setShowPaymentTypeModal(false)}
        onSelect={(t) => setFormData({ ...formData, payment_type: t.id })}
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
