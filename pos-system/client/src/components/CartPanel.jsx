import { useState, useEffect, useRef, useCallback } from 'react';
import { FiShoppingBag, FiTrash2, FiPlus, FiMinus, FiX, FiUser, FiDollarSign, FiCreditCard, FiCheck } from 'react-icons/fi';
import { getCustomers, createSale } from '../services/api';
import toast from 'react-hot-toast';

const CartPanel = ({ cart, onUpdateQuantity, onRemoveItem, onClearCart, onSaleComplete }) => {
  const [customer, setCustomer] = useState(null);
  const [customerSearch, setCustomerSearch] = useState('');
  const [customers, setCustomers] = useState([]);
  const [showCustomerDropdown, setShowCustomerDropdown] = useState(false);
  const [paymentType, setPaymentType] = useState(1); // 1=Cash, 2=Credit
  const [discount, setDiscount] = useState(0);
  const [processing, setProcessing] = useState(false);
  const customerRef = useRef(null);

  // Calculate totals
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const discountAmount = parseFloat(discount) || 0;
  const grandTotal = subtotal - discountAmount;
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

  // Search customers
  useEffect(() => {
    if (customerSearch.length < 2) {
      setCustomers([]);
      return;
    }
    const timer = setTimeout(async () => {
      try {
        const res = await getCustomers(customerSearch);
        setCustomers(res.data.data || []);
        setShowCustomerDropdown(true);
      } catch (err) {
        console.error('Error fetching customers:', err);
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [customerSearch]);

  // Close dropdown on outside click
  useEffect(() => {
    const handler = (e) => {
      if (customerRef.current && !customerRef.current.contains(e.target)) {
        setShowCustomerDropdown(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const selectCustomer = (cust) => {
    setCustomer(cust);
    setCustomerSearch('');
    setShowCustomerDropdown(false);
  };

  const removeCustomer = () => {
    setCustomer(null);
    setCustomerSearch('');
  };

  // Process sale
  const handleCheckout = useCallback(async () => {
    if (cart.length === 0) {
      toast.error('Cart is empty');
      return;
    }

    if (paymentType === 2 && !customer) {
      toast.error('Please select a customer for credit sales');
      return;
    }

    setProcessing(true);
    try {
      const saleData = {
        customer_id: customer?.id || 0,
        customer_name: customer?.name || 'Walk-in Customer',
        customer_mobile: customer?.mobile || '',
        customer_address: customer?.address || '',
        department_id: 1,
        payment_type: paymentType,
        items: cart.map(item => ({
          item_id: item.item_id,
          code: item.code,
          name: item.name,
          quantity: item.quantity,
          price: item.price,
          cost: item.cost,
          discount: item.discount,
        })),
        sub_total: subtotal,
        discount: discountAmount,
        tax: 0,
        grand_total: grandTotal,
        remark: 'POS Sale',
      };

      const res = await createSale(saleData);

      if (res.data.success) {
        onSaleComplete({
          ...res.data.data,
          items: cart,
          customer: customer,
          payment_type: paymentType,
          sub_total: subtotal,
          discount: discountAmount,
          grand_total: grandTotal,
        });
        setCustomer(null);
        setDiscount(0);
        setPaymentType(1);
      } else {
        toast.error(res.data.message || 'Sale failed');
      }
    } catch (err) {
      console.error('Checkout error:', err);
      toast.error('Failed to process sale. Please try again.');
    } finally {
      setProcessing(false);
    }
  }, [cart, customer, paymentType, subtotal, discountAmount, grandTotal, onSaleComplete]);

  return (
    <>
      {/* Cart Header */}
      <div className="pos-cart-header">
        <div className="pos-cart-title">
          <FiShoppingBag />
          Current Order
          {totalItems > 0 && <span className="pos-cart-count">{totalItems}</span>}
        </div>
        {cart.length > 0 && (
          <button className="pos-cart-clear" onClick={onClearCart}>
            <FiTrash2 style={{ marginRight: 4 }} /> Clear
          </button>
        )}
      </div>

      {/* Customer Selection */}
      <div className="pos-customer-section">
        {customer ? (
          <div className="pos-selected-customer">
            <div className="pos-selected-customer-info">
              <div className="pos-selected-customer-avatar">
                {customer.name.charAt(0).toUpperCase()}
              </div>
              <div>
                <div className="pos-selected-customer-name">{customer.name}</div>
                <div className="pos-selected-customer-mobile">{customer.mobile || customer.code}</div>
              </div>
            </div>
            <button className="pos-selected-customer-remove" onClick={removeCustomer}>
              <FiX />
            </button>
          </div>
        ) : (
          <div className="pos-customer-select" ref={customerRef}>
            <input
              type="text"
              className="pos-customer-input"
              placeholder="🔍 Search customer (optional for cash)..."
              value={customerSearch}
              onChange={(e) => setCustomerSearch(e.target.value)}
              onFocus={() => customers.length > 0 && setShowCustomerDropdown(true)}
            />
            {showCustomerDropdown && customers.length > 0 && (
              <div className="pos-customer-dropdown">
                {customers.map(cust => (
                  <div
                    key={cust.id}
                    className="pos-customer-option"
                    onClick={() => selectCustomer(cust)}
                  >
                    <div className="pos-customer-option-name">{cust.name}</div>
                    <div className="pos-customer-option-detail">
                      {cust.code} {cust.mobile && `• ${cust.mobile}`}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Cart Items */}
      <div className="pos-cart-items">
        {cart.length === 0 ? (
          <div className="pos-cart-empty">
            <FiShoppingBag className="pos-cart-empty-icon" />
            <div className="pos-cart-empty-text">No items in cart</div>
            <div style={{ fontSize: 12, color: 'var(--text-muted)' }}>
              Click on products to add them
            </div>
          </div>
        ) : (
          cart.map(item => (
            <div key={item.item_id} className="pos-cart-item">
              <div className="pos-cart-item-info">
                <div className="pos-cart-item-name">{item.name}</div>
                <div className="pos-cart-item-price">
                  Rs. {item.price.toLocaleString('en-US', { minimumFractionDigits: 2 })} × {item.quantity}
                </div>
              </div>
              <div className="pos-cart-item-qty">
                <button
                  className="pos-qty-btn"
                  onClick={() => onUpdateQuantity(item.item_id, item.quantity - 1)}
                >
                  <FiMinus />
                </button>
                <span className="pos-qty-value">{item.quantity}</span>
                <button
                  className="pos-qty-btn"
                  onClick={() => onUpdateQuantity(item.item_id, item.quantity + 1)}
                >
                  <FiPlus />
                </button>
              </div>
              <div className="pos-cart-item-total">
                Rs. {(item.price * item.quantity).toLocaleString('en-US', { minimumFractionDigits: 2 })}
              </div>
              <button
                className="pos-cart-item-remove"
                onClick={() => onRemoveItem(item.item_id)}
              >
                <FiX />
              </button>
            </div>
          ))
        )}
      </div>

      {/* Summary */}
      {cart.length > 0 && (
        <div className="pos-cart-summary">
          <div className="pos-summary-row">
            <span className="pos-summary-label">Subtotal ({totalItems} items)</span>
            <span className="pos-summary-value">
              Rs. {subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
          </div>
          <div className="pos-discount-row">
            <span className="pos-summary-label" style={{ flex: 1 }}>Discount</span>
            <span style={{ color: 'var(--text-muted)', fontSize: 13, marginRight: 4 }}>Rs.</span>
            <input
              type="number"
              className="pos-discount-input"
              value={discount}
              onChange={(e) => setDiscount(e.target.value)}
              min="0"
              step="0.01"
            />
          </div>
          <div className="pos-summary-row total">
            <span className="pos-summary-label">Grand Total</span>
            <span className="pos-summary-value">
              Rs. {grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
          </div>
        </div>
      )}

      {/* Payment */}
      <div className="pos-payment-section">
        <div className="pos-payment-buttons">
          <button
            className={`pos-pay-type-btn ${paymentType === 1 ? 'active' : ''}`}
            onClick={() => setPaymentType(1)}
          >
            <FiDollarSign /> Cash
          </button>
          <button
            className={`pos-pay-type-btn ${paymentType === 2 ? 'active' : ''}`}
            onClick={() => setPaymentType(2)}
          >
            <FiCreditCard /> Credit
          </button>
        </div>
        <button
          className="pos-checkout-btn"
          onClick={handleCheckout}
          disabled={cart.length === 0 || processing}
        >
          {processing ? (
            <>
              <div className="pos-spinner" style={{ width: 18, height: 18, borderWidth: 2 }}></div>
              Processing...
            </>
          ) : (
            <>
              <FiCheck />
              Complete Sale — Rs. {grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </>
          )}
        </button>
      </div>
    </>
  );
};

export default CartPanel;
