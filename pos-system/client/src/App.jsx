import { useState, useEffect, useCallback, useRef } from 'react';
import { FiShoppingCart, FiSearch, FiGrid, FiMaximize, FiArrowLeft, FiClock, FiSun, FiMoon } from 'react-icons/fi';
import ProductGrid from './components/ProductGrid';
import CartPanel from './components/CartPanel';
import ReceiptModal from './components/ReceiptModal';
import RecentSalesModal from './components/RecentSalesModal';
import Dashboard from './components/Dashboard';
import ItemMaster from './components/ItemMaster';
import { getProducts, getCategories } from './services/api';
import toast from 'react-hot-toast';

function App() {
  const [currentView, setCurrentView] = useState('dashboard');
  const [theme, setTheme] = useState('light');
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [cart, setCart] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [activeCategory, setActiveCategory] = useState('');
  const [loading, setLoading] = useState(true);
  const [receiptData, setReceiptData] = useState(null);
  const [showRecent, setShowRecent] = useState(false);
  const searchRef = useRef(null);

  // Fetch products
  const fetchProducts = useCallback(async () => {
    if (currentView !== 'pos') return;
    try {
      setLoading(true);
      const res = await getProducts({ search: searchTerm, category: activeCategory });
      setProducts(res.data.data || []);
    } catch (err) {
      console.error('Failed to fetch products:', err);
      toast.error('Failed to load products');
    } finally {
      setLoading(false);
    }
  }, [searchTerm, activeCategory, currentView]);

  // Apply theme to document
  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
  }, [theme]);

  // Fetch categories
  const fetchCategories = useCallback(async () => {
    if (currentView !== 'pos') return;
    try {
      const res = await getCategories();
      setCategories(res.data.data || []);
    } catch (err) {
      console.error('Failed to fetch categories:', err);
    }
  }, [currentView]);

  useEffect(() => {
    if (currentView === 'pos') {
      fetchCategories();
      fetchProducts();
    }
  }, [currentView]);

  useEffect(() => {
    if (currentView !== 'pos') return;
    const debounce = setTimeout(() => {
      fetchProducts();
    }, 300);
    return () => clearTimeout(debounce);
  }, [searchTerm, activeCategory, currentView]);

  // Add to cart
  const addToCart = useCallback((product) => {
    setCart(prev => {
      const existing = prev.find(item => item.item_id === product.id);
      if (existing) {
        if (existing.quantity >= product.available_qty) {
          toast.error(`Only ${product.available_qty} available in stock`);
          return prev;
        }
        return prev.map(item =>
          item.item_id === product.id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      }
      toast.success(`${product.name} added to cart`, { duration: 1500 });
      return [...prev, {
        item_id: product.id,
        code: product.code,
        name: product.name,
        price: parseFloat(product.list_price) || 0,
        cost: parseFloat(product.invoice_price) || 0,
        quantity: 1,
        discount: 0,
        max_qty: product.available_qty,
        brand_name: product.brand_name,
        category_name: product.category_name,
      }];
    });
  }, []);

  // Update cart quantity
  const updateQuantity = useCallback((itemId, newQty) => {
    if (newQty < 1) return;
    setCart(prev =>
      prev.map(item => {
        if (item.item_id === itemId) {
          if (newQty > item.max_qty) {
            toast.error(`Only ${item.max_qty} available`);
            return item;
          }
          return { ...item, quantity: newQty };
        }
        return item;
      })
    );
  }, []);

  // Remove from cart
  const removeFromCart = useCallback((itemId) => {
    setCart(prev => prev.filter(item => item.item_id !== itemId));
  }, []);

  // Clear cart
  const clearCart = useCallback(() => {
    setCart([]);
  }, []);

  // After successful sale
  const handleSaleComplete = useCallback((data) => {
    setReceiptData(data);
    setCart([]);
    fetchProducts();
    setCurrentView('dashboard');
    toast.success('Sale completed successfully! 🎉');
  }, [fetchProducts]);

  // Toggle fullscreen
  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen();
    } else {
      document.exitFullscreen();
    }
  };

  // Keyboard shortcut: Focus search on F2
  useEffect(() => {
    if (currentView !== 'pos') return;
    const handler = (e) => {
      if (e.key === 'F2') {
        e.preventDefault();
        searchRef.current?.focus();
      }
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [currentView]);

  const toggleTheme = () => {
    setTheme(prev => prev === 'light' ? 'dark' : 'light');
  };

  if (currentView === 'dashboard') {
    return (
      <>
        <Dashboard onNavigate={setCurrentView} theme={theme} toggleTheme={toggleTheme} />
        {receiptData && (
          <ReceiptModal
            data={receiptData}
            onClose={() => setReceiptData(null)}
          />
        )}
      </>
    );
  }

  if (currentView === 'itemMaster') {
    return <ItemMaster onNavigate={setCurrentView} theme={theme} toggleTheme={toggleTheme} />;
  }

  return (
    <div className="pos-container">
      {/* LEFT: Products */}
      <div className="pos-left">
        {/* Header */}
        <div className="pos-header">
          <div className="pos-logo">
            <div className="pos-logo-icon">
              <FiShoppingCart />
            </div>
            <div className="pos-logo-text">
              POS <span>Terminal</span>
            </div>
          </div>

          <div className="pos-search">
            <FiSearch className="pos-search-icon" />
            <input
              ref={searchRef}
              type="text"
              placeholder="Search products by name or code... (F2)"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>

          <div className="pos-header-actions">
            <button className="pos-header-btn" onClick={toggleTheme} title="Toggle Theme">
              {theme === 'light' ? <FiMoon /> : <FiSun />}
            </button>
            <button className="pos-header-btn" onClick={() => setShowRecent(true)} title="Recent Sales">
              <FiClock />
            </button>
            <button className="pos-header-btn" onClick={toggleFullscreen} title="Fullscreen">
              <FiMaximize />
            </button>
            <button className="pos-back-btn" onClick={() => setCurrentView('dashboard')} title="Back to Dashboard">
              <FiArrowLeft />
              <span>Back</span>
            </button>
          </div>
        </div>

        {/* Categories */}
        <div className="pos-categories" style={{ marginTop: '10px' }}>
          <button
            className={`pos-category-btn ${activeCategory === '' ? 'active' : ''}`}
            onClick={() => setActiveCategory('')}
          >
            <FiGrid style={{ marginRight: 4 }} /> All Items
          </button>
          {categories.map(cat => (
            <button
              key={cat.id}
              className={`pos-category-btn ${activeCategory === cat.id ? 'active' : ''}`}
              onClick={() => setActiveCategory(activeCategory === cat.id ? '' : cat.id)}
            >
              {cat.name}
            </button>
          ))}
        </div>

        {/* Products */}
        <ProductGrid
          products={products}
          loading={loading}
          onAddToCart={addToCart}
        />
      </div>

      {/* RIGHT: Cart */}
      <div className="pos-right">
        <CartPanel
          cart={cart}
          onUpdateQuantity={updateQuantity}
          onRemoveItem={removeFromCart}
          onClearCart={clearCart}
          onSaleComplete={handleSaleComplete}
        />
      </div>

      {/* Receipt Modal */}
      {receiptData && (
        <ReceiptModal
          data={receiptData}
          onClose={() => setReceiptData(null)}
        />
      )}

      {/* Recent Sales Modal */}
      {showRecent && (
        <RecentSalesModal onClose={() => setShowRecent(false)} />
      )}
    </div>
  );
}

export default App;
