import React, { useState, useEffect } from 'react';
import { FiArrowLeft, FiFileText, FiShoppingBag, FiUser, FiMapPin, FiCalendar, FiPackage, FiCreditCard } from 'react-icons/fi';
import { getSaleDetails } from '../services/api';
import Swal from 'sweetalert2';

const InvoiceDetails = ({ invoiceId, onBack }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (invoiceId) {
      fetchDetails();
    }
  }, [invoiceId]);

  const fetchDetails = async () => {
    try {
      setLoading(true);
      const res = await getSaleDetails(invoiceId);
      setData(res.data?.data);
    } catch (err) {
      console.error('Error fetching Invoice details:', err);
      Swal.fire({
        icon: 'error',
        title: 'Fetch Failed',
        text: 'Could not load details for this Invoice. Please try again.'
      });
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="page animate-fade-in">
        <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '400px' }}>
          <div className="text-primary fw-bold" style={{ fontSize: '18px' }}>Loading Invoice Details...</div>
        </div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="page animate-fade-in">
        <div className="alert alert-danger">No data found for this Invoice.</div>
        <button className="btn btn-secondary mt-3" onClick={onBack}>
          <FiArrowLeft style={{ marginRight: '8px' }} /> Go Back
        </button>
      </div>
    );
  }

  const { invoice, items } = data;

  return (
    <div className="page animate-fade-in">
      {/* Header */}
      <div className="page-header-row animate-fade-in" style={{ alignItems: 'center', marginBottom: '32px', display: 'flex', justifyContent: 'space-between' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
          <button className="topbar-btn" onClick={onBack}>
            <FiArrowLeft />
          </button>
          <div>
            <h1 className="page-title" style={{ fontSize: 28 }}>Invoice Details: {invoice.invoice_no}</h1>
            <p className="page-subtitle">Standard Sales Ledger Record</p>
          </div>
        </div>
        <div className="page-actions">
           <span className="badge badge-primary" style={{ fontSize: '14px', padding: '10px 16px', borderRadius: '8px' }}>
             Invoice No: {invoice.invoice_no}
           </span>
        </div>
      </div>

      <div className="animate-fade-in">
        {/* Info Grid */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px', marginBottom: '24px' }}>
          {/* Customer Info */}
          <div className="card shadow-md">
            <div className="card-header">
              <div className="card-title"><FiUser /> Customer Information</div>
            </div>
            <div className="card-body">
              <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                   <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: 'var(--primary-light)', color: 'var(--primary)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                     <FiUser size={20} />
                   </div>
                   <div>
                     <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700 }}>Customer Name</div>
                     <div style={{ fontWeight: 600, fontSize: '16px' }}>{invoice.customer_name || 'Walk-in / Cash Customer'}</div>
                   </div>
                </div>

                {invoice.customer_mobile && (
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: 'var(--info-light)', color: 'var(--info)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <FiMapPin size={20} />
                    </div>
                    <div>
                      <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700 }}>Contact & Address</div>
                      <div style={{ fontWeight: 600, fontSize: '14px' }}>{invoice.customer_mobile}</div>
                      <div className="text-sm text-secondary">{invoice.customer_address}</div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Invoice Summary */}
          <div className="card shadow-md">
            <div className="card-header">
              <div className="card-title"><FiFileText /> Invoice Summary</div>
            </div>
            <div className="card-body">
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                <div>
                  <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Invoice Date</div>
                  <div style={{ fontWeight: 600 }}><FiCalendar style={{ marginRight: '6px', opacity: 0.7 }} /> {new Date(invoice.invoice_date).toLocaleDateString()}</div>
                </div>
                <div>
                  <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Payment Type</div>
                  <div className={`badge ${invoice.payment_type == 2 ? 'badge-warning' : 'badge-success'}`}>
                    <FiCreditCard style={{ marginRight: '6px' }} />
                    {invoice.payment_type == 2 ? 'Credit Sale' : 'Cash Sale'}
                  </div>
                </div>
                <div>
                  <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Department</div>
                  <div style={{ fontWeight: 600 }}>{invoice.department_name}</div>
                </div>
                <div>
                   <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Grand Total</div>
                   <div style={{ fontWeight: 900, fontSize: '20px', color: 'var(--success)' }}>Rs. {parseFloat(invoice.grand_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Order Items Table */}
        <div className="card shadow-md">
          <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div className="card-title"><FiShoppingBag /> Order Breakdown</div>
            <span className="badge badge-info">{items.length} Line Items</span>
          </div>
          <div className="table-responsive">
            <table className="table table-hover">
              <thead>
                <tr>
                  <th style={{ paddingLeft: '24px' }}>#</th>
                  <th>Product Details</th>
                  <th className="text-right">Price</th>
                  <th className="text-center">Qty</th>
                  <th className="text-right">Discount</th>
                  <th className="text-right" style={{ paddingRight: '24px' }}>Item Total</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item, idx) => (
                  <tr key={item.id}>
                    <td style={{ paddingLeft: '24px', color: 'var(--text-muted)' }}>{idx + 1}</td>
                    <td>
                      <div style={{ fontWeight: 700, color: 'var(--primary)' }}>{item.item_code}</div>
                      <div style={{ fontSize: '12px', opacity: 0.8 }}>{item.item_name}</div>
                    </td>
                    <td className="text-right font-mono">{parseFloat(item.price).toFixed(2)}</td>
                    <td className="text-center">
                       <span style={{ fontWeight: 800, padding: '4px 10px', background: 'var(--bg-hover)', borderRadius: '6px' }}>{item.quantity}</span>
                    </td>
                    <td className="text-right font-mono text-danger">-{parseFloat(item.discount).toFixed(2)}</td>
                    <td className="text-right font-mono fw-bold" style={{ paddingRight: '24px', fontSize: '15px' }}>
                      {parseFloat(item.total).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr style={{ background: 'var(--bg-hover)', fontWeight: 800 }}>
                   <td colSpan="5" className="text-right" style={{ padding: '16px' }}>Gross Subtotal</td>
                   <td className="text-right" style={{ paddingRight: '24px', padding: '16px' }}>Rs. {parseFloat(invoice.sub_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                </tr>
                {invoice.discount > 0 && (
                  <tr style={{ color: 'var(--danger)' }}>
                    <td colSpan="5" className="text-right" style={{ padding: '10px 16px' }}>Total Discounts</td>
                    <td className="text-right" style={{ paddingRight: '24px', padding: '10px 16px' }}>-Rs. {parseFloat(invoice.discount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                  </tr>
                )}
                {invoice.tax > 0 && (
                  <tr>
                    <td colSpan="5" className="text-right" style={{ padding: '10px 16px' }}>Tax Charges</td>
                    <td className="text-right" style={{ paddingRight: '24px', padding: '10px 16px' }}>+Rs. {parseFloat(invoice.tax).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                  </tr>
                )}
                <tr style={{ background: 'var(--primary)', color: 'white', fontSize: '18px' }}>
                   <td colSpan="5" className="text-right" style={{ padding: '20px', borderRadius: '0 0 0 12px' }}>Amount Due</td>
                   <td className="text-right" style={{ paddingRight: '24px', padding: '20px', borderRadius: '0 0 12px 0' }}>Rs. {parseFloat(invoice.grand_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        {/* Remarks Section */}
        {invoice.remark && (
          <div className="card shadow-sm mt-4" style={{ background: 'var(--bg-hover)', border: 'none' }}>
            <div className="card-body">
              <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '8px' }}>Internal Remarks</div>
              <p style={{ margin: 0, fontStyle: 'italic', color: 'var(--text-secondary)' }}>"{invoice.remark}"</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default InvoiceDetails;
