import { useState, useEffect, useCallback } from 'react';
import { FiSave, FiSearch, FiTruck, FiList, FiPlus, FiPhone, FiMail, FiMapPin, FiCreditCard, FiUser, FiMessageSquare, FiX } from 'react-icons/fi';
import { getSuppliers, createSupplier, updateSupplier } from '../services/api';
import Swal from 'sweetalert2';

const initialForm = {
  id: null,
  code: '',
  name: '',
  address: '',
  mobile_number: '',
  mobile_number_2: '',
  email: '',
  contact_person: '',
  contact_person_number: '',
  credit_limit: '',
  outstanding: '',
  is_active: true,
  remark: '',
};

const SupplierMaster = () => {
  const [suppliers, setSuppliers] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showList, setShowList] = useState(false);
  const [formData, setFormData] = useState(initialForm);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      const res = await getSuppliers();
      setSuppliers(res.data.data || []);
    } catch (err) {
      console.error('Fetch error:', err);
      Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load suppliers' });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleInput = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
  };

  const selectSupplier = (s) => {
    setFormData({
      id: s.id,
      code: s.code || '',
      name: s.name || '',
      address: s.address || '',
      mobile_number: s.mobile_number || '',
      mobile_number_2: s.mobile_number_2 || '',
      email: s.email || '',
      contact_person: s.contact_person || '',
      contact_person_number: s.contact_person_number || '',
      credit_limit: s.credit_limit || '',
      outstanding: s.outstanding || '',
      is_active: s.is_active !== undefined ? Boolean(s.is_active) : true,
      remark: s.remark || '',
    });
    setShowList(false);
  };

  const handleSave = async () => {
    if (!formData.name || !formData.address || !formData.mobile_number) {
      Swal.fire({ icon: 'error', title: 'Missing Fields', text: 'Name, address and mobile number are required' });
      return;
    }
    setSaving(true);
    try {
      if (formData.id) await updateSupplier(formData.id, formData);
      else await createSupplier(formData);
      await Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Supplier saved successfully',
        confirmButtonColor: '#4F46E5',
      });
      window.location.reload();
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save supplier. Please try again.' });
    } finally {
      setSaving(false);
    }
  };

  const filtered = suppliers.filter(s =>
    s.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    s.code.toLowerCase().includes(searchTerm.toLowerCase())
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
          <h1 className="page-title">Supplier Master</h1>
          <p className="page-subtitle">Manage your vendor database and credit terms</p>
        </div>
        <div className="page-actions">
          <button className="btn btn-secondary" onClick={() => setShowList(v => !v)}>
            <FiList /> {showList ? 'Hide List' : 'Browse Suppliers'}
          </button>
          <button className="btn btn-secondary" onClick={() => setFormData(initialForm)}>
            <FiPlus /> New Supplier
          </button>
          <button className="btn btn-primary" onClick={handleSave} disabled={saving}>
            {saving ? (
              <><div className="spinner" style={{ width: 14, height: 14, borderWidth: 2 }} /> Saving...</>
            ) : (
              <><FiSave /> Save Supplier</>
            )}
          </button>
        </div>
      </div>

      {/* Supplier Browser Modal */}
      {showList && (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && setShowList(false)}>
          <div className="modal modal-lg">
            <div className="modal-header">
              <div className="modal-title">
                <FiList />
                Browse Suppliers
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
                {filtered.length > 0 ? filtered.map(s => (
                  <div key={s.id} className="list-item" onClick={() => selectSupplier(s)} style={{ cursor: 'pointer' }}>
                    <div className="list-item-name">{s.name}</div>
                    <div className="list-item-meta">
                      <span className="badge badge-primary">{s.code || 'NO CODE'}</span>
                      <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                        <FiPhone size={10} /> {s.mobile_number}
                      </span>
                    </div>
                  </div>
                )) : (
                  <div className="empty-state" style={{ gridColumn: '1 / -1', padding: 40 }}>
                    <div className="empty-state-title">No suppliers found</div>
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

      {/* Form */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {/* General Info */}
        <div className="card">
          <div className="card-header">
            <div className="card-title"><FiTruck /> General Information</div>
          </div>
          <div className="card-body">
            <div className="form-grid form-grid-4" style={{ rowGap: 16 }}>
              <div className="form-group">
                <label className="form-label">Supplier Code</label>
                <input
                  className="form-input"
                  type="text"
                  name="code"
                  value={formData.code}
                  readOnly
                  placeholder="Auto-assigned"
                  style={{ fontWeight: 700, color: 'var(--primary)' }}
                />
              </div>
              <div className="form-group span-3">
                <label className="form-label">Supplier / Company Name *</label>
                <input
                  className="form-input"
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleInput}
                  placeholder="Official business name"
                />
              </div>
              <div className="form-group span-3">
                <label className="form-label"><FiMapPin size={12} style={{ marginRight: 4 }} />Registered Address *</label>
                <input
                  className="form-input"
                  type="text"
                  name="address"
                  value={formData.address}
                  onChange={handleInput}
                  placeholder="Street address, City, Country"
                />
              </div>
              <div className="form-group" style={{ display: 'flex', alignItems: 'flex-end' }}>
                <label
                  className="form-checkbox"
                  style={{
                    width: '100%',
                    borderColor: formData.is_active ? 'var(--success)' : 'var(--border)',
                    background: formData.is_active ? 'var(--success-light)' : '',
                    color: formData.is_active ? 'var(--success)' : 'var(--text-primary)',
                    fontWeight: formData.is_active ? 700 : 400,
                  }}
                >
                  <input type="checkbox" name="is_active" checked={formData.is_active} onChange={handleInput} />
                  {formData.is_active ? 'Active Supplier' : 'Inactive'}
                </label>
              </div>
            </div>
          </div>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: '1.5fr 1fr', gap: 16 }}>
          {/* Contact Details */}
          <div className="card">
            <div className="card-header">
              <div className="card-title"><FiUser /> Contact Details</div>
            </div>
            <div className="card-body">
              <div className="form-grid form-grid-2" style={{ rowGap: 16 }}>
                <div className="form-group">
                  <label className="form-label"><FiPhone size={12} style={{ marginRight: 4 }} />Primary Mobile *</label>
                  <input className="form-input" type="tel" name="mobile_number" value={formData.mobile_number} onChange={handleInput} />
                </div>
                <div className="form-group">
                  <label className="form-label"><FiPhone size={12} style={{ marginRight: 4 }} />Secondary Phone</label>
                  <input className="form-input" type="tel" name="mobile_number_2" value={formData.mobile_number_2} onChange={handleInput} />
                </div>
                <div className="form-group span-2">
                  <label className="form-label"><FiMail size={12} style={{ marginRight: 4 }} />Email Address</label>
                  <input className="form-input" type="email" name="email" value={formData.email} onChange={handleInput} placeholder="contact@supplier.com" />
                </div>
                <div className="form-divider" />
                <div className="form-group">
                  <label className="form-label">Contact Person</label>
                  <input className="form-input" type="text" name="contact_person" value={formData.contact_person} onChange={handleInput} />
                </div>
                <div className="form-group">
                  <label className="form-label">Direct Line</label>
                  <input className="form-input" type="tel" name="contact_person_number" value={formData.contact_person_number} onChange={handleInput} />
                </div>
              </div>
            </div>
          </div>

          {/* Financials */}
          <div className="card">
            <div className="card-header">
              <div className="card-title"><FiCreditCard /> Financial Terms</div>
            </div>
            <div className="card-body" style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
              <div className="form-group">
                <label className="form-label">Credit Limit (LKR)</label>
                <input
                  className="form-input"
                  type="number"
                  name="credit_limit"
                  value={formData.credit_limit}
                  onChange={handleInput}
                  placeholder="0.00"
                  style={{ fontSize: 20, fontWeight: 800, color: 'var(--warning)', textAlign: 'right' }}
                />
              </div>
              <div className="form-group">
                <label className="form-label">Current Outstanding</label>
                <input
                  className="form-input"
                  type="number"
                  name="outstanding"
                  value={formData.outstanding}
                  readOnly
                  placeholder="0.00"
                  style={{ fontSize: 20, fontWeight: 800, color: 'var(--danger)', textAlign: 'right' }}
                />
              </div>
              <div className="form-group">
                <label className="form-label"><FiMessageSquare size={12} style={{ marginRight: 4 }} />Remarks</label>
                <textarea
                  className="form-textarea"
                  name="remark"
                  value={formData.remark}
                  onChange={handleInput}
                  rows={4}
                  placeholder="Payment terms, delivery notes..."
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SupplierMaster;
