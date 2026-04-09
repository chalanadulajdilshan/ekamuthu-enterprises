import React, { useState, useEffect } from 'react';
import { 
  FiFileText, FiCalendar, FiUser, FiSearch, FiFilter, 
  FiTrendingUp, FiShoppingBag, FiTag, FiPrinter, FiEye, FiX 
} from 'react-icons/fi';
import { getSalesReport, getCustomers } from '../services/api';
import SearchableSelectModal from './SearchableSelectModal';
import Swal from 'sweetalert2';

const SalesReport = () => {
  const [loading, setLoading] = useState(false);
  const [sales, setSales] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [showCustomerModal, setShowCustomerModal] = useState(false);
  
  // Filters
  const [filters, setFilters] = useState({
    from: new Date().toISOString().split('T')[0],
    to: new Date().toISOString().split('T')[0],
    customer_id: 'all',
    customer_name: 'All Customers'
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  const fetchInitialData = async () => {
    try {
      const res = await getCustomers();
      setCustomers(res.data?.data || []);
      handleSearch(); // Fetch report with default dates
    } catch (err) {
      console.error('Error loading report initial data:', err);
    }
  };

  const handleSearch = async () => {
    try {
      setLoading(true);
      const res = await getSalesReport({
        from: filters.from,
        to: filters.to,
        customer_id: filters.customer_id
      });
      setSales(res.data?.data || []);
    } catch (err) {
      console.error('Error fetching sales report:', err);
      Swal.fire({
        icon: 'error',
        title: 'Report Failed',
        text: 'Could not generate the sales report. Please check your connection.'
      });
    } finally {
      setLoading(false);
    }
  };

  const calculateTotals = () => {
    return sales.reduce((acc, curr) => {
      acc.grandTotal += parseFloat(curr.grand_total || 0);
      acc.discountTotal += parseFloat(curr.discount || 0);
      acc.count += 1;
      return acc;
    }, { grandTotal: 0, discountTotal: 0, count: 0 });
  };

  const totals = calculateTotals();

  const handlePrint = () => {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
      <html>
        <head>
          <title>Sales Report - ${filters.from} to ${filters.to}</title>
          <style>
            body { font-family: sans-serif; padding: 20px; color: #333; }
            h1 { text-align: center; margin-bottom: 5px; }
            .report-meta { text-align: center; margin-bottom: 20px; font-size: 14px; color: #666; }
            .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
            .summary-card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center; }
            .card-label { font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; color: #888; }
            .card-value { font-size: 20px; font-weight: 800; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #eee; padding: 10px; text-align: left; font-size: 12px; }
            th { background: #f4f4f4; text-transform: uppercase; font-weight: 700; }
            .text-right { text-align: right; }
            .total-row { background: #eee; font-weight: 800; }
          </style>
        </head>
        <body>
          <h1>Sales Performance Report</h1>
          <div class="report-meta">
            Period: ${filters.from} to ${filters.to} | Customer: ${filters.customer_name}
          </div>
          
          <div class="summary-grid">
            <div class="summary-card">
              <div class="card-label">Total Revenue</div>
              <div class="card-value">Rs. ${totals.grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
            </div>
            <div class="summary-card">
              <div class="card-label">Total Invoices</div>
              <div class="card-value">${totals.count}</div>
            </div>
            <div class="summary-card">
              <div class="card-label">Total Discounts</div>
              <div class="card-value">Rs. ${totals.discountTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
            </div>
          </div>

          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Invoice No</th>
                <th>Customer</th>
                <th>Department</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Grand Total</th>
              </tr>
            </thead>
            <tbody>
              ${sales.map(s => `
                <tr>
                  <td>${new Date(s.invoice_date).toLocaleDateString()}</td>
                  <td>${s.invoice_no}</td>
                  <td>${s.customer_name || 'Walk-in'}</td>
                  <td>${s.department_name}</td>
                  <td class="text-right">${parseFloat(s.discount).toFixed(2)}</td>
                  <td class="text-right">${parseFloat(s.grand_total).toFixed(2)}</td>
                </tr>
              `).join('')}
              <tr class="total-row">
                <td colspan="4" class="text-right">REPORT TOTALS</td>
                <td class="text-right">${totals.discountTotal.toFixed(2)}</td>
                <td class="text-right">Rs. ${totals.grandTotal.toFixed(2)}</td>
              </tr>
            </tbody>
          </table>
          <script>window.onload = function(){ window.print(); window.close(); }<\/script>
        </body>
      </html>
    `);
    printWindow.document.close();
  };

  return (
    <div className="page animate-fade-in">
      <div className="page-header-row animate-fade-in" style={{ marginBottom: '24px', alignItems: 'center' }}>
        <div>
          <h1 className="page-title" style={{ fontSize: 28 }}>Sales Reports</h1>
          <p className="page-subtitle">Historical performance and revenue analysis</p>
        </div>
        <div className="page-actions">
           <button className="btn btn-secondary" onClick={handlePrint} disabled={sales.length === 0}>
             <FiPrinter style={{ marginRight: '8px' }} /> Print Report
           </button>
        </div>
      </div>

      {/* Filter Card */}
      <div className="card shadow-md mb-4 animate-fade-in">
        <div className="card-header">
           <div className="card-title"><FiFilter /> Analysis Filters</div>
        </div>
        <div className="card-body">
           <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr auto', gap: '20px', alignItems: 'end' }}>
             <div className="form-group">
               <label className="form-label">From Date</label>
               <div className="input-with-icon">
                 <FiCalendar className="input-icon" />
                 <input 
                   type="date" 
                   className="form-input" 
                   value={filters.from}
                   onChange={(e) => setFilters(prev => ({ ...prev, from: e.target.value }))}
                 />
               </div>
             </div>
             
             <div className="form-group">
               <label className="form-label">To Date</label>
               <div className="input-with-icon">
                 <FiCalendar className="input-icon" />
                 <input 
                   type="date" 
                   className="form-input" 
                   value={filters.to}
                   onChange={(e) => setFilters(prev => ({ ...prev, to: e.target.value }))}
                 />
               </div>
             </div>

             <div className="form-group">
               <label className="form-label">Customer Selection</label>
               <button 
                type="button" 
                className="selection-trigger" 
                onClick={() => setShowCustomerModal(true)}
               >
                 <span className="selection-trigger-value">
                   <FiUser style={{ marginRight: 8, opacity: 0.6 }} />
                   {filters.customer_name}
                 </span>
                 <FiSearch className="trigger-icon" />
               </button>
             </div>

             <div className="form-group">
               <button 
                className="btn btn-primary" 
                onClick={handleSearch} 
                disabled={loading}
                style={{ height: '48px', padding: '0 32px' }}
               >
                 {loading ? 'Crunching...' : <><FiTrendingUp style={{ marginRight: 8 }} /> Generate Report</>}
               </button>
             </div>
           </div>
        </div>
      </div>

      {/* Metrics Bar */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '24px', marginBottom: '24px' }}>
        <div className="card shadow-sm" style={{ borderLeft: '4px solid var(--success)' }}>
           <div className="card-body">
             <div className="text-secondary" style={{ fontSize: '13px', textTransform: 'uppercase', fontWeight: 800, marginBottom: '4px' }}>Total Revenue</div>
             <div style={{ fontSize: '28px', fontWeight: 900, color: 'var(--success)' }}>Rs. {totals.grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
             <div className="text-sm" style={{ opacity: 0.6 }}>Gross amount collected</div>
           </div>
        </div>
        <div className="card shadow-sm" style={{ borderLeft: '4px solid var(--primary)' }}>
           <div className="card-body">
             <div className="text-secondary" style={{ fontSize: '13px', textTransform: 'uppercase', fontWeight: 800, marginBottom: '4px' }}>Transaction Volume</div>
             <div style={{ fontSize: '28px', fontWeight: 900, color: 'var(--primary)' }}>{totals.count}</div>
             <div className="text-sm" style={{ opacity: 0.6 }}>Total number of invoices</div>
           </div>
        </div>
        <div className="card shadow-sm" style={{ borderLeft: '4px solid var(--danger)' }}>
           <div className="card-body">
             <div className="text-secondary" style={{ fontSize: '13px', textTransform: 'uppercase', fontWeight: 800, marginBottom: '4px' }}>Total Savings</div>
             <div style={{ fontSize: '28px', fontWeight: 900, color: 'var(--danger)' }}>Rs. {totals.discountTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
             <div className="text-sm" style={{ opacity: 0.6 }}>Customer discounts granted</div>
           </div>
        </div>
      </div>

      {/* Results Table */}
      <div className="card shadow-md">
        <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div className="card-title"><FiShoppingBag /> Transaction Breakdown</div>
          <span className="badge badge-info">{sales.length} Records Found</span>
        </div>
        <div className="table-responsive">
          <table className="table">
             <thead>
               <tr>
                 <th style={{ paddingLeft: 24 }}>Date / Time</th>
                 <th>Invoice #</th>
                 <th>Customer Name</th>
                 <th>Branch/Dept</th>
                 <th className="text-right">Discount</th>
                 <th className="text-right" style={{ paddingRight: 24 }}>Grand Total</th>
               </tr>
             </thead>
             <tbody>
               {loading ? (
                 <tr><td colSpan="6" className="text-center py-5">
                   Crunching Sales Data...
                 </td></tr>
               ) : sales.length === 0 ? (
                 <tr><td colSpan="6" className="text-center py-5 text-secondary">
                   No transactions found for the selected criteria. Try adjusting your filters.
                 </td></tr>
               ) : (
                 sales.map(s => (
                   <tr key={s.id} className="animate-fade-in">
                     <td style={{ paddingLeft: 24 }}>
                       <div style={{ fontWeight: 600 }}>{new Date(s.invoice_date).toLocaleDateString()}</div>
                       <div className="text-sm text-secondary">{new Date(s.invoice_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                     </td>
                     <td><span className="badge badge-primary">{s.invoice_no}</span></td>
                     <td style={{ fontWeight: 600 }}>{s.customer_name || 'Walk-in'}</td>
                     <td>{s.department_name}</td>
                     <td className="text-right font-mono text-danger">-{parseFloat(s.discount).toFixed(2)}</td>
                     <td className="text-right font-mono fw-bold" style={{ paddingRight: 24, color: 'var(--success)', fontSize: 15 }}>
                       Rs. {parseFloat(s.grand_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                     </td>
                   </tr>
                 ))
               )}
             </tbody>
          </table>
        </div>
      </div>

      <SearchableSelectModal 
        isOpen={showCustomerModal}
        title="Filter by Customer"
        data={[{ id: 'all', name: 'All Customers', code: 'ALL' }, ...customers]}
        onClose={() => setShowCustomerModal(false)}
        onSelect={(c) => {
          setFilters(prev => ({ ...prev, customer_id: c.id, customer_name: c.name }));
          setShowCustomerModal(false);
        }}
        renderItem={(c) => c.id === 'all' ? <strong>All Customers</strong> : `[${c.code}] ${c.name}`}
      />
    </div>
  );
};

export default SalesReport;
