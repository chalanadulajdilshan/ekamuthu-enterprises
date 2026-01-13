$(document).ready(function () {
    

    // Select customer on row click
    $(document).on('click', '#customerTable tbody tr', function () {
        const table = $('#customerTable').DataTable();
        const data = table.row(this).data();
        if (!data) return;
        $('#customer_id').val(data.id);
        $('#customer_code').val(data.code);
        $('#customerModal').modal('hide');
    });

    // Default dates: current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    if ($('#fromDate').length) $('#fromDate').val($.datepicker.formatDate('yy-mm-dd', firstDay));
    if ($('#toDate').length) $('#toDate').val($.datepicker.formatDate('yy-mm-dd', today));

    // View button
    $('#viewBtn').on('click', function () {
        loadSettlement();
    });

    // Reset button
    $('#resetBtn').on('click', function () {
        $('#settlementForm')[0].reset();
        $('#customer_id').val('');
        $('#customer_code').val('');
        $('#settlementTableBody').html('<tr><td colspan="3" class="text-center">No data</td></tr>');
        $('#invoiceNos, #firstPaymentDate, #lastSettleDate, #daysBetween').text('-');
    });

    function loadSettlement() {
        const customerId = $('#customer_id').val();
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const status = $('#status').val();
        
        // Show loading state
        $('#outstandingTablesContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading invoices...</p></div>');

        $.ajax({
            url: 'ajax/php/outstand-settlement.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_settlement_data',
                customer_id: customerId,
                from_date: fromDate,
                to_date: toDate,
                status: status
            },
            beforeSend: function () {
                $('#outstandingTablesContainer').html('<div class="text-center">Loading...</div>');
            },
            success: function (response) {
                if (response.status === 'success') {
                    renderSummary(response.summary);
                    renderInvoices(response.invoices);
                } else {
                    const msg = (response && response.message) ? response.message : 'Error loading data';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg,
                        confirmButtonColor: '#3b5de7',
                    });
                    $('#outstandingTablesContainer').empty();
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load data. Please try again.',
                    confirmButtonColor: '#3b5de7',
                });
                $('#outstandingTablesContainer').empty();
            }
        });
    }

    function renderSummary(summary) {
        // This function is kept for backward compatibility
        // The summary is now shown in each invoice card
    }

    function renderInvoices(invoices) {
        const $container = $('#outstandingTablesContainer');
        $container.empty();

        if (!invoices || invoices.length === 0) {
            $container.html('<div class="alert alert-info">No invoices found for this customer</div>');
            return;
        }

        invoices.forEach(invoice => {
            const balance = invoice.invoice_amount - (invoice.total_receipts || 0);
            const cardId = `invoice-${invoice.invoice_no.replace(/[^a-zA-Z0-9]/g, '-')}`;
            
            let html = `
            <div class="card mb-5 shadow-sm" id="${cardId}" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                    <div>
                        <strong>Invoice #${invoice.invoice_no}</strong>
                        <span class="ms-3">Date: ${formatDate(invoice.invoice_date)}</span>
                    </div>
                    <div>
                        <span class="badge bg-${balance <= 0 ? 'success' : 'warning'} fs-6">
                            ${balance <= 0 ? 'Settled' : 'Pending'}
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 summary-box mb-4">
                        <div class="col-md-3">
                            <div class="summary-label">Invoice No</div>
                            <div class="fw-bold">${invoice.invoice_no || '-'}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-label">Invoice Date</div>
                            <div class="fw-bold">${formatDate(invoice.invoice_date) || '-'}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-label">Last Payment Date</div>
                            <div class="fw-bold">${invoice.last_payment_date ? formatDate(invoice.last_payment_date) : '-'}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-label">Days Between</div>
                            <div class="fw-bold">${invoice.days_between || '0'}</div>
                        </div>
                    </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0" style="margin-bottom: 0 !important;">
                            <thead class="table-light">
                                <style>
                                    .card { margin-bottom: 2rem !important; }
                                    .table { margin-bottom: 0 !important; }
                                    .card-body { padding: 1.5rem; }
                                    .card-header { padding: 0.75rem 1.5rem; }
                                    .card-footer { padding: 0.75rem 1.5rem; }
                                    /* Payment method styling */
                                    .payment-cash {
                                        background-color: #d4edda;
                                        color: #155724;
                                        padding: 2px 6px;
                                        border-radius: 4px;
                                        font-weight: 500;
                                        display: inline-block;
                                    }
                                    .payment-cheque {
                                        background-color: #fff3cd;
                                        color: #856404;
                                        padding: 2px 6px;
                                        border-radius: 4px;
                                        font-weight: 500;
                                        display: inline-block;
                                    }
                                    .payment-other {
                                        background-color: #e2e3e5;
                                        color: #383d41;
                                        padding: 2px 6px;
                                        border-radius: 4px;
                                        font-weight: 500;
                                        display: inline-block;
                                    }
                                </style>
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Receipt Date</th>
                                    <th>Payment Method</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>`;

            // Add receipt rows
            if (invoice.receipts && invoice.receipts.length > 0) {
                invoice.receipts.forEach(receipt => {
                    const paymentMethod = receipt.payment_method || 'N/A';
                    const paymentMethodClass = getPaymentMethodClass(paymentMethod);
                    
                    html += `
                                <tr>
                                    <td>${receipt.receipt_no || '-'}</td>
                                    <td>${formatDate(receipt.receipt_date)}</td>
                                    <td><span class="${paymentMethodClass}">${paymentMethod}</span></td>
                                    <td class="text-end">${formatCurrency(receipt.amount || 0)}</td>
                                </tr>`;
                });
            } else {
                html += `
                                <tr>
                                    <td colspan="4" class="text-center">No receipts found for this invoice</td>
                                </tr>`;
            }

            // Add summary row
            html += `
                                <tr class="table-active fw-bold">
                                    <td colspan="3" class="text-end">Total Receipts:</td>
                                    <td class="text-end">${formatCurrency(invoice.total_receipts || 0)}</td>
                                </tr>
                                <tr class="table-${balance <= 0 ? 'success' : 'warning'} fw-bold">
                                    <td colspan="3" class="text-end">Balance:</td>
                                    <td class="text-end">${formatCurrency(balance)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light py-3">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Invoice Amount:</strong> ${formatCurrency(invoice.invoice_amount || 0)}
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Status:</strong> 
                            <span class="badge bg-${balance <= 0 ? 'success' : 'warning'} fs-6 py-2 px-3">
                                ${balance <= 0 ? 'Fully Settled' : formatCurrency(balance)}
                            </span>
                        </div>
                    </div>
                </div>
            </div>`;

            $container.append(html);
        });
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatCurrency(amount) {
        if (amount === null || amount === undefined) return 'LKR 0.00';
        return 'LKR ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function getPaymentMethodClass(paymentMethod) {
        const method = paymentMethod.toLowerCase();
        if (method.includes('cash')) {
            return 'payment-cash';
        } else if (method.includes('cheque')) {
            return 'payment-cheque';
        } else {
            return 'payment-other';
        }
    }
});
