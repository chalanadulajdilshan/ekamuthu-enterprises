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

  const handlePrint = () => {
    const printContent = receiptRef.current.innerHTML;
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    printWindow.document.write(`
      <html>
        <head>
          <title>Receipt - ${data.invoice_no}</title>
          <style>
            body { 
              font-family: 'Courier New', monospace; 
              margin: 0; padding: 16px; 
              font-size: 12px; color: #000;
            }
            .pos-receipt { padding: 0; }
            .pos-receipt-header { text-align: center; padding-bottom: 12px; border-bottom: 2px dashed #000; margin-bottom: 12px; }
            .pos-receipt-company { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
            .pos-receipt-address { font-size: 10px; color: #555; margin-bottom: 2px; }
            .pos-receipt-divider { border: none; border-top: 1px dashed #000; margin: 10px 0; }
            .pos-receipt-info { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 3px; }
            .pos-receipt-info-label { color: #555; }
            .pos-receipt-info-value { font-weight: 600; }
            .pos-receipt-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .pos-receipt-table th { font-size: 10px; font-weight: 600; text-transform: uppercase; padding: 6px 0; border-bottom: 1px solid #000; text-align: left; }
            .pos-receipt-table th:last-child, .pos-receipt-table td:last-child { text-align: right; }
            .pos-receipt-table td { padding: 5px 0; font-size: 11px; border-bottom: 1px solid #eee; }
            .pos-receipt-totals { margin-top: 10px; padding-top: 8px; border-top: 2px dashed #000; }
            .pos-receipt-total-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; }
            .pos-receipt-total-row.grand { font-size: 15px; font-weight: 800; padding: 6px 0; border-top: 2px solid #000; margin-top: 4px; }
            .pos-receipt-footer { text-align: center; margin-top: 14px; padding-top: 10px; border-top: 2px dashed #000; font-size: 11px; color: #555; }
          </style>
        </head>
        <body>
          ${printContent}
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
    <div className="pos-modal-overlay" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pos-modal">
        <div className="pos-modal-header">
          <div className="pos-modal-title" style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <FiCheck style={{ color: '#22c55e' }} />
            Sale Complete
          </div>
          <button className="pos-modal-close" onClick={onClose}>
            <FiX />
          </button>
        </div>

        <div className="pos-modal-body">
          <div className="pos-receipt" ref={receiptRef}>
            <div className="pos-receipt-header">
              <div className="pos-receipt-company">{company.name || 'Ekamuthu Enterprises'}</div>
              {company.address && <div className="pos-receipt-address">{company.address}</div>}
              {company.phone && <div className="pos-receipt-address">Tel: {company.phone}</div>}
              {company.email && <div className="pos-receipt-address">{company.email}</div>}
            </div>

            <div className="pos-receipt-info">
              <span className="pos-receipt-info-label">Invoice #:</span>
              <span className="pos-receipt-info-value">{data.invoice_no}</span>
            </div>
            <div className="pos-receipt-info">
              <span className="pos-receipt-info-label">Date:</span>
              <span className="pos-receipt-info-value">{formattedDate} {formattedTime}</span>
            </div>
            <div className="pos-receipt-info">
              <span className="pos-receipt-info-label">Customer:</span>
              <span className="pos-receipt-info-value">{data.customer?.name || 'Walk-in Customer'}</span>
            </div>
            <div className="pos-receipt-info">
              <span className="pos-receipt-info-label">Payment:</span>
              <span className="pos-receipt-info-value">{data.payment_type === 1 ? 'Cash' : 'Credit'}</span>
            </div>

            <hr className="pos-receipt-divider" />

            <table className="pos-receipt-table">
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

            <div className="pos-receipt-totals">
              <div className="pos-receipt-total-row">
                <span>Subtotal</span>
                <span>Rs. {parseFloat(data.sub_total).toFixed(2)}</span>
              </div>
              {data.discount > 0 && (
                <div className="pos-receipt-total-row">
                  <span>Discount</span>
                  <span>- Rs. {parseFloat(data.discount).toFixed(2)}</span>
                </div>
              )}
              <div className="pos-receipt-total-row grand">
                <span>Grand Total</span>
                <span>Rs. {parseFloat(data.grand_total).toFixed(2)}</span>
              </div>
            </div>

            <div className="pos-receipt-footer">
              <p style={{ fontWeight: 600, marginBottom: 4 }}>Thank you for your purchase!</p>
              <p>Goods once sold will not be taken back</p>
            </div>
          </div>
        </div>

        <div className="pos-modal-footer">
          <button className="pos-modal-btn" onClick={onClose}>
            <FiX /> Close
          </button>
          <button className="pos-modal-btn primary" onClick={handlePrint}>
            <FiPrinter /> Print Receipt
          </button>
        </div>
      </div>
    </div>
  );
};

export default ReceiptModal;
