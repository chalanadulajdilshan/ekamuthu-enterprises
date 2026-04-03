import { useState, useEffect, useCallback } from 'react';
import { FiSave, FiSearch, FiTruck, FiArrowLeft, FiList, FiPlus, FiSun, FiMoon, FiCheckSquare, FiUser, FiPhone, FiMail, FiMapPin, FiCreditCard, FiInfo, FiMessageSquare } from 'react-icons/fi';
import { getSuppliers, createSupplier, updateSupplier } from '../services/api';
import toast from 'react-hot-toast';

const SupplierMaster = ({ onNavigate, theme, toggleTheme }) => {
  const [suppliers, setSuppliers] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showList, setShowList] = useState(false);
  
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
    credit_limit: '0.00',
    outstanding: '0.00',
    is_active: true,
    remark: ''
  };
  
  const [formData, setFormData] = useState(initialForm);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      const res = await getSuppliers();
      setSuppliers(res.data.data || []);
    } catch (err) {
      console.error('Failed to fetch suppliers:', err);
      toast.error('Failed to load supplier data');
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
      credit_limit: s.credit_limit || '0.00',
      outstanding: s.outstanding || '0.00',
      is_active: s.is_active !== undefined ? Boolean(s.is_active) : true,
      remark: s.remark || ''
    });
    setShowList(false);
  };

  const handleSave = async () => {
    if (!formData.name || !formData.address || !formData.mobile_number) {
      return toast.error('Name, Address, and Mobile Number are required!');
    }
    setSaving(true);
    try {
      if (formData.id) await updateSupplier(formData.id, formData);
      else await createSupplier(formData);
      toast.success('Supplier saved successfully!');
      fetchData();
      if (!formData.id) setFormData(initialForm);
    } catch (err) {
      toast.error('Failed to save supplier');
    } finally {
      setSaving(false);
    }
  };

  const filteredSuppliers = suppliers.filter(s => 
    s.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
    s.code.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div style={{height: '100vh', overflowY: 'auto', backgroundColor: 'var(--bg-main)', color: 'var(--text-main)', display: 'flex', flexDirection: 'column'}}>
      {/* Premium Header */}
      <div style={{height: 72, borderBottom: '1px solid var(--border-color)', backgroundColor: 'var(--bg-panel)', display: 'flex', alignItems: 'center', padding: '0 32px', justifyContent: 'space-between', boxShadow: 'var(--shadow-sm)', zIndex: 10}}>
         <div style={{display: 'flex', alignItems: 'center', gap: 32}}>
            <button className="pos-back-btn" onClick={() => onNavigate('dashboard')} style={{border: 'none', background: 'transparent', borderRight: '1px solid var(--border-color)', paddingRight: 20, borderRadius: 0, height: 40}}><FiArrowLeft /> Dashboard</button>
            <div style={{color: 'var(--accent-primary)', fontWeight: 700, fontSize: '18px', display: 'flex', alignItems: 'center', gap: 10}}><FiTruck size={22} /> Supplier Master</div>
         </div>
         <div style={{display: 'flex', alignItems: 'center', gap: 16}}>
            <button onClick={toggleTheme} className="pos-header-btn">
               {theme === 'light' ? <FiMoon /> : <FiSun />}
            </button>
         </div>
      </div>

      <div style={{padding: 40, maxWidth: 1400, margin: '0 auto', width: '100%', flex: 1, backgroundColor: 'var(--bg-primary)'}}>
         {/* Toolbar Area */}
         <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: 32}}>
            <div>
                <h1 style={{margin: 0, fontSize: '28px', fontWeight: 800}}>Manage Suppliers</h1>
                <p style={{color: 'var(--text-muted)', marginTop: 4, fontSize: '15px'}}>Efficiently manage your vendor database and credit terms</p>
            </div>
            <div style={{display: 'flex', gap: 12}}>
               <button className="im-btn secondary" onClick={() => setShowList(!showList)} style={{height: 44}}><FiList /> {showList ? 'Hide List' : 'Browse Suppliers'}</button>
               <button className="im-btn secondary" onClick={() => setFormData(initialForm)} style={{height: 44}}><FiPlus /> New Entry</button>
               <button className="im-btn primary" onClick={handleSave} disabled={saving} style={{height: 44, padding: '0 24px', background: 'linear-gradient(135deg, var(--accent-primary), #a855f7)', boxShadow: '0 4px 12px rgba(99, 102, 241, 0.3)'}}>{saving ? 'Processing...' : <><FiSave /> Save Data</>}</button>
            </div>
         </div>

         {/* Search Filter Dropdown List */}
         {showList && (
             <div className="im-card" style={{marginBottom: 32, borderColor: 'var(--accent-primary)', borderWidth: '2px'}}>
                 <div className="pos-search" style={{maxWidth: '100%', marginBottom: 20}}>
                    <FiSearch className="pos-search-icon" /><input type="text" placeholder="Search by name, code or phone..." value={searchTerm} onChange={e => setSearchTerm(e.target.value)} style={{height: 48, fontSize: '16px'}} />
                 </div>
                 <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: 16, maxHeight: 400, overflowY: 'auto', paddingRight: 8}}>
                     {filteredSuppliers.map(s => (
                         <div key={s.id} onClick={() => selectSupplier(s)} style={{padding: 16, border: '1px solid var(--border-color)', borderRadius: 12, cursor: 'pointer', backgroundColor: 'var(--bg-main)', transition: 'all 0.2s', borderLeft: '4px solid var(--accent-primary)'}}>
                            <div style={{fontWeight: 700, color: 'var(--text-primary)', fontSize: '15px'}}>{s.name}</div>
                            <div style={{color: 'var(--text-muted)', fontSize: 12, display: 'flex', justifyContent: 'space-between', marginTop: 8}}>
                                <span style={{backgroundColor: 'var(--bg-tertiary)', padding: '2px 8px', borderRadius: 4}}>{s.code || 'NO CODE'}</span>
                                <span style={{display: 'flex', alignItems: 'center', gap: 4}}><FiPhone size={10}/> {s.mobile_number}</span>
                            </div>
                         </div>
                     ))}
                     {filteredSuppliers.length === 0 && (
                         <div style={{gridColumn: '1/-1', textAlign: 'center', padding: 40, color: 'var(--text-muted)'}}><FiInfo size={32} style={{opacity: 0.3, marginBottom: 12}} /><br/>No suppliers matching your search.</div>
                     )}
                 </div>
             </div>
         )}

         {/* Main Form Sections */}
         <div style={{display: 'flex', flexDirection: 'column', gap: 32}}>
            {/* SECTION 1: General Info */}
            <div className="im-card">
                <h3 className="im-card-title"><FiInfo /> General Information</h3>
                <div className="im-grid-4">
                    <div className="im-form-group"><label><FiTruck size={14}/> Supplier Code</label><input type="text" name="code" value={formData.code} readOnly style={{fontWeight: 700, color: 'var(--accent-primary)'}} placeholder="Autoassigned" /></div>
                    <div className="im-form-group span-3"><label>Supplier Full Name/Company Name *</label><input type="text" name="name" value={formData.name} onChange={handleInputChange} placeholder="Enter the official business name" style={{fontSize: '16px', fontWeight: 500}} /></div>
                    <div className="im-form-group span-3"><label><FiMapPin size={14}/> Registered Business Address *</label><input type="text" name="address" value={formData.address} onChange={handleInputChange} placeholder="Street address, City, Country" /></div>
                    <div className="im-form-group" style={{display: 'flex', alignItems: 'flex-end', paddingBottom: 10}}>
                        <label className="im-checkbox" style={{padding: '12px 16px', border: '1px solid var(--border-color)', borderRadius: '8px', width: '100%', background: formData.is_active ? 'rgba(34, 197, 94, 0.05)' : 'transparent', borderColor: formData.is_active ? 'var(--accent-success)' : 'var(--border-color)'}}>
                            <input type="checkbox" name="is_active" checked={formData.is_active} onChange={handleInputChange} style={{width: 20, height: 20}} /> 
                            <span style={{fontWeight: 700, color: formData.is_active ? 'var(--accent-success)' : 'var(--text-muted)'}}>{formData.is_active ? 'Active Supplier' : 'Inactive'}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div style={{display: 'grid', gridTemplateColumns: '1.5fr 1fr', gap: 32}}>
                {/* SECTION 2: Contact Details */}
                <div className="im-card">
                    <h3 className="im-card-title"><FiUser /> Contact Details</h3>
                    <div className="im-grid" style={{gridTemplateColumns: '1fr 1fr'}}>
                        <div className="im-form-group"><label><FiPhone size={14}/> Primary Mobile *</label><input type="tel" name="mobile_number" value={formData.mobile_number} onChange={handleInputChange} /></div>
                        <div className="im-form-group"><label><FiPhone size={14}/> Secondary Phone</label><input type="tel" name="mobile_number_2" value={formData.mobile_number_2} onChange={handleInputChange} /></div>
                        <div className="im-form-group span-2"><label><FiMail size={14}/> Official Email Address</label><input type="email" name="email" value={formData.email} onChange={handleInputChange} placeholder="contact@supplier.com" /></div>
                        <div style={{gridColumn: '1/-1', height: 1, backgroundColor: 'var(--border-color)', margin: '8px 0'}}></div>
                        <div className="im-form-group"><label>Attn: Contact Person</label><input type="text" name="contact_person" value={formData.contact_person} onChange={handleInputChange} /></div>
                        <div className="im-form-group"><label>Person Direct Line</label><input type="tel" name="contact_person_number" value={formData.contact_person_number} onChange={handleInputChange} /></div>
                    </div>
                </div>

                {/* SECTION 3: Financials & Notes */}
                <div style={{display: 'flex', flexDirection: 'column', gap: 32}}>
                    <div className="im-card" style={{height: '100%'}}>
                        <h3 className="im-card-title"><FiCreditCard /> Financial Terms</h3>
                        <div style={{display: 'flex', flexDirection: 'column', gap: 20}}>
                            <div className="im-form-group"><label>Credit Limit (LKR)</label><input type="number" name="credit_limit" value={formData.credit_limit} onChange={handleInputChange} style={{fontSize: '20px', fontWeight: 800, color: 'var(--accent-warning)', textAlign: 'right'}} /></div>
                            <div className="im-form-group"><label>Current Outstanding</label><input type="number" name="outstanding" value={formData.outstanding} readOnly style={{fontSize: '20px', fontWeight: 800, color: 'var(--accent-danger)', textAlign: 'right', background: 'var(--bg-main)'}} /></div>
                            <div className="im-form-group" style={{marginTop: 12}}>
                                <label><FiMessageSquare size={14} /> Internal Remarks</label>
                                <textarea name="remark" value={formData.remark} onChange={handleInputChange} rows="4" style={{width: '100%', padding: '16px', borderRadius: '12px', border: '1px solid var(--border-color)', backgroundColor: 'var(--bg-main)', color: 'var(--text-primary)', fontSize: '14px', resize: 'none', transition: 'var(--transition)'}} placeholder="Add payment terms, delivery notes, etc..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
         </div>
      </div>
    </div>
  );
};

export default SupplierMaster;
