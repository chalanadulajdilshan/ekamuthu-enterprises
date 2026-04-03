import { useState, useEffect, useCallback } from 'react';
import { FiSave, FiSearch, FiTruck, FiArrowLeft, FiList, FiPlus, FiSun, FiMoon, FiCheckSquare, FiInfo, FiTag, FiGlobe, FiMessageSquare } from 'react-icons/fi';
import { getBrands, getBrandCategories, createBrand, updateBrand } from '../services/api';
import toast from 'react-hot-toast';

const BrandMaster = ({ onNavigate, theme, toggleTheme }) => {
  const [brands, setBrands] = useState([]);
  const [categories, setCategories] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showList, setShowList] = useState(false);
  
  const initialForm = {
    id: null,
    category_id: '',
    name: '',
    country_id: '',
    is_active: true,
    remark: ''
  };
  
  const [formData, setFormData] = useState(initialForm);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      const [brandRes, catRes] = await Promise.all([
        getBrands(),
        getBrandCategories()
      ]);
      setBrands(brandRes.data.data || []);
      setCategories(catRes.data.data || []);
    } catch (err) {
      console.error('Failed to fetch brand data:', err);
      toast.error('Failed to load brand data');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
  };

  const selectBrand = (b) => {
    setFormData({
      id: b.id,
      category_id: b.category_id || '',
      name: b.name || '',
      country_id: b.country_id || '',
      is_active: b.is_active !== undefined ? Boolean(b.is_active) : true,
      remark: b.remark || ''
    });
    setShowList(false);
  };

  const handleSave = async () => {
    if (!formData.name || !formData.category_id) {
      return toast.error('Brand Name and Category are required!');
    }
    setSaving(true);
    try {
      if (formData.id) await updateBrand(formData.id, formData);
      else await createBrand(formData);
      toast.success('Brand saved successfully!');
      fetchData();
      if (!formData.id) setFormData(initialForm);
    } catch (err) {
      toast.error('Failed to save brand');
    } finally {
      setSaving(false);
    }
  };

  const filteredBrands = brands.filter(b => 
    b.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div style={{height: '100vh', overflowY: 'auto', backgroundColor: 'var(--bg-main)', color: 'var(--text-main)', display: 'flex', flexDirection: 'column'}}>
      {/* Premium Header */}
      <div style={{height: 72, borderBottom: '1px solid var(--border-color)', backgroundColor: 'var(--bg-panel)', display: 'flex', alignItems: 'center', padding: '0 32px', justifyContent: 'space-between', boxShadow: 'var(--shadow-sm)', zIndex: 10}}>
         <div style={{display: 'flex', alignItems: 'center', gap: 32}}>
            <button className="pos-back-btn" onClick={() => onNavigate('dashboard')} style={{border: 'none', background: 'transparent', borderRight: '1px solid var(--border-color)', paddingRight: 20, borderRadius: 0, height: 40}}><FiArrowLeft /> Dashboard</button>
            <div style={{color: 'var(--accent-primary)', fontWeight: 700, fontSize: '18px', display: 'flex', alignItems: 'center', gap: 10}}><FiTag size={22} /> Brand Manager</div>
         </div>
         <div style={{display: 'flex', alignItems: 'center', gap: 16}}>
            <button onClick={toggleTheme} className="pos-header-btn">
               {theme === 'light' ? <FiMoon /> : <FiSun />}
            </button>
         </div>
      </div>

      <div style={{padding: 40, maxWidth: 1200, margin: '0 auto', width: '100%', flex: 1, backgroundColor: 'var(--bg-primary)'}}>
         {/* Toolbar Area */}
         <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: 32}}>
            <div>
                <h1 style={{margin: 0, fontSize: '28px', fontWeight: 800}}>Product Brands</h1>
                <p style={{color: 'var(--text-muted)', marginTop: 4, fontSize: '15px'}}>Categorize and manage your product labels and origins</p>
            </div>
            <div style={{display: 'flex', gap: 12}}>
               <button className="im-btn secondary" onClick={() => setShowList(!showList)} style={{height: 44}}><FiList /> {showList ? 'Hide List' : 'Browse Brands'}</button>
               <button className="im-btn secondary" onClick={() => setFormData(initialForm)} style={{height: 44}}><FiPlus /> New Brand</button>
               <button className="im-btn primary" onClick={handleSave} disabled={saving} style={{height: 44, padding: '0 24px', background: 'linear-gradient(135deg, var(--accent-primary), #a855f7)', boxShadow: '0 4px 12px rgba(99, 102, 241, 0.3)'}}>{saving ? 'Saving...' : <><FiSave /> Save Brand</>}</button>
            </div>
         </div>

         {/* Search Filter Dropdown List */}
         {showList && (
             <div className="im-card" style={{marginBottom: 32, borderColor: 'var(--accent-primary)', borderWidth: '2px'}}>
                 <div className="pos-search" style={{maxWidth: '100%', marginBottom: 20}}>
                    <FiSearch className="pos-search-icon" /><input type="text" placeholder="Search brands..." value={searchTerm} onChange={e => setSearchTerm(e.target.value)} style={{height: 48, fontSize: '16px'}} />
                 </div>
                 <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: 16, maxHeight: 400, overflowY: 'auto', paddingRight: 8}}>
                     {filteredBrands.map(b => (
                         <div key={b.id} onClick={() => selectBrand(b)} style={{padding: 16, border: '1px solid var(--border-color)', borderRadius: 12, cursor: 'pointer', backgroundColor: 'var(--bg-main)', transition: 'all 0.2s', borderLeft: '4px solid var(--accent-primary)'}}>
                            <div style={{fontWeight: 700, color: 'var(--text-primary)', fontSize: '15px', marginBottom: 4}}>{b.name}</div>
                            <div style={{color: 'var(--text-muted)', fontSize: 11, display: 'flex', alignItems: 'center', gap: 6}}>
                                <FiTag size={10} /> {categories.find(c => c.id === b.category_id)?.name || 'Unknown Category'}
                            </div>
                         </div>
                     ))}
                     {filteredBrands.length === 0 && (
                         <div style={{gridColumn: '1/-1', textAlign: 'center', padding: 40, color: 'var(--text-muted)'}}>No brands matching your search.</div>
                     )}
                 </div>
             </div>
         )}

         {/* Main Form Section */}
         <div className="im-card">
            <h3 className="im-card-title"><FiInfo /> Brand Details</h3>
            <div className="im-grid-4">
                <div className="im-form-group span-2"><label><FiTag size={14}/> Brand Name *</label><input type="text" name="name" value={formData.name} onChange={handleInputChange} placeholder="Enter brand name" style={{fontSize: '16px', fontWeight: 600}} /></div>
                <div className="im-form-group"><label>Category *</label>
                    <select name="category_id" value={formData.category_id} onChange={handleInputChange}>
                        <option value="">-- Choose --</option>
                        {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                </div>
                <div className="im-form-group"><label><FiGlobe size={14}/> Country of Origin</label><input type="text" name="country_id" value={formData.country_id} onChange={handleInputChange} placeholder="e.g. USA, UK" /></div>
                
                <div className="im-form-group span-3">
                    <label><FiMessageSquare size={14} /> Description / Remarks</label>
                    <textarea name="remark" value={formData.remark} onChange={handleInputChange} rows="3" style={{width: '100%', padding: '16px', borderRadius: '12px', border: '1px solid var(--border-color)', backgroundColor: 'var(--bg-main)', color: 'var(--text-primary)', fontSize: '14px', resize: 'none', transition: 'var(--transition)'}} placeholder="Add brand notes..."></textarea>
                </div>
                <div className="im-form-group" style={{display: 'flex', alignItems: 'flex-end', paddingBottom: 10}}>
                    <label className="im-checkbox" style={{padding: '12px 16px', border: '1px solid var(--border-color)', borderRadius: '8px', width: '100%', background: formData.is_active ? 'rgba(34, 197, 94, 0.05)' : 'transparent', borderColor: formData.is_active ? 'var(--accent-success)' : 'var(--border-color)'}}>
                        <input type="checkbox" name="is_active" checked={formData.is_active} onChange={handleInputChange} style={{width: 20, height: 20}} /> 
                        <span style={{fontWeight: 700, color: formData.is_active ? 'var(--accent-success)' : 'var(--text-muted)'}}>{formData.is_active ? 'Active Brand' : 'Inactive'}</span>
                    </label>
                </div>
            </div>
         </div>
      </div>
    </div>
  );
};

export default BrandMaster;
