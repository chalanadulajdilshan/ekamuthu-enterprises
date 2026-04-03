import { useState, useEffect, useCallback, useRef } from 'react';
import { FiSave, FiSearch, FiPackage, FiArrowLeft, FiList, FiPlus, FiSun, FiMoon, FiCheckSquare, FiCamera, FiX } from 'react-icons/fi';
import { getProducts, getCategories, createProduct, updateProduct } from '../services/api';
import toast from 'react-hot-toast';

const ItemMaster = ({ onNavigate, theme, toggleTheme }) => {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showList, setShowList] = useState(false);
  const fileInputRef = useRef(null);
  
  const initialForm = {
    id: null,
    code: '',
    name: '',
    barcode: '',
    category: '',
    note: '',
    reminder_note: '',
    cost_price: '0.00',
    net_price: '0.00',
    tax_type: 'T0-0',
    retail_price: '0.00',
    min_stock: '0',
    max_stock: '0',
    pack_qty: '1',
    quick_menu: false,
    can_price_edit: false,
    scale_item: false,
    is_available: true,
    lottery_item: false,
    voucher_item: false,
    serial_track: false,
    image: null,      // Base64 for new uploads
    image_url: null   // URL for existing images
  };
  
  const [formData, setFormData] = useState(initialForm);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      const [prodRes, catRes] = await Promise.all([
        getProducts(),
        getCategories()
      ]);
      setProducts(prodRes.data.data || []);
      setCategories(catRes.data.data || []);
    } catch (err) {
      console.error('Failed to fetch data:', err);
      toast.error('Failed to load item data');
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

  const handleImageClick = () => fileInputRef.current?.click();

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) return toast.error('Image must be under 2MB');
      const reader = new FileReader();
      reader.onloadend = () => {
        setFormData(prev => ({ ...prev, image: reader.result, image_url: null }));
      };
      reader.readAsDataURL(file);
    }
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
      barcode: p.pattern || '',
      category: p.category || '',
      cost_price: p.list_price || '0.00',
      retail_price: p.invoice_price || '0.00',
      note: p.note || '',
      reminder_note: p.size || '',
      min_stock: p.re_order_level || '0',
      pack_qty: p.re_order_qty || '1',
      is_available: p.is_active !== undefined ? Boolean(p.is_active) : true,
      image_url: p.image_file ? `http://localhost:5000/uploads/${p.image_file}` : null
    });
    setShowList(false);
  };

  const handleSave = async () => {
    if (!formData.name || !formData.code || !formData.category) return toast.error('Check required fields!');
    setSaving(true);
    try {
      const dbPayload = {
        code: formData.code,
        name: formData.name,
        category: formData.category,
        list_price: formData.cost_price,
        invoice_price: formData.retail_price,
        re_order_level: formData.min_stock,
        re_order_qty: formData.pack_qty,
        note: formData.note,
        is_active: formData.is_available,
        pattern: formData.barcode,
        size: formData.reminder_note,
        image: formData.image // Send base64 to server
      };
      if (formData.id) await updateProduct(formData.id, dbPayload);
      else await createProduct(dbPayload);
      toast.success('Successfully saved!');
      fetchData();
    } catch (err) {
      toast.error('Failed to save product');
    } finally {
      setSaving(false);
    }
  };

  const filteredProducts = products.filter(p => p.name.toLowerCase().includes(searchTerm.toLowerCase()) || p.code.toLowerCase().includes(searchTerm.toLowerCase()));

  return (
    <div style={{height: '100vh', overflowY: 'auto', backgroundColor: 'var(--bg-main)', color: 'var(--text-main)', display: 'flex', flexDirection: 'column'}}>
      {/* Header */}
      <div style={{height: 64, borderBottom: '1px solid var(--border-color)', backgroundColor: 'var(--bg-panel)', display: 'flex', alignItems: 'center', padding: '0 24px', justifyContent: 'space-between'}}>
         <div style={{display: 'flex', alignItems: 'center', gap: 24}}>
            <button className="pos-back-btn" onClick={() => onNavigate('dashboard')} style={{border: 'none', background: 'transparent', borderRight: '1px solid var(--border-color)', paddingRight: 16, borderRadius: 0}}><FiArrowLeft /> Dashboard</button>
            <div style={{color: 'var(--accent-primary)', fontWeight: 600, display: 'flex', alignItems: 'center', gap: 8}}><FiPackage /> Item Master Entry</div>
         </div>
         <button onClick={toggleTheme} style={{padding: 8, cursor: 'pointer', background: 'transparent', border: '1px solid var(--border-color)', borderRadius: 8, color: 'var(--text-secondary)'}}>
            {theme === 'light' ? <FiMoon /> : <FiSun />}
         </button>
      </div>

      <div style={{padding: 32, maxWidth: 1300, margin: '0 auto', width: '100%'}}>
         {/* Toolbar */}
         <div style={{display: 'flex', justifyContent: 'space-between', marginBottom: 24}}>
            <div><h2 style={{margin: 0}}>Item Master</h2><p style={{color: 'var(--text-muted)'}}>Complete product data entry with image upload</p></div>
            <div style={{display: 'flex', gap: 12}}>
               <button className="im-btn secondary" onClick={() => setShowList(!showList)}><FiList /> {showList ? 'Hide' : 'Search'}</button>
               <button className="im-btn secondary" onClick={() => setFormData(initialForm)}><FiPlus /> New</button>
               <button className="im-btn primary" onClick={handleSave} disabled={saving}>{saving ? 'Saving...' : <><FiSave /> Save Details</>}</button>
            </div>
         </div>

         {showList && (
             <div style={{backgroundColor: 'var(--bg-panel)', border: '1px solid var(--accent-primary)', borderRadius: 8, padding: 20, marginBottom: 24}}>
                 <div className="pos-search" style={{maxWidth: '100%', marginBottom: 16}}>
                    <FiSearch className="pos-search-icon" /><input type="text" placeholder="Search..." value={searchTerm} onChange={e => setSearchTerm(e.target.value)} />
                 </div>
                 <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: 12, maxHeight: 300, overflowY: 'auto'}}>
                     {filteredProducts.map(p => (
                         <div key={p.id} onClick={() => selectProduct(p)} style={{padding: 12, border: '1px solid var(--border-color)', borderRadius: 6, cursor: 'pointer', backgroundColor: 'var(--bg-main)'}}>
                            <div style={{fontWeight: 600}}>{p.name}</div>
                            <div style={{color: 'var(--text-muted)', fontSize: 11, display: 'flex', justifyContent: 'space-between'}}><span>{p.code}</span><span>Rs. {parseFloat(p.invoice_price).toFixed(2)}</span></div>
                         </div>
                     ))}
                 </div>
             </div>
         )}

         <div style={{display: 'grid', gridTemplateColumns: '1fr 340px', gap: 24, alignItems: 'start'}}>
             {/* Left Column: Form */}
             <div style={{backgroundColor: 'var(--bg-panel)', border: '1px solid var(--border-color)', borderRadius: 12, padding: 32}}>
                 <div className="im-grid" style={{rowGap: 24}}>
                    <div className="im-form-group span-2"><label>Product Code *</label><input type="text" name="code" value={formData.code} onChange={handleInputChange} style={{color: 'var(--accent-primary)', fontWeight: 600}} /></div>
                    <div className="im-form-group span-2"><label>Description *</label><input type="text" name="name" value={formData.name} onChange={handleInputChange} /></div>
                    <div className="im-form-group"><label>Entered Bar Code</label><input type="text" name="barcode" value={formData.barcode} onChange={handleInputChange} /></div>
                    <div className="im-form-group"><label>Department *</label><select name="category" value={formData.category} onChange={handleInputChange}><option value="">-- Select --</option>{categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select></div>
                    <div className="im-form-group span-2"><label>Note</label><input type="text" name="note" value={formData.note} onChange={handleInputChange} /></div>
                    <div className="im-form-group span-2"><label>Reminder Note</label><input type="text" name="reminder_note" value={formData.reminder_note} onChange={handleInputChange} /></div>
                    <div style={{gridColumn: '1/-1', height: 1, backgroundColor: 'var(--border-color)'}}></div>
                    <div className="im-form-group"><label>Cost Price</label><input type="number" name="cost_price" value={formData.cost_price} onChange={handleInputChange} /></div>
                    <div className="im-form-group"><label>Net Price</label><input type="number" name="net_price" value={formData.net_price} onChange={handleInputChange} /></div>
                    <div className="im-form-group"><label>TAX</label><select name="tax_type" value={formData.tax_type} onChange={handleInputChange}><option value="T0-0">T0-0</option></select></div>
                    <div className="im-form-group"><label>Retail Price</label><input type="number" name="retail_price" value={formData.retail_price} onChange={handleInputChange} style={{fontWeight: 700, color: 'var(--accent-success)'}} /></div>
                    <div style={{gridColumn: '1/-1', height: 1, backgroundColor: 'var(--border-color)'}}></div>
                    <div className="im-form-group"><label>Min Stock</label><input type="number" name="min_stock" value={formData.min_stock} onChange={handleInputChange} /></div>
                    <div className="im-form-group"><label>Max Stock</label><input type="number" name="max_stock" value={formData.max_stock} onChange={handleInputChange} /></div>
                    <div className="im-form-group"><label>Pack Qty</label><input type="number" name="pack_qty" value={formData.pack_qty} onChange={handleInputChange} /></div>
                 </div>
             </div>

             {/* Right Column: Settings & Image */}
             <div style={{display: 'flex', flexDirection: 'column', gap: 24}}>
                 <div style={{backgroundColor: 'var(--bg-panel)', border: '1px solid var(--border-color)', borderRadius: 12, padding: 32}}>
                    <h4 style={{marginBottom: 20, display: 'flex', alignItems: 'center', gap: 8}}><FiCheckSquare /> Settings</h4>
                    <div style={{display: 'flex', flexDirection: 'column', gap: 16}}>
                        <label className="im-checkbox"><input type="checkbox" name="quick_menu" checked={formData.quick_menu} onChange={handleInputChange} /> Quick Menu</label>
                        <label className="im-checkbox"><input type="checkbox" name="can_price_edit" checked={formData.can_price_edit} onChange={handleInputChange} /> Can Price Edit By Till</label>
                        <label className="im-checkbox"><input type="checkbox" name="scale_item" checked={formData.scale_item} onChange={handleInputChange} /> Scale Item</label>
                        <label className="im-checkbox" style={{color: 'var(--accent-primary)', fontWeight: 600}}><input type="checkbox" name="is_available" checked={formData.is_available} onChange={handleInputChange} /> Is Available to Sale</label>
                        <label className="im-checkbox"><input type="checkbox" name="lottery_item" checked={formData.lottery_item} onChange={handleInputChange} /> Lottery Item</label>
                        <label className="im-checkbox"><input type="checkbox" name="voucher_item" checked={formData.voucher_item} onChange={handleInputChange} /> Discount Voucher</label>
                        <label className="im-checkbox"><input type="checkbox" name="serial_track" checked={formData.serial_track} onChange={handleInputChange} /> Serial Track</label>
                    </div>
                 </div>

                 {/* IMAGE BOX */}
                 <div onClick={handleImageClick} style={{ 
                    aspectRatio: '1/1', border: '2px dashed var(--border-color)', borderRadius: 12, 
                    display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', 
                    cursor: 'pointer', overflow: 'hidden', position: 'relative', backgroundColor: 'var(--bg-panel)'
                 }}>
                    {(formData.image || formData.image_url) ? (
                        <>
                           <img src={formData.image || formData.image_url} alt="Preview" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                           <button onClick={removeImage} style={{ 
                              position: 'absolute', top: 10, right: 10, background: 'rgba(0,0,0,0.5)', 
                              border: 'none', color: 'white', borderRadius: '50%', width: 28, height: 28, cursor: 'pointer' 
                           }}><FiX /></button>
                        </>
                    ) : (
                        <div style={{ textAlign: 'center', color: 'var(--text-muted)' }}>
                            <FiCamera size={40} style={{ marginBottom: 12, opacity: 0.5 }} />
                            <p style={{ margin: 0, fontSize: 13 }}>Click to upload image</p>
                        </div>
                    )}
                    <input type="file" ref={fileInputRef} onChange={handleImageChange} accept="image/*" style={{ display: 'none' }} />
                 </div>
             </div>
         </div>
      </div>
    </div>
  );
};

export default ItemMaster;
