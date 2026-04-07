import React, { useState, useEffect } from 'react';
import {
  FiShoppingBag, FiUser, FiCalendar, FiFileText, FiPlus, FiTrash2,
  FiSave, FiArrowLeft, FiSearch, FiShoppingCart, FiCreditCard,
  FiMapPin, FiCheckCircle
} from 'react-icons/fi';
import {
  getCustomers, getDepartments, getProducts, createSale
} from '../services/api';
import SearchableSelectModal from './SearchableSelectModal';
import Swal from 'sweetalert2';

const Invoice = ({ onBack }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Master Data
  const [customers, setCustomers] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [products, setProducts] = useState([]);

  // Modal States
  const [showCustomerModal, setShowCustomerModal] = useState(false);
  const [showDepartmentModal, setShowDepartmentModal] = useState(false);
  const [showProductModal, setShowProductModal] = useState(false);
  const [showPaymentTypeModal, setShowPaymentTypeModal] = useState(false);

  // Form State
  const [formData, setFormData] = useState({
    customer_id: '',
    customer_name: '',
    customer_mobile: '',
    customer_address: '',
    department_id: '',
    invoice_date: new Date().toISOString().split('T')[0],
    payment_type: '1', // 1: Cash, 2: Credit
    remark: '',
    tax: 0,
    discount: 0,
    items: []
  });

  // Current Item State (for the entry line)
  const [currentItem, setCurrentItem] = useState({
    item_id: '',
    item_code: '',
    item_name: '',
    quantity: 1,
    price: 0, // retail price
    discount: 0,
    unit_total: 0,
    available_qty: 0
  });

  // Global runtime error visibility (so silent failures on live become visible).
  useEffect(() => {
    const onErr = (e) => {
      console.error('[Invoice window.error]', e.error || e.message, e);
      try { window.alert('JS Error: ' + (e.error?.stack || e.message || 'unknown')); } catch (_) {}
    };
    const onRej = (e) => {
      console.error('[Invoice unhandledrejection]', e.reason);
      try { window.alert('Promise rejected: ' + (e.reason?.stack || e.reason?.message || JSON.stringify(e.reason))); } catch (_) {}
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
      getCustomers(),
      getDepartments(),
      getProducts({ all: true })
    ]);
    const [custR, deptR, prodR] = results;

    if (custR.status === 'fulfilled') setCustomers(unwrap(custR.value));
    else console.error('Invoice customers fetch failed:', custR.reason);

    if (deptR.status === 'fulfilled') setDepartments(unwrap(deptR.value));
    else console.error('Invoice departments fetch failed:', deptR.reason);

    if (prodR.status === 'fulfilled') {
      const list = unwrap(prodR.value);
      console.log('Invoice products loaded:', list.length);
      setProducts(list);
    } else {
      console.error('Invoice products fetch failed:', prodR.reason);
    }

    const failed = results
      .map((r, i) => ({ r, name: ['customers', 'departments', 'products'][i] }))
      .filter(x => x.r.status === 'rejected');
    if (failed.length) {
      const msg = failed.map(f => `${f.name}: ${f.r.reason?.response?.data?.message || f.r.reason?.message || 'failed'}`).join(' | ');
      setError('Some data failed to load — ' + msg);
    }

    setLoading(false);
  };

  // Calculate unit total when current item changes
  useEffect(() => {
    const listPrice = parseFloat(currentItem.price) || 0;
    const q = parseFloat(currentItem.quantity) || 0;
    const d = parseFloat(currentItem.discount) || 0;

    let discountedPrice = listPrice - d;
    if (discountedPrice < 0) discountedPrice = 0;

    setCurrentItem(prev => ({
      ...prev,
      unit_total: (discountedPrice * q).toFixed(2)
    }));
  }, [currentItem.price, currentItem.quantity, currentItem.discount]);

  const handleProductSelect = (product) => {
    console.log('[Invoice] product selected (raw):', product);
    if (!product) {
      window.alert('Invoice: product select called with empty product');
      return;
    }
    // Accept several possible id/code/name field shapes from the API
    const pid = product.id ?? product.Id ?? product.item_id ?? product.ID;
    const pcode = product.code ?? product.item_code ?? product.Code ?? '';
    const pname = product.name ?? product.item_name ?? product.Name ?? '';
    if (pid == null || pid === '') {
      const m = 'Selected product has no id. Raw: ' + JSON.stringify(product);
      console.error(m);
      setError(m);
      window.alert(m);
      return;
    }
    setCurrentItem(prev => ({
      ...prev,
      item_id: pid,
      item_code: pcode,
      item_name: pname,
      price: Number(product.invoice_price ?? product.retail_price ?? 0) || 0,
      discount: 0,
      available_qty: Number(product.available_qty || 0)
    }));
    setError('');
  };

  const handleCustomerSelect = (customer) => {
    if (customer) {
      setFormData(prev => ({
        ...prev,
        customer_id: customer.id,
        customer_name: customer.name,
        customer_mobile: customer.mobile_number || '',
        customer_address: customer.address || ''
      }));
    }
  };

  const getCustomerName = () => {
    if (formData.customer_id) {
      return `${formData.customer_name} ${formData.customer_mobile ? `(${formData.customer_mobile})` : ''}`;
    }
    return formData.customer_name ? formData.customer_name : 'Walk-in Customer (Select or Type)';
  };

  const getDepartmentName = () => {
    const d = departments.find(d => d.id === parseInt(formData.department_id));
    return d ? d.name : 'Select Department';
  };

  const getPaymentTypeName = () => {
    const types = [
      { id: '1', name: 'Cash Sale' },
      { id: '2', name: 'Credit Sale' }
    ];
    const t = types.find(t => t.id === String(formData.payment_type));
    return t ? t.name : 'Select Payment Type';
  };

  const addItem = () => {
    console.log('[Invoice] addItem called. currentItem =', currentItem);
    const hasId = currentItem.item_id !== '' && currentItem.item_id != null;
    const qty = parseFloat(currentItem.quantity);
    if (!hasId) {
      const m = 'No item selected. item_id=' + JSON.stringify(currentItem.item_id) +
        ' code=' + JSON.stringify(currentItem.item_code) +
        ' name=' + JSON.stringify(currentItem.item_name);
      setError(m);
      window.alert(m);
      return;
    }
    if (!qty || qty <= 0) {
      const m = 'Invalid quantity: ' + JSON.stringify(currentItem.quantity);
      setError(m);
      window.alert(m);
      return;
    }

    // Check against available stock
    const alreadyAddedQty = formData.items
      .filter(i => i.item_id === currentItem.item_id)
      .reduce((sum, i) => sum + parseFloat(i.quantity), 0);
      
    if ((qty + alreadyAddedQty) > currentItem.available_qty) {
      const m = `Insufficient stock for ${currentItem.item_code}!\nAvailable: ${currentItem.available_qty}\nAlready in invoice: ${alreadyAddedQty}\nRequested: ${qty}\n\nYou cannot bill more than what is in stock.`;
      setError(m);
      window.alert(m);
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
      price: 0,
      discount: 0,
      unit_total: 0,
      available_qty: 0
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
    const subTotal = formData.items.reduce((acc, item) => acc + (parseFloat(item.price) * parseFloat(item.quantity)), 0);
    const itemDiscounts = formData.items.reduce((acc, item) => acc + (parseFloat(item.discount) * parseFloat(item.quantity)), 0);
    const globalDiscount = parseFloat(formData.discount) || 0;
    const globalTax = parseFloat(formData.tax) || 0;

    // Grand total = (SubTotal - itemDiscounts - globalDiscount) + globalTax
    const grandTotal = (subTotal - itemDiscounts - globalDiscount) + globalTax;

    return {
      subTotal: subTotal.toFixed(2),
      itemDiscounts: itemDiscounts.toFixed(2),
      globalDiscount: globalDiscount.toFixed(2),
      totalDiscount: (itemDiscounts + globalDiscount).toFixed(2),
      tax: globalTax.toFixed(2),
      grandTotal: grandTotal.toFixed(2),
      totalQty: formData.items.reduce((acc, item) => acc + parseFloat(item.quantity), 0)
    };
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (formData.items.length === 0) {
      setError('Please add at least one item to the invoice');
      return;
    }

    // Walk-in logic handling
    let customerName = formData.customer_name.trim();
    if (!customerName) {
      customerName = 'Walk-in Customer';
    }

    try {
      setLoading(true);
      setError('');
      const { subTotal, totalDiscount, tax, grandTotal } = calculateTotals();

      const payload = {
        ...formData,
        customer_name: customerName,
        customer_id: formData.customer_id || null, // null if walk-in
        sub_total: subTotal,
        discount: totalDiscount,
        tax: tax,
        grand_total: grandTotal
      };

      await createSale(payload);

      Swal.fire({
        icon: 'success',
        title: 'Invoice Created!',
        text: 'The sales invoice has been successfully recorded.',
        timer: 2000,
        showConfirmButton: false
      });

      setTimeout(() => onBack(), 2000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to save Invoice');
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
            <h1 className="page-title">Sales Invoice</h1>
            <p className="page-subtitle">Record a new POS sale transaction</p>
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit}>
        {/* Main Info Card */}
        <div className="card mb-4 shadow-sm">
          <div className="card-header">
            <div className="card-title"><FiFileText /> Invoice Details</div>
          </div>
          <div className="card-body">
            <div className="form-grid form-grid-4" style={{ rowGap: '20px' }}>
              <div className="form-group span-2">
                <label className="form-label">Customer</label>
                <div style={{ display: 'flex', gap: '10px' }}>
                  <button
                    type="button"
                    className="selection-trigger"
                    onClick={() => setShowCustomerModal(true)}
                    style={{ flex: 1 }}
                  >
                    <span className={formData.customer_id || formData.customer_name ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                      <FiUser style={{ marginRight: 8, opacity: 0.7 }} />
                      {getCustomerName()}
                    </span>
                    <FiSearch className="trigger-icon" />
                  </button>
                  {formData.customer_id && (
                    <button type="button" className="btn btn-secondary" onClick={() => setFormData(prev => ({ ...prev, customer_id: '', customer_name: '', customer_mobile: '', customer_address: '' }))}>
                      Clear
                    </button>
                  )}
                </div>
              </div>
              <div className="form-group">
                <label className="form-label">Customer Name (Manual)</label>
                <input
                  type="text"
                  className="form-input"
                  value={formData.customer_name}
                  onChange={(e) => setFormData(prev => ({ ...prev, customer_name: e.target.value, customer_id: '' }))}
                  placeholder="Walk-in Customer Name"
                  disabled={formData.customer_id !== ''}
                />
              </div>

              <div className="form-group">
                <label className="form-label">Department *</label>
                <button
                  type="button"
                  className="selection-trigger"
                  onClick={() => setShowDepartmentModal(true)}
                  style={{ borderColor: !formData.department_id ? 'var(--danger)' : '' }}
                >
                  <span className={formData.department_id ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                    <FiMapPin style={{ marginRight: 8, opacity: 0.7 }} />
                    {getDepartmentName()}
                  </span>
                  <FiSearch className="trigger-icon" />
                </button>
              </div>

              <div className="form-group">
                <label className="form-label">Date *</label>
                <div className="input-with-icon">
                  <FiCalendar className="input-icon" />
                  <input
                    type="date"
                    className="form-input"
                    value={formData.invoice_date}
                    onChange={(e) => setFormData(prev => ({ ...prev, invoice_date: e.target.value }))}
                    required
                  />
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">Payment Type *</label>
                <button
                  type="button"
                  className="selection-trigger"
                  onClick={() => setShowPaymentTypeModal(true)}
                >
                  <span className="selection-trigger-value">
                    <FiCreditCard style={{ marginRight: 8, opacity: 0.7 }} />
                    {getPaymentTypeName()}
                  </span>
                  <FiSearch className="trigger-icon" />
                </button>
              </div>

              <div className="form-group span-2">
                <label className="form-label">Remarks</label>
                <input
                  type="text"
                  className="form-input"
                  value={formData.remark}
                  onChange={(e) => setFormData(prev => ({ ...prev, remark: e.target.value }))}
                  placeholder="Optional transaction notes"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Item Entry Card */}
        <div className="card mb-4 shadow-sm" style={{ borderTop: '3px solid var(--primary)' }}>
          <div className="card-header" style={{ backgroundColor: 'var(--primary-light)' }}>
            <div className="card-title" style={{ color: 'var(--primary)' }}>
              <FiShoppingBag /> Add Products
            </div>
          </div>
          <div className="card-body">
            <div className="form-grid" style={{ gridTemplateColumns: 'minmax(250px, 2fr) 1fr 1fr 1fr 1.5fr auto', gap: '15px', alignItems: 'end' }}>

              <div className="form-group">
                <label className="form-label" style={{ fontSize: '13px' }}>Scan or Select Product *</label>
                <button
                  type="button"
                  className="selection-trigger"
                  onClick={() => setShowProductModal(true)}
                  style={{ height: '42px' }}
                >
                  <span className={currentItem.item_code ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                    <FiSearch style={{ marginRight: 8, opacity: 0.5 }} />
                    {currentItem.item_code ? `${currentItem.item_code} - ${currentItem.item_name}` : 'Search product...'}
                  </span>
                </button>
              </div>

              <div className="form-group">
                <label className="form-label" style={{ fontSize: '13px' }}>Price</label>
                <input
                  type="number"
                  className="form-input"
                  value={currentItem.price}
                  onChange={(e) => setCurrentItem(prev => ({ ...prev, price: e.target.value }))}
                  readOnly
                  style={{ height: '42px', color: 'var(--text-light)' }}
                />
              </div>

              <div className="form-group">
                <label className="form-label" style={{ fontSize: '13px' }}>Qty *</label>
                <input
                  type="number"
                  className="form-input"
                  value={currentItem.quantity}
                  onChange={(e) => setCurrentItem(prev => ({ ...prev, quantity: e.target.value }))}
                  min="0.01" step="0.01"
                  style={{ height: '42px', fontWeight: 'bold' }}
                />
              </div>

              <div className="form-group">
                <label className="form-label" style={{ fontSize: '13px' }}>Discount (Amt)</label>
                <input
                  type="number"
                  className="form-input"
                  value={currentItem.discount}
                  onChange={(e) => setCurrentItem(prev => ({ ...prev, discount: e.target.value }))}
                  min="0" step="0.01"
                  style={{ height: '42px' }}
                />
              </div>

              <div className="form-group">
                <label className="form-label" style={{ fontSize: '13px' }}>Unit Total</label>
                <div style={{ height: '42px', display: 'flex', alignItems: 'center', fontWeight: '700', fontSize: '16px', color: 'var(--primary)', padding: '0 12px', background: 'var(--primary-light)', borderRadius: '6px' }}>
                  {currentItem.unit_total}
                </div>
              </div>

              <div className="form-group">
                <button
                  type="button"
                  className="btn btn-primary"
                  onClick={addItem}
                  style={{ height: '42px', width: '100%' }}
                >
                  <FiPlus style={{ marginRight: '4px' }} /> Add
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Invoice Summary Area */}
        <div className="form-grid" style={{ gridTemplateColumns: '3fr 1.5fr', gap: '24px' }}>

          {/* Item List */}
          <div className="card shadow-sm">
            <div className="table-responsive">
              <table className="table">
                <thead>
                  <tr>
                    <th style={{ width: '40px' }}>#</th>
                    <th>Product details</th>
                    <th className="text-right">Price</th>
                    <th className="text-center">Qty</th>
                    <th className="text-right">Disc.</th>
                    <th className="text-right">Total</th>
                    <th style={{ width: '60px' }}></th>
                  </tr>
                </thead>
                <tbody>
                  {formData.items.length === 0 ? (
                    <tr>
                      <td colSpan="7" className="text-center" style={{ padding: '40px 20px', color: '#94a3b8' }}>
                        <FiShoppingCart style={{ fontSize: '48px', opacity: 0.2, marginBottom: '12px', display: 'inline-block' }} />
                        <p>No items added yet. Scan or search above to start.</p>
                      </td>
                    </tr>
                  ) : (
                    formData.items.map((item, index) => (
                      <tr key={item.id} className="animate-fade-in">
                        <td className="text-secondary">{index + 1}</td>
                        <td>
                          <div className="fw-bold">{item.item_code}</div>
                          <div className="text-sm text-secondary">{item.item_name}</div>
                        </td>
                        <td className="text-right font-mono">{parseFloat(item.price).toFixed(2)}</td>
                        <td className="text-center">
                          <span className="badge" style={{ fontSize: '13px' }}>
                            {item.quantity}
                          </span>
                        </td>
                        <td className="text-right font-mono text-danger">
                          {item.discount > 0 ? parseFloat(item.discount).toFixed(2) : '-'}
                        </td>
                        <td className="text-right font-mono fw-bold text-primary">
                          {item.unit_total}
                        </td>
                        <td className="text-right">
                          <button
                            type="button"
                            className="btn btn-icon danger-icon"
                            onClick={() => removeItem(item.id)}
                            style={{ background: '#fee2e2', color: '#ef4444' }}
                          >
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

          {/* Grand Totals */}
          <div className="card shadow-sm">
            <div className="card-body d-flex flex-column" style={{ height: '100%' }}>

              <div style={{ flex: 1 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '12px', fontSize: '15px' }}>
                  <span className="text-secondary">Total Items:</span>
                  <span className="fw-bold">{formData.items.length} ({totals.totalQty} qty)</span>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '16px', fontSize: '15px' }}>
                  <span className="text-secondary">Sub Total:</span>
                  <span className="font-mono fw-bold">{totals.subTotal}</span>
                </div>

                <div style={{ borderTop: '1px dashed #cbd5e1', paddingTop: '16px', marginBottom: '16px' }}>
                  <div className="form-group" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '12px' }}>
                    <label className="form-label mb-0">Global Discount</label>
                    <input
                      type="number"
                      className="form-input text-right font-mono"
                      style={{ width: '120px', padding: '4px 8px', height: '32px' }}
                      value={formData.discount}
                      onChange={(e) => setFormData(prev => ({ ...prev, discount: e.target.value }))}
                    />
                  </div>

                  <div className="form-group" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <label className="form-label mb-0">Tax</label>
                    <input
                      type="number"
                      className="form-input text-right font-mono"
                      style={{ width: '120px', padding: '4px 8px', height: '32px' }}
                      value={formData.tax}
                      onChange={(e) => setFormData(prev => ({ ...prev, tax: e.target.value }))}
                    />
                  </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '16px', fontSize: '15px', color: 'var(--danger)' }}>
                  <span>Total Discount:</span>
                  <span className="font-mono fw-bold">-{totals.totalDiscount}</span>
                </div>
              </div>

              <div style={{
                background: 'var(--primary)',
                color: 'white',
                padding: '20px',
                borderRadius: '12px',
                marginTop: '16px',
                boxShadow: '0 10px 20px -5px rgba(var(--primary-rgb), 0.4)'
              }}>
                <div style={{ fontSize: '14px', opacity: 0.8, marginBottom: '4px' }}>GRAND TOTAL</div>
                <div className="font-mono" style={{ fontSize: '32px', fontWeight: '800', lineHeight: 1 }}>
                  Rs {totals.grandTotal}
                </div>
              </div>

              {error && <div className="alert alert-danger mt-3" style={{ padding: '10px', fontSize: '14px' }}>{error}</div>}
              {success && <div className="alert alert-success mt-3" style={{ padding: '10px', fontSize: '14px' }}>{success}</div>}

              <button
                type="submit"
                className="btn btn-primary mt-3"
                disabled={loading || formData.items.length === 0}
                style={{ width: '100%', height: '54px', fontSize: '16px', fontWeight: 'bold' }}
              >
                {loading ? 'Processing...' : (
                  <><FiCheckCircle style={{ marginRight: '8px', fontSize: '20px' }} /> COMPLETE SALE</>
                )}
              </button>
            </div>
          </div>
        </div>
      </form>

      {/* Modals */}
      <SearchableSelectModal
        isOpen={showCustomerModal}
        title="Select Customer"
        data={customers}
        onClose={() => setShowCustomerModal(false)}
        onSelect={(customer) => {
          handleCustomerSelect(customer);
          setShowCustomerModal(false);
        }}
        searchFields={['code', 'name', 'mobile_number']}
        renderItem={(c) => `[${c.code}] ${c.name} ${c.mobile_number ? `(${c.mobile_number})` : ''}`}
      />

      <SearchableSelectModal
        isOpen={showDepartmentModal}
        title="Select Department"
        data={departments}
        onClose={() => setShowDepartmentModal(false)}
        onSelect={(dept) => {
          setFormData(prev => ({ ...prev, department_id: dept.id }));
          setShowDepartmentModal(false);
        }}
        renderItem={(d) => d.name}
      />

      <SearchableSelectModal
        isOpen={showPaymentTypeModal}
        title="Select Payment Type"
        data={[
          { id: '1', name: 'Cash Sale' },
          { id: '2', name: 'Credit Sale' }
        ]}
        onClose={() => setShowPaymentTypeModal(false)}
        onSelect={(type) => {
          setFormData(prev => ({ ...prev, payment_type: type.id }));
          setShowPaymentTypeModal(false);
        }}
        renderItem={(t) => t.name}
        searchFields={['name']}
      />

      <SearchableSelectModal
        isOpen={showProductModal}
        title="Select Product"
        data={products}
        onClose={() => setShowProductModal(false)}
        onSelect={(product) => {
          handleProductSelect(product);
          setShowProductModal(false);
        }}
        searchFields={['barcode', 'code', 'name']}
        renderItem={(p) => `[${p.code}] ${p.name} - Rs.${p.invoice_price || p.retail_price || 0}`}
      />
    </div>
  );
};

export default Invoice;
