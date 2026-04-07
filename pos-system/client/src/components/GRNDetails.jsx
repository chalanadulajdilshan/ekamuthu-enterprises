import React, { useState, useEffect } from 'react';
import { FiArrowLeft, FiFileText, FiShoppingCart, FiTruck, FiMapPin, FiCalendar, FiPackage } from 'react-icons/fi';
import { getGrnDetails } from '../services/api';
import Swal from 'sweetalert2';

const GRNDetails = ({ grnId, onBack }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (grnId) {
      fetchDetails();
    }
  }, [grnId]);

  const fetchDetails = async () => {
    try {
      setLoading(true);
      const res = await getGrnDetails(grnId);
      setData(res.data?.data);
    } catch (err) {
      console.error('Error fetching GRN details:', err);
      Swal.fire({
        icon: 'error',
        title: 'Fetch Failed',
        text: 'Could not load details for this GRN. Please try again.'
      });
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="page animate-fade-in">
        <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '400px' }}>
          <div className="text-primary fw-bold" style={{ fontSize: '18px' }}>Loading GRN Details...</div>
        </div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="page animate-fade-in">
        <div className="alert alert-danger">No data found for this GRN.</div>
        <button className="btn btn-secondary mt-3" onClick={onBack}>
          <FiArrowLeft style={{ marginRight: '8px' }} /> Go Back
        </button>
      </div>
    );
  }

  const { grn, items } = data;

  return (
    <div className="page animate-fade-in">
      {/* Header */}
      <div className="page-header-row animate-fade-in" style={{ alignItems: 'center', marginBottom: '32px', display: 'flex', justifyContent: 'space-between' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
          <button className="topbar-btn" onClick={onBack}>
            <FiArrowLeft />
          </button>
          <div>
            <h1 className="page-title" style={{ fontSize: 28 }}>GRN Details: {grn.grn_no}</h1>
            <p className="page-subtitle">Historical record of stock receipt</p>
          </div>
        </div>
        <div className="page-actions">
           <span className="badge badge-primary" style={{ fontSize: '14px', padding: '10px 16px', borderRadius: '8px' }}>
             ARN No: {grn.grn_no}
           </span>
        </div>
      </div>

      <div className="animate-fade-in">
        {/* Info Grid */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px', marginBottom: '24px' }}>
          {/* Supplier & Location Info */}
          <div className="card shadow-md">
            <div className="card-header">
              <div className="card-title"><FiTruck /> Supplier & Location</div>
            </div>
            <div className="card-body">
              <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                   <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: 'var(--primary-light)', color: 'var(--primary)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                     <FiTruck size={20} />
                   </div>
                   <div>
                     <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700 }}>Supplier</div>
                     <div style={{ fontWeight: 600, fontSize: '16px' }}>[{grn.supplier_code}] {grn.supplier_name}</div>
                   </div>
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                   <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: 'var(--info-light)', color: 'var(--info)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                     <FiMapPin size={20} />
                   </div>
                   <div>
                     <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700 }}>Department</div>
                     <div style={{ fontWeight: 600, fontSize: '16px' }}>{grn.department_name}</div>
                   </div>
                </div>
              </div>
            </div>
          </div>

          {/* Transaction Metadata */}
          <div className="card shadow-md">
            <div className="card-header">
              <div className="card-title"><FiFileText /> Transaction Summary</div>
            </div>
            <div className="card-body">
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                <div>
                  <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Entry Date</div>
                  <div style={{ fontWeight: 600 }}><FiCalendar style={{ marginRight: '6px', opacity: 0.7 }} /> {new Date(grn.entry_date).toLocaleDateString()}</div>
                </div>
                <div>
                  <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Invoice Info</div>
                  <div style={{ fontWeight: 600 }}>#{grn.invoice_no} ({new Date(grn.invoice_date).toLocaleDateString()})</div>
                </div>
                <div>
                  <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Value (Excl. Tax)</div>
                  <div style={{ fontWeight: 800, fontSize: '18px', color: 'var(--primary)' }}>Rs. {parseFloat(grn.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                </div>
                <div>
                   <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '4px' }}>Payment Mode</div>
                   <div className={`badge ${grn.payment_type == 2 ? 'badge-warning' : 'badge-success'}`} style={{ fontSize: '12px' }}>
                     {grn.payment_type == 2 ? 'Credit Purchase' : 'Cash Purchase'}
                   </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Received Items Table */}
        <div className="card shadow-md">
          <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div className="card-title"><FiShoppingCart /> Received Items Ledger</div>
            <span className="badge badge-info">{items.length} Unique Products</span>
          </div>
          <div className="table-responsive">
            <table className="table table-striped">
              <thead>
                <tr>
                  <th style={{ paddingLeft: '24px' }}>#</th>
                  <th>Product Details</th>
                  <th className="text-right">List Price</th>
                  <th className="text-center">Quantity</th>
                  <th className="text-right">Actual Cost</th>
                  <th className="text-right">Selling Price</th>
                  <th className="text-right" style={{ paddingRight: '24px' }}>Total Value</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item, idx) => (
                  <tr key={item.id}>
                    <td style={{ paddingLeft: '24px', color: 'var(--text-muted)' }}>{idx + 1}</td>
                    <td>
                      <div style={{ fontWeight: 700, color: 'var(--primary)' }}>{item.item_code || 'N/A'}</div>
                      <div style={{ fontSize: '12px', opacity: 0.8 }}>{item.item_id ? `Product ID: ${item.item_id}` : 'Manual Entry'}</div>
                    </td>
                    <td className="text-right font-mono">{parseFloat(item.list_price).toFixed(2)}</td>
                    <td className="text-center">
                       <span style={{ fontWeight: 800, padding: '4px 10px', background: 'var(--bg-hover)', borderRadius: '6px' }}>{item.qty}</span>
                    </td>
                    <td className="text-right font-mono" style={{ color: 'var(--text-secondary)' }}>{parseFloat(item.actual_cost).toFixed(2)}</td>
                    <td className="text-right font-mono text-success fw-bold">{parseFloat(item.selling_price).toFixed(2)}</td>
                    <td className="text-right font-mono fw-bold text-primary" style={{ paddingRight: '24px', fontSize: '15px' }}>
                      {parseFloat(item.unit_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Remarks Section */}
        {grn.remark && (
          <div className="card shadow-sm mt-4" style={{ background: 'var(--bg-hover)', border: 'none' }}>
            <div className="card-body">
              <div className="text-secondary" style={{ fontSize: '12px', textTransform: 'uppercase', fontWeight: 700, marginBottom: '8px' }}>Internal Remarks</div>
              <p style={{ margin: 0, fontStyle: 'italic', color: 'var(--text-secondary)' }}>"{grn.remark}"</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default GRNDetails;
