import { useRef, useEffect, useState } from 'react';
import { FiX, FiPrinter, FiCheck } from 'react-icons/fi';
import { getCompany } from '../services/api';

const ReceiptModal = ({ data, onClose }) => {
  const receiptRef = useRef(null);
  const [company, setCompany] = useState({});

  useEffect(() => {
    const fetchCompany = async () => {
      try {
        const res = await getCompany();
        setCompany(res.data.data || {});
      } catch (err) {
        console.error('Error fetching company:', err);
      }
    };
    fetchCompany();
  }, []);

  useEffect(() => {
    if (data && company.name) {
      // Auto trigger print when data is ready
      setTimeout(() => {
        handlePrint();
      }, 500);
    }
  }, [data, company]);

  const handlePrint = () => {
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.write(`
      <html>
        <head>
          <title>Invoice - ${data.invoice_no}</title>
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
                <div><span class="meta-label">Bill To:</span> ${data.customer?.name || 'Cash Customer'}</div>
                <div><span class="meta-label">Contact:</span> ${data.customer?.mobile || '-'}</div>
              </div>
              <div class="meta-group text-right">
                <div><span class="meta-label">Invoice #:</span> ${data.invoice_no}</div>
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
                ${data.items.map((item, idx) => `
                  <tr>
                    <td class="text-center">${idx + 1}</td>
                    <td><b>${item.name}</b></td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-right">${parseFloat(item.price).toFixed(2)}</td>
                    <td class="text-right">${(item.price * item.quantity).toFixed(2)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>

            <div class="totals-container">
              <div class="total-row">
                <span>Subtotal</span>
                <span>${parseFloat(data.sub_total).toFixed(2)}</span>
              </div>
              ${data.discount > 0 ? `
                <div class="total-row">
                  <span>Discount</span>
                  <span>- ${parseFloat(data.discount).toFixed(2)}</span>
                </div>
              ` : ''}
              <div class="total-row grand">
                <span>Total Due</span>
                <span>Rs. ${parseFloat(data.grand_total).toFixed(2)}</span>
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

  const now = new Date();
  const formattedDate = now.toLocaleDateString('en-GB');
  const formattedTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

  return (
    <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="modal">
        <div className="modal-header">
          <div className="modal-title">
            <FiCheck style={{ color: 'var(--success)' }} />
            Sale Complete
          </div>
          <button className="modal-close" onClick={onClose}>
            <FiX />
          </button>
        </div>

        <div className="modal-body">
          <div className="receipt" ref={receiptRef}>
            <div className="receipt-header">
              <div className="receipt-company">{company.name || 'Ekamuthu Enterprises'}</div>
              {company.address && <div className="receipt-address">{company.address}</div>}
              {company.phone && <div className="receipt-address">Tel: {company.phone}</div>}
              {company.email && <div className="receipt-address">{company.email}</div>}
            </div>

            <div className="receipt-info">
              <span className="receipt-info-label">Invoice #:</span>
              <span className="receipt-info-value">{data.invoice_no}</span>
            </div>
            <div className="receipt-info">
              <span className="receipt-info-label">Date:</span>
              <span className="receipt-info-value">{formattedDate} {formattedTime}</span>
            </div>
            <div className="receipt-info">
              <span className="receipt-info-label">Customer:</span>
              <span className="receipt-info-value">{data.customer?.name || 'Walk-in Customer'}</span>
            </div>
            <div className="receipt-info">
              <span className="receipt-info-label">Payment:</span>
              <span className="receipt-info-value">{data.payment_type === 1 ? 'Cash' : 'Credit'}</span>
            </div>

            <hr className="receipt-divider" />

            <table className="receipt-table">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Price</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                {data.items.map((item, idx) => (
                  <tr key={idx}>
                    <td style={{ maxWidth: 140, overflow: 'hidden', textOverflow: 'ellipsis' }}>
                      {item.name}
                    </td>
                    <td>{item.quantity}</td>
                    <td>{parseFloat(item.price).toFixed(2)}</td>
                    <td>{(item.price * item.quantity).toFixed(2)}</td>
                  </tr>
                ))}
              </tbody>
            </table>

            <div className="receipt-totals">
              <div className="receipt-total-row">
                <span>Subtotal</span>
                <span>Rs. {parseFloat(data.sub_total).toFixed(2)}</span>
              </div>
              {data.discount > 0 && (
                <div className="receipt-total-row">
                  <span>Discount</span>
                  <span>- Rs. {parseFloat(data.discount).toFixed(2)}</span>
                </div>
              )}
              <div className="receipt-total-row grand">
                <span>Grand Total</span>
                <span>Rs. {parseFloat(data.grand_total).toFixed(2)}</span>
              </div>
            </div>

            <div className="receipt-footer">
              <p style={{ fontWeight: 600, marginBottom: 4 }}>Thank you for your purchase!</p>
              <p>Goods once sold will not be taken back</p>
            </div>
          </div>
        </div>

        <div className="modal-footer">
          <button className="btn btn-secondary" onClick={onClose}>
            <FiX /> Close
          </button>
          <button className="btn btn-primary" onClick={handlePrint}>
            <FiPrinter /> Print Receipt
          </button>
        </div>
      </div>
    </div>
  );
};

export default ReceiptModal;
