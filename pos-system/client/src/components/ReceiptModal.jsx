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
            body { font-family: 'Courier New', monospace; margin: 0; padding: 16px; font-size: 12px; color: #000; }
            .receipt { padding: 0; }
            .receipt-header { text-align: center; padding-bottom: 12px; border-bottom: 2px dashed #000; margin-bottom: 12px; }
            .receipt-company { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
            .receipt-address { font-size: 10px; color: #555; margin-bottom: 2px; }
            .receipt-divider { border: none; border-top: 1px dashed #000; margin: 10px 0; }
            .receipt-info { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 3px; }
            .receipt-info-label { color: #555; }
            .receipt-info-value { font-weight: 600; }
            .receipt-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .receipt-table th { font-size: 10px; font-weight: 600; text-transform: uppercase; padding: 6px 0; border-bottom: 1px solid #000; text-align: left; }
            .receipt-table th:last-child, .receipt-table td:last-child { text-align: right; }
            .receipt-table td { padding: 5px 0; font-size: 11px; border-bottom: 1px solid #eee; }
            .receipt-totals { margin-top: 10px; padding-top: 8px; border-top: 2px dashed #000; }
            .receipt-total-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; }
            .receipt-total-row.grand { font-size: 15px; font-weight: 800; padding: 6px 0; border-top: 2px solid #000; margin-top: 4px; }
            .receipt-footer { text-align: center; margin-top: 14px; padding-top: 10px; border-top: 2px dashed #000; font-size: 11px; color: #555; }
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
