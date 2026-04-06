import { useState, useEffect, useCallback, useRef } from 'react';
import { FiSave, FiSearch, FiPackage, FiList, FiPlus, FiCamera, FiX, FiSettings, FiTrash2, FiMapPin, FiTag, FiTruck } from 'react-icons/fi';
import { 
  getProducts, createProduct, updateProduct, deleteProduct, 
  getDepartments, getBrands 
} from '../services/api';
import SearchableSelectModal from './SearchableSelectModal';
import Swal from 'sweetalert2';

const initialForm = {
  id: null,
  code: '',
  name: '',
  barcode: '',
  category: '',
  brand: '',
  note: '',
  reminder_note: '',
  cost_price: '',
  net_price: '',
  tax_type: 'T0-0',
  retail_price: '',
  min_stock: '',
  max_stock: '',
  pack_qty: '1',
  image: null,
  image_url: null,
};

const ItemMaster = () => {
  const [products, setProducts] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [brands, setBrands] = useState([]);
  
  // Modal States
  const [showDeptModal, setShowDeptModal] = useState(false);
  const [showBrandModal, setShowBrandModal] = useState(false);
  const [showTaxModal, setShowTaxModal] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showList, setShowList] = useState(false);
  const [formData, setFormData] = useState(initialForm);
  const fileInputRef = useRef(null);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      const [prodRes, deptRes, brandRes] = await Promise.all([
        getProducts({ all: 'true' }),
        getDepartments(),
        getBrands()
      ]);
      setProducts(prodRes.data?.data || (Array.isArray(prodRes.data) ? prodRes.data : []));
      setDepartments(deptRes.data?.data || (Array.isArray(deptRes.data) ? deptRes.data : []));
      setBrands(brandRes.data?.data || (Array.isArray(brandRes.data) ? brandRes.data : []));
    } catch (err) {
      console.error('ItemMaster fetch error:', err);
      Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load data. ' + (err.response?.data?.message || err.message) });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleInput = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
      Swal.fire({ icon: 'error', title: 'Image too large', text: 'Image must be under 2 MB' });
      return;
    }
    const reader = new FileReader();
    reader.onloadend = () => setFormData(prev => ({ ...prev, image: reader.result, image_url: null }));
    reader.readAsDataURL(file);
  };

  const removeImage = (e) => {
    e.stopPropagation();
    setFormData(prev => ({ ...prev, image: null, image_url: null }));
  };

  const selectProduct = (p) => {
    setFormData({
      ...initialForm,
      id: p.id,
      code: p.code || '',
      name: p.name || '',
      barcode: p.barcode || '',
      category: p.category || '',
      brand: p.brand || '',
      cost_price: p.list_price || '',
      retail_price: p.invoice_price || '',
      note: p.note || '',
      reminder_note: p.reminder_note || '',
      min_stock: p.re_order_level || '',
      pack_qty: p.re_order_qty || '1',
      image_url: p.image_file ? `/uploads/${p.image_file}` : null,
    });
    setShowList(false);
  };

  const getDeptName = () => {
    const d = departments.find(d => d.id === parseInt(formData.category));
    return d ? d.name : 'Select Department';
  };

  const getBrandName = () => {
    const b = brands.find(b => b.id === parseInt(formData.brand));
    return b ? b.name : 'Select Brand';
  };

  const handleSave = async () => {
    if (!formData.name || !formData.code || !formData.category) {
      Swal.fire({ icon: 'error', title: 'Missing Fields', text: 'Code, name and department are required' });
      return;
    }
    setSaving(true);
    try {
      const payload = {
        code: formData.code,
        name: formData.name,
        category: formData.category,
        brand: formData.brand,
        list_price: formData.cost_price,
        net_price: formData.net_price,
        tax_type: formData.tax_type,
        invoice_price: formData.retail_price,
        re_order_level: formData.min_stock,
        re_order_qty: formData.pack_qty,
        max_stock: formData.max_stock,
        note: formData.note,
        is_active: true,
        pattern: formData.barcode,
        size: formData.reminder_note,
        image: formData.image,
      };
      if (formData.id) await updateProduct(formData.id, payload);
      else await createProduct(payload);
      await Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Item saved successfully',
        confirmButtonColor: '#4F46E5',
      });
      window.location.reload();
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save item. Please try again.' });
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!formData.id) return;
    const confirmed = await Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#EF4444',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Yes, delete it!'
    });
    if (!confirmed.isConfirmed) return;
    try {
      setSaving(true);
      await deleteProduct(formData.id);
      await Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: 'Item has been deleted successfully',
      });
      window.location.reload();
    } catch (err) {
      console.error('Delete error:', err);
      const msg = err.response?.data?.message || 'Failed to delete item. This usually happens if the item is already used in stock or sales records.';
      Swal.fire({ icon: 'error', title: 'Deletion Failed', text: msg });
    } finally {
      setSaving(false);
    }
  };

  const filtered = products.filter(p =>
    p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    p.code.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner" />
        <span>Loading...</span>
      </div>
    );
  }

  return (
    <div className="page">
      {/* Header Row */}
      <div className="page-header-row">
        <div>
          <h1 className="page-title">Item Master</h1>
          <p className="page-subtitle">Add and manage inventory items with pricing and stock settings</p>
        </div>
        <div className="page-actions">
          <button className="btn btn-secondary" onClick={() => setShowList(v => !v)}>
            <FiList /> {showList ? 'Hide List' : 'Browse Items'}
          </button>
          <button className="btn btn-secondary" onClick={() => setFormData(initialForm)}>
            <FiPlus /> New Item
          </button>
          {formData.id && (
            <button className="btn btn-secondary" onClick={handleDelete} disabled={saving} style={{ color: 'var(--danger)', borderColor: 'var(--danger-light)' }}>
              <FiTrash2 /> Delete
            </button>
          )}
          <button className="btn btn-primary" onClick={handleSave} disabled={saving}>
            {saving ? (
              <><div className="spinner" style={{ width: 14, height: 14, borderWidth: 2 }} /> Processing...</>
            ) : (
              <><FiSave /> {formData.id ? 'Update Item' : 'Save Item'}</>
            )}
          </button>
        </div>
      </div>

      {/* Product Browser Modal */}
      {showList && (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && setShowList(false)}>
          <div className="modal modal-lg">
            <div className="modal-header">
              <div className="modal-title">
                <FiPackage />
                Browse Items
              </div>
              <button className="modal-close" onClick={() => setShowList(false)}>
                <FiX />
              </button>
            </div>

            <div className="modal-body">
              <div className="search-wrapper" style={{ marginBottom: 16 }}>
                <FiSearch className="search-icon" />
                <input
                  className="search-input"
                  type="text"
                  placeholder="Search by name or code..."
                  value={searchTerm}
                  onChange={e => setSearchTerm(e.target.value)}
                />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: 12, maxHeight: '50vh', overflowY: 'auto' }}>
                {filtered.length > 0 ? filtered.map(p => (
                  <div
                    key={p.id}
                    className="list-item"
                    onClick={() => {
                      selectProduct(p);
                      setSearchTerm('');
                    }}
                    style={{ cursor: 'pointer' }}
                  >
                    <div className="list-item-name">{p.name}</div>
                    <div className="list-item-meta">
                      <span className="badge badge-primary">{p.code}</span>
                      <span>Rs. {parseFloat(p.invoice_price || 0).toFixed(2)}</span>
                    </div>
                  </div>
                )) : (
                  <div className="empty-state" style={{ gridColumn: '1 / -1', padding: 40 }}>
                    <div className="empty-state-title">No items found</div>
                  </div>
                )}
              </div>
            </div>

            <div className="modal-footer">
              <button className="btn btn-secondary" onClick={() => setShowList(false)}>
                <FiX /> Close
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Form Area */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 300px', gap: 20, alignItems: 'start' }}>
        {/* Left: Main Form */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          {/* Basic Info */}
          <div className="card">
            <div className="card-header">
              <div className="card-title"><FiPackage /> Basic Information</div>
            </div>
            <div className="card-body">
              <div className="form-grid form-grid-3" style={{ rowGap: 16 }}>
                <div className="form-group span-2">
                  <label className="form-label">Product Code *</label>
                  <input
                    className="form-input"
                    type="text"
                    name="code"
                    value={formData.code}
                    onChange={handleInput}
                    placeholder="e.g. PRD-001"
                    style={{ fontWeight: 700, color: 'var(--primary)' }}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Barcode</label>
                  <input className="form-input" type="text" name="barcode" value={formData.barcode} onChange={handleInput} placeholder="Scan or type" />
                </div>

                <div className="form-group span-2">
                  <label className="form-label">Product Description *</label>
                  <input className="form-input" type="text" name="name" value={formData.name} onChange={handleInput} placeholder="Full product name" />
                </div>
                <div className="form-group">
                  <label className="form-label">Department *</label>
                  <button 
                    type="button"
                    className="selection-trigger" 
                    onClick={() => setShowDeptModal(true)}
                  >
                    <span className={formData.category ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                      <FiMapPin style={{ marginRight: 8 }} />
                      {getDeptName()}
                    </span>
                    <FiSearch className="trigger-icon" />
                  </button>
                </div>

                <div className="form-group span-3">
                  <label className="form-label">Brand</label>
                  <button 
                    type="button"
                    className="selection-trigger" 
                    onClick={() => setShowBrandModal(true)}
                  >
                    <span className={formData.brand ? "selection-trigger-value" : "selection-trigger-placeholder"}>
                      <FiTruck style={{ marginRight: 8 }} />
                      {getBrandName()}
                    </span>
                    <FiSearch className="trigger-icon" />
                  </button>
                </div>

                <div className="form-group span-2">
                  <label className="form-label">Note</label>
                  <input className="form-input" type="text" name="note" value={formData.note} onChange={handleInput} placeholder="Optional notes" />
                </div>
                <div className="form-group">
                  <label className="form-label">Reminder Note</label>
                  <input className="form-input" type="text" name="reminder_note" value={formData.reminder_note} onChange={handleInput} />
                </div>
              </div>
            </div>
          </div>

          {/* Pricing */}
          <div className="card">
            <div className="card-header">
              <div className="card-title">Pricing</div>
            </div>
            <div className="card-body">
              <div className="form-grid form-grid-4" style={{ rowGap: 16 }}>
                <div className="form-group">
                  <label className="form-label">Cost Price</label>
                  <input className="form-input" type="number" name="cost_price" value={formData.cost_price} onChange={handleInput} placeholder="0.00" />
                </div>
                <div className="form-group">
                  <label className="form-label">Net Price</label>
                  <input className="form-input" type="number" name="net_price" value={formData.net_price} onChange={handleInput} placeholder="0.00" />
                </div>
                <div className="form-group">
                  <label className="form-label">Tax</label>
                  <input
                    className="form-input"
                    type="text"
                    name="tax_type"
                    value={formData.tax_type}
                    onChange={handleInput}
                    placeholder="e.g. 0"
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Retail Price</label>
                  <input
                    className="form-input"
                    type="number"
                    name="retail_price"
                    value={formData.retail_price}
                    onChange={handleInput}
                    placeholder="0.00"
                    style={{ fontWeight: 700, color: 'var(--success)' }}
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Stock */}
          <div className="card">
            <div className="card-header">
              <div className="card-title">Stock Levels</div>
            </div>
            <div className="card-body">
              <div className="form-grid form-grid-3" style={{ rowGap: 16 }}>
                <div className="form-group">
                  <label className="form-label">Min Stock</label>
                  <input className="form-input" type="number" name="min_stock" value={formData.min_stock} onChange={handleInput} placeholder="0" />
                </div>
                <div className="form-group">
                  <label className="form-label">Max Stock</label>
                  <input className="form-input" type="number" name="max_stock" value={formData.max_stock} onChange={handleInput} placeholder="0" />
                </div>
                <div className="form-group">
                  <label className="form-label">Pack Qty</label>
                  <input className="form-input" type="number" name="pack_qty" value={formData.pack_qty} onChange={handleInput} placeholder="1" />
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Right Area: Image Upload */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          {/* Image Upload */}
          <div
            className="image-upload"
            onClick={() => fileInputRef.current?.click()}
          >
            {(formData.image || formData.image_url) ? (
              <>
                <img src={formData.image || formData.image_url} alt="Preview" />
                <div className="image-upload-overlay">
                  <button
                    onClick={removeImage}
                    style={{
                      background: 'rgba(0,0,0,0.55)',
                      border: 'none',
                      color: 'white',
                      borderRadius: '50%',
                      width: 28,
                      height: 28,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      cursor: 'pointer',
                    }}
                  >
                    <FiX size={14} />
                  </button>
                </div>
              </>
            ) : (
              <div className="image-upload-placeholder">
                <FiCamera />
                <p>Click to upload image</p>
                <p style={{ fontSize: 11, marginTop: 4, opacity: 0.7 }}>Max 2 MB</p>
              </div>
            )}
            <input
              type="file"
              ref={fileInputRef}
              onChange={handleImageChange}
              accept="image/*"
              style={{ display: 'none' }}
            />
          </div>
        </div>
      </div>
      {/* Modals */}
      <SearchableSelectModal
        isOpen={showDeptModal}
        onClose={() => setShowDeptModal(false)}
        onSelect={(d) => setFormData(prev => ({ ...prev, category: d.id }))}
        data={departments}
        title="Select Department"
        searchPlaceholder="Type department name..."
        renderItem="name"
      />

      <SearchableSelectModal
        isOpen={showBrandModal}
        onClose={() => setShowBrandModal(false)}
        onSelect={(b) => setFormData(prev => ({ ...prev, brand: b.id }))}
        data={brands}
        title="Select Brand / Supplier"
        searchPlaceholder="Type brand name..."
        renderItem="name"
      />

      <SearchableSelectModal
        isOpen={showTaxModal}
        onClose={() => setShowTaxModal(false)}
        onSelect={(t) => setFormData(prev => ({ ...prev, tax_type: t.name }))}
        data={[
          { id: 'T0-0', name: 'T0-0' }
        ]}
        title="Select Tax Type"
        renderItem="name"
      />

    </div>
  );
};

export default ItemMaster;
