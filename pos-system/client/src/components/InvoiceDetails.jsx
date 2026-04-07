import React, { useState, useEffect } from 'react';
import { FiArrowLeft, FiFileText, FiShoppingBag, FiUser, FiMapPin, FiCalendar, FiPackage, FiCreditCard, FiPrinter } from 'react-icons/fi';
import { getSaleDetails, getCompany } from '../services/api';
import Swal from 'sweetalert2';

const InvoiceDetails = ({ invoiceId, onBack }) => {
  const [data, setData] = useState(null);
  const [company, setCompany] = useState({});
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (invoiceId) {
      fetchDetails();
    }
  }, [invoiceId]);

  const fetchDetails = async () => {
    try {
      setLoading(true);
      const [saleRes, companyRes] = await Promise.all([
        getSaleDetails(invoiceId),
        getCompany()
      ]);
      setData(saleRes.data?.data);
      setCompany(companyRes.data?.data || {});
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

  const handlePrint = () => {
    if (!data) return;
    const { invoice, items } = data;
    const formattedDate = new Date(invoice.invoice_date).toLocaleDateString('en-GB');
    const formattedTime = new Date(invoice.created_at || new Date()).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    const printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.write(`
      <html>
        <head>
          <title>Invoice - ${invoice.invoice_no}</title>
          <style>
            @page { size: A5 portrait; margin: 10mm; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; font-size: 13px; color: #333; }
            .invoice-wrapper { width: 148mm; box-sizing: border-box; }
            .header-table { width: 100%; margin-bottom: 25px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .company-name { font-size: 20px; font-weight: 800; text-transform: uppercase; margin-bottom: 4px; }
            .company-info { font-size: 11px; line-height: 1.4; color: #555; }
            .invoice-title { font-size: 24px; font-weight: 900; color: #000; text-align: right; }
            
            .meta-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
            .meta-group div { margin-bottom: 4px; }
            .meta-label { font-weight: 700; color: #000; min-width: 90px; display: inline-block; }
            
            .item-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .item-table th { background: #f4f4f4; border: 1px solid #000; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
            .item-table td { border: 1px solid #ccc; padding: 8px; font-size: 12px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            
            .totals-container { float: right; width: 220px; margin-top: 15px; }
            .total-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
            .total-row.grand { border-bottom: 2px double #000; font-weight: 800; font-size: 15px; margin-top: 4px; padding: 8px 0; }
            
            .footer-section { clear: both; margin-top: 50px; border-top: 1px solid #ddd; padding-top: 15px; font-size: 10px; color: #777; }
            .signature-box { border-top: 1px solid #000; width: 150px; margin-top: 40px; text-align: center; font-weight: 700; }
          </style>
        </head>
        <body>
          <div class="invoice-wrapper">
            <table class="header-table">
              <tr>
                <td>
                  <div class="company-name">${company.name || 'Ekamuthu Enterprises'}</div>
                  <div class="company-info">
                    ${company.address ? `<div>${company.address}</div>` : ''}
                    ${company.phone ? `<div>Tel: ${company.phone}</div>` : ''}
                    ${company.email ? `<div>Email: ${company.email}</div>` : ''}
                  </div>
                </td>
                <td class="invoice-title">INVOICE</td>
              </tr>
            </table>

            <div class="meta-section">
              <div class="meta-group">
                <div><span class="meta-label">Bill To:</span> ${invoice.customer_name || 'Cash Customer'}</div>
                <div><span class="meta-label">Contact:</span> ${invoice.customer_mobile || '-'}</div>
              </div>
              <div class="meta-group text-right">
                <div><span class="meta-label">Invoice #:</span> ${invoice.invoice_no}</div>
                <div><span class="meta-label">Date:</span> ${formattedDate}</div>
                <div><span class="meta-label">Time:</span> ${formattedTime}</div>
              </div>
            </div>

            <table class="item-table">
              <thead>
                <tr>
                  <th style="width: 40px">#</th>
                  <th>Item Description</th>
                  <th class="text-center" style="width: 60px">Qty</th>
                  <th class="text-right" style="width: 80px">Price</th>
                  <th class="text-right" style="width: 90px">Total</th>
                </tr>
              </thead>
              <tbody>
                ${items.map((item, idx) => `
                  <tr>
                    <td class="text-center">${idx + 1}</td>
                    <td><b>${item.item_name}</b></td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-right">${parseFloat(item.price).toFixed(2)}</td>
                    <td class="text-right">${parseFloat(item.total).toFixed(2)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>

            <div class="totals-container">
              <div class="total-row">
                <span>Subtotal</span>
                <span>${parseFloat(invoice.sub_total).toFixed(2)}</span>
              </div>
              ${invoice.discount > 0 ? `
                <div class="total-row">
                  <span>Discount</span>
                  <span>- ${parseFloat(invoice.discount).toFixed(2)}</span>
                </div>
              ` : ''}
              <div class="total-row grand">
                <span>Total Due</span>
                <span>Rs. ${parseFloat(invoice.grand_total).toFixed(2)}</span>
              </div>
            </div>

            <div style="margin-top: 60px; display: flex; justify-content: space-between;">
               <div class="signature-box">Prepared By</div>
               <div class="signature-box">Customer Signature</div>
            </div>

            <div class="footer-section">
              <p>Terms: Goods once sold will not be taken back. This is a computer generated invoice.</p>
              <div class="text-center" style="margin-top: 10px;"><b>Thank you for your business!</b></div>
            </div>
          </div>
          <script>window.onload = function(){ window.print(); window.close(); }<\/script>
        </body>
      </html>
    `);
    printWindow.document.close();
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
        <div className="page-actions" style={{ display: 'flex', gap: '12px' }}>
           <button className="btn btn-primary" onClick={handlePrint} style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
             <FiPrinter /> Print Invoice
           </button>
           <span className="badge badge-primary" style={{ fontSize: '14px', padding: '10px 16px', borderRadius: '8px', display: 'flex', alignItems: 'center' }}>
             No: {invoice.invoice_no}
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
