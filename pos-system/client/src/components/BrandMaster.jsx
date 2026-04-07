import { useState, useEffect, useCallback } from 'react';
import { FiSave, FiSearch, FiList, FiPlus, FiTag, FiGlobe, FiMessageSquare, FiX } from 'react-icons/fi';
import { getBrands, createBrand, updateBrand } from '../services/api';
import Swal from 'sweetalert2';

const initialForm = {
  id: null,
  name: '',
  country_id: '',
  is_active: true,
  remark: '',
};

const BrandMaster = () => {
  const [brands, setBrands] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showList, setShowList] = useState(false);
  const [formData, setFormData] = useState(initialForm);
  
  // Modal States

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      const [brandRes] = await Promise.all([getBrands()]);
      setBrands(brandRes.data?.data || (Array.isArray(brandRes.data) ? brandRes.data : []));
    } catch (err) {
      console.error('BrandMaster fetch error:', err);
      Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load brands. ' + (err.response?.data?.message || err.message) });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleInput = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
  };

  const selectBrand = (b) => {
    setFormData({
      id: b.id,
      name: b.name || '',
      country_id: b.country_id || '',
      is_active: b.is_active !== undefined ? Boolean(b.is_active) : true,
      remark: b.remark || '',
    });
    setShowList(false);
  };


  const handleSave = async () => {
    if (!formData.name) {
      Swal.fire({ icon: 'error', title: 'Missing Fields', text: 'Brand name is required' });
      return;
    }
    setSaving(true);
    try {
      if (formData.id) await updateBrand(formData.id, formData);
      else await createBrand(formData);
      await Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: `Brand ${formData.id ? 'updated' : 'saved'} successfully`,
        confirmButtonColor: '#4F46E5',
      });
      window.location.reload();
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save brand. Please try again.' });
    } finally {
      setSaving(false);
    }
  };

  const filtered = brands.filter(b =>
    b.name.toLowerCase().includes(searchTerm.toLowerCase())
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
          <h1 className="page-title">Brand Master</h1>
          <p className="page-subtitle">Manage product brands, labels and country of origin</p>
        </div>
        <div className="page-actions">
          <button className="btn btn-secondary" onClick={() => setShowList(v => !v)}>
            <FiList /> {showList ? 'Hide List' : 'Browse Brands'}
          </button>
          <button className="btn btn-secondary" onClick={() => setFormData(initialForm)}>
            <FiPlus /> New Brand
          </button>
          <button className="btn btn-primary" onClick={handleSave} disabled={saving}>
            {saving ? (
              <><div className="spinner" style={{ width: 14, height: 14, borderWidth: 2 }} /> Saving...</>
            ) : (
              <><FiSave /> {formData.id ? 'Update Brand' : 'Save Brand'}</>
            )}
          </button>
        </div>
      </div>

      {/* Brand Browser Modal */}
      {showList && (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && setShowList(false)}>
          <div className="modal modal-lg">
            <div className="modal-header">
              <div className="modal-title">
                <FiList />
                Browse Brands
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
                  placeholder="Search brands..."
                  value={searchTerm}
                  onChange={e => setSearchTerm(e.target.value)}
                />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: 12, maxHeight: '50vh', overflowY: 'auto' }}>
                {filtered.length > 0 ? filtered.map(b => (
                  <div key={b.id} className="list-item" onClick={() => selectBrand(b)} style={{ cursor: 'pointer' }}>
                    <div className="list-item-name">{b.name}</div>
                    <div className="list-item-meta">
                      <span className={`badge ${b.is_active ? 'badge-success' : 'badge-warning'}`}>
                        {b.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </div>
                  </div>
                )) : (
                  <div className="empty-state" style={{ gridColumn: '1 / -1', padding: 40 }}>
                    <div className="empty-state-title">No brands found</div>
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
      <div className="card">
        <div className="card-header">
          <div className="card-title"><FiTag /> Brand Details</div>
        </div>
        <div className="card-body">
          <div className="form-grid form-grid-4" style={{ rowGap: 20 }}>
            <div className="form-group span-2">
              <label className="form-label"><FiTag size={11} style={{ marginRight: 4 }} />Brand Name *</label>
              <input
                className="form-input"
                type="text"
                name="name"
                value={formData.name}
                onChange={handleInput}
                placeholder="Enter brand name"
                style={{ fontSize: 15, fontWeight: 600 }}
              />
            </div>


            <div className="form-group">
              <label className="form-label"><FiGlobe size={11} style={{ marginRight: 4 }} />Country of Origin</label>
              <input
                className="form-input"
                type="text"
                name="country_id"
                value={formData.country_id}
                onChange={handleInput}
                placeholder="e.g. Sri Lanka, USA"
              />
            </div>

            <div className="form-group span-3">
              <label className="form-label"><FiMessageSquare size={11} style={{ marginRight: 4 }} />Description / Remarks</label>
              <textarea
                className="form-textarea"
                name="remark"
                value={formData.remark}
                onChange={handleInput}
                rows={3}
                placeholder="Add brand notes or description..."
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
                {formData.is_active ? 'Active Brand' : 'Inactive'}
              </label>
            </div>
          </div>
        </div>
      </div>
      {/* Modals */}

    </div>
  );
};

export default BrandMaster;
