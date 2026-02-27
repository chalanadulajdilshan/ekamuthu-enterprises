let reportTable;

$(document).ready(function () {

    // Default date range: first day of current month to today
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const fmt = $.datepicker.formatDate('yy-mm-dd', firstDay);
    const fmtToday = $.datepicker.formatDate('yy-mm-dd', today);
    $('#from_date').val(fmt);
    $('#to_date').val(fmtToday);

    loadReport();

    // Generate Report Button
    $('#generateBtn').click(function() {
        loadReport();
    });

    // Print Report (detailed)
    $('#printBtn').click(function() {
        openPrintWindow(false);
    });

    // Print Report (summary only)
    $('#printSummaryBtn').click(function() {
        openPrintWindow(true);
    });

    // Clear Customer
    $('#clearCustomer').click(function() {
        $('#customer_code').val('');
        $('#customer_id').val('');
        loadReport();
    });

    // Open details modal when clicking on a row (excluding the toggle button column)
    $('#reportTable tbody').on('click', 'td:not(:first-child)', function () {
        var tr = $(this).closest('tr');
        var row = reportTable.row(tr);
        var data = row.data();

        // Ignore clicks on child rows or empty rows
        if (!data) return;

        fillBillDetailsModal(data);
        $('#billDetailModal').modal('show');
    });

    // Print modal content
    $('#billModalPrint').on('click', function () {
        printBillDetails();
    });

});

function loadReport() {
    var customerId = $('#customer_id').val();
    var fromDate = $('#from_date').val();
    var toDate = $('#to_date').val();

    if ($.fn.DataTable.isDataTable('#reportTable')) {
        $('#reportTable').DataTable().destroy();
        $('#reportTable tbody').off('click', 'button.row-toggle');
    }

    reportTable = $('#reportTable').DataTable({
        "ajax": {
            "url": "ajax/php/outstanding-report.php",
            "type": "POST",
            "data": {
                action: 'get_outstanding_report',
                customer_id: customerId,
                from_date: fromDate,
                to_date: toDate
            },
            "dataSrc": function (json) {
                // Update top summary cards if API provided totals
                if (json.grand_total_rent !== undefined) {
                    $('#cardTotalRent').text('Rs. ' + formatAmount(parseAmount(json.grand_total_rent)));
                }
                if (json.grand_total_paid !== undefined) {
                    $('#cardTotalPaid').text('Rs. ' + formatAmount(parseAmount(json.grand_total_paid)));
                }
                if (json.grand_total_balance !== undefined) {
                    $('#cardTotalBalance').text('Rs. ' + formatAmount(parseAmount(json.grand_total_balance)));
                }
                return json.data || [];
            }
        },
        "order": [[ 2, "desc" ]],
        "columns": [
            {
                "data": null,
                "orderable": false,
                "className": "text-center",
                "defaultContent": '<button class="btn btn-sm btn-link row-toggle" title="View details"><i class="uil uil-plus"></i></button>'
            },
            { "data": "bill_number" },
            { "data": "rental_date" },
            { "data": "payment_type_name" },
            { "data": "customer_name" },
            { 
                "data": "status_label",
                "render": function (data) {
                    return data === 'Returned'
                        ? '<span class="badge bg-success">Returned</span>'
                        : '<span class="badge bg-warning text-dark">Not Returned</span>';
                }
            },
            {
                "data": "total_rent",
                "className": "text-end",
                "render": function (data) { return formatAmount(parseAmount(data)); }
            },
            {
                "data": "total_paid",
                "className": "text-end",
                "render": function (data) { return formatAmount(parseAmount(data)); }
            },
            {
                "data": "balance",
                "className": "text-end",
                "render": function (data) { return formatAmount(parseAmount(data)); }
            }
        ],
        "footerCallback": function (row, data, start, end, display) {
            var api = this.api();

            var totalRent = api.column(6).data().reduce(function (a, b) { return parseAmount(a) + parseAmount(b); }, 0);
            var totalPaid = api.column(7).data().reduce(function (a, b) { return parseAmount(a) + parseAmount(b); }, 0);
            var totalBalance = api.column(8).data().reduce(function (a, b) { return parseAmount(a) + parseAmount(b); }, 0);

            // Update footer
            $(api.column(6).footer()).html(formatAmount(totalRent));
            $(api.column(7).footer()).html(formatAmount(totalPaid));
            $(api.column(8).footer()).html(formatAmount(totalBalance));
        }
    });

    $('#reportTable tbody').on('click', 'button.row-toggle', function () {
        var tr = $(this).closest('tr');
        var row = reportTable.row(tr);
        var icon = $(this).find('i');

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('uil-minus').addClass('uil-plus');
        } else {
            row.child(formatDetail(row.data())).show();
            tr.addClass('shown');
            icon.removeClass('uil-plus').addClass('uil-minus');
        }
    });
}

function openPrintWindow(isSummary) {
    var customerId = $('#customer_id').val();
    var fromDate = $('#from_date').val();
    var toDate = $('#to_date').val();
    var url = 'print-outstanding-report.php';
    var searchTerm = '';

    if ($.fn.DataTable.isDataTable('#reportTable')) {
        searchTerm = $('#reportTable').DataTable().search();
    }

    const params = [];
    if (customerId) {
        params.push('customer_id=' + customerId);
    }
    if (fromDate) {
        params.push('from_date=' + encodeURIComponent(fromDate));
    }
    if (toDate) {
        params.push('to_date=' + encodeURIComponent(toDate));
    }
    if (searchTerm) {
        params.push('q=' + encodeURIComponent(searchTerm));
    }
    if (isSummary) {
        params.push('summary=1');
    }

    if (params.length) {
        url += '?' + params.join('&');
    }

    window.open(url, '_blank');
}

function fillBillDetailsModal(data) {
    var fmt = function (val) { return formatAmount(parseAmount(val)); };

    $('#billModalInvoice').text(data.bill_number || '-');
    $('#billModalDate').text(data.rental_date || '-');

    // Show day count from rental date to today (inclusive)
    var dayCountText = '';
    if (data.rental_date) {
        var startDate = new Date(data.rental_date);
        if (!isNaN(startDate.getTime())) {
            var today = new Date();
            var startMidnight = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
            var todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            var diffMs = todayMidnight - startMidnight;
            var days = Math.max(0, Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1); // inclusive of start date
            dayCountText = '(' + days + ' day' + (days !== 1 ? 's' : '') + ')';
        }
    }
    $('#billModalDayCount').text(dayCountText);
    var contactParts = [];
    if (data.customer_mobile) contactParts.push(data.customer_mobile);
    if (data.customer_mobile_2) contactParts.push(data.customer_mobile_2);
    var contactText = contactParts.length ? ' (' + contactParts.join(' / ') + ')' : '';
    $('#billModalCustomer').text((data.customer_name || '-') + contactText);
    $('#billModalPayment').text(data.payment_type_name || '-');
    $('#billModalStatus').html(data.status_label === 'Returned'
        ? '<span class="badge bg-success">Returned</span>'
        : '<span class="badge bg-warning text-dark">Not Returned</span>');

    $('#billModalTotalRent').text(fmt(data.total_rent));
    $('#billModalTotalPaid').text(fmt(data.total_paid));
    $('#billModalBalance').text(fmt(data.balance));

    // Recorded outstanding table
    var recordedBody = $('#billModalRecorded tbody');
    recordedBody.empty();
    if (data.recorded_details && data.recorded_details.length) {
        data.recorded_details.forEach(function (item) {
            recordedBody.append(`
                <tr>
                    <td>${item.return_date || '-'}</td>
                    <td>${item.item || '-'}</td>
                    <td class="text-end">${fmt(item.outstanding_amount || 0)}</td>
                    <td class="text-end text-success">${fmt(item.customer_paid || 0)}</td>
                    <td>${item.remark || '-'}</td>
                </tr>
            `);
        });
    } else {
        recordedBody.append('<tr><td colspan="5" class="text-muted">No recorded outstanding entries.</td></tr>');
    }

    // Bill items table
    var itemsBody = $('#billModalItems tbody');
    var itemsTotalCell = $('#billModalItemsTotal');
    itemsBody.empty();
    var itemsTotal = 0;
    if (data.items && data.items.length) {
        data.items.forEach(function (itm) {
            var statusBadge = itm.return_status === 'Returned'
                ? '<span class="badge bg-success">Returned</span>'
                : '<span class="badge bg-warning text-dark">Not Returned</span>';
            var amt = parseAmount(itm.amount);
            itemsTotal += isNaN(amt) ? 0 : amt;
            itemsBody.append(`
                <tr>
                    <td>${itm.item || '-'}</td>
                    <td class="text-center">${itm.quantity || 0}</td>
                    <td class="text-center">${itm.duration || 0}</td>
                    <td class="text-center">${itm.rent_type || '-'}</td>
                    <td class="text-center">${itm.returned_qty || 0}</td>
                    <td class="text-center">${itm.pending_qty || 0}</td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-end">${fmt(itm.amount)}</td>
                </tr>
            `);
        });
    } else {
        itemsBody.append('<tr><td colspan="7" class="text-muted">No items found.</td></tr>');
    }
    itemsTotalCell.text(formatAmount(itemsTotal));

    // Payments table
    var paymentBody = $('#billModalPayments tbody');
    paymentBody.empty();
    if (data.payments && data.payments.length) {
        data.payments.forEach(function (pay) {
            var references = [];
            if (pay.payment_method) references.push(pay.payment_method);
            if (pay.cheq_no) references.push('Cheque: ' + pay.cheq_no);
            if (pay.ref_no) references.push('Ref: ' + pay.ref_no);
            paymentBody.append(`
                <tr>
                    <td>${pay.entry_date || '-'}</td>
                    <td>${pay.receipt_no || '-'}</td>
                    <td class="text-end text-success">${fmt(pay.amount || 0)}</td>
                    <td>${references.join(' | ') || '-'}</td>
                </tr>
            `);
        });
    } else {
        paymentBody.append('<tr><td colspan="4" class="text-muted">No payments recorded.</td></tr>');
    }

    // Deposit payments table
    var depositBody = $('#billModalDeposits tbody');
    var depositTotalCell = $('#billModalDepositTotal');
    depositBody.empty();
    var depositTotal = 0;
    if (data.deposits && data.deposits.length) {
        data.deposits.forEach(function (dep) {
            var depAmt = parseAmount(dep.amount || 0);
            if (!isNaN(depAmt)) depositTotal += depAmt;
            depositBody.append(`
                <tr>
                    <td>${dep.payment_date || '-'}</td>
                    <td>${dep.remark || '-'}</td>
                    <td class="text-end text-success">${fmt(dep.amount || 0)}</td>
                </tr>
            `);
        });
    } else {
        depositBody.append('<tr><td colspan="3" class="text-muted">No deposit payments recorded.</td></tr>');
    }

    if (depositTotalCell.length) {
        depositTotalCell.text(formatAmount(depositTotal));
    }

    // Return history table
    var returnBody = $('#billModalReturns tbody');
    returnBody.empty();
    if (data.return_history && data.return_history.length) {
        data.return_history.forEach(function (ret) {
            var settlementLabel = ret.settlement_type === 'pay'
                ? '<span class="text-danger">Pay</span>'
                : (ret.settlement_type === 'refund' ? '<span class="text-success">Refund</span>' : '-');

            returnBody.append(`
                <tr>
                    <td>${ret.return_date || '-'}</td>
                    <td>${ret.return_time || '-'}</td>
                    <td>${ret.item || '-'}</td>
                    <td class="text-center">${ret.quantity || 0}</td>
                    <td class="text-end">${fmt(ret.rental_amount || 0)}</td>
                    <td class="text-end">${fmt(ret.extra_day_amount || 0)}</td>
                    <td class="text-end">${fmt(ret.penalty_amount || 0)}</td>
                    <td class="text-end">${fmt(ret.damage_amount || 0)}</td>
                    <td class="text-end">${fmt(ret.extra_charge_amount || 0)}</td>
                    <td class="text-end">${fmt(ret.repair_cost || 0)}</td>
                    <td class="text-end">${settlementLabel} ${fmt(ret.settlement_amount || 0)}</td>
                    <td class="text-end text-success">${fmt(ret.paid || 0)}</td>
                    <td class="text-end text-danger">${fmt(ret.outstanding || 0)}</td>
                    <td>${ret.remark || '-'}</td>
                </tr>
            `);
        });
    } else {
        returnBody.append('<tr><td colspan="14" class="text-muted">No return history found.</td></tr>');
    }

    // Totals in modal summary
    $('#billModalRecordedTotal').text(fmt(data.recorded_outstanding_raw || data.recorded_outstanding));
    $('#billModalProjectedTotal').text(fmt(data.projected_outstanding_raw || data.projected_outstanding));
}

function printBillDetails() {
    var modalBody = $('#billDetailModal .modal-body').html();
    var title = $('#billDetailModalLabel').text();

    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>${title}</title>
            <link rel="stylesheet" href="assets/css/bootstrap.min.css">
            <style>
                body { padding: 20px; font-size: 13px; }
                .table { width: 100%; }
                .table th, .table td { vertical-align: middle; }
            </style>
        </head>
        <body>
            <h4 class="mb-3">${title}</h4>
            ${modalBody}
            <script>
                window.onload = function() {
                    window.focus();
                    window.print();
                    window.onafterprint = function() { window.close(); };
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function formatDetail(rowData) {
    var recordedSection = '<tr><td colspan="5" class="text-muted">No recorded outstanding entries.</td></tr>';
    if (rowData.recorded_details && rowData.recorded_details.length > 0) {
        recordedSection = rowData.recorded_details.map(function (item) {
            return `
                <tr>
                    <td>${item.return_date || '-'}</td>
                    <td>${item.item || '-'}</td>
                    <td class="text-end">${Number(item.outstanding_amount || 0).toFixed(2)}</td>
                    <td class="text-end text-success">${Number(item.customer_paid || 0).toFixed(2)}</td>
                    <td>${item.remark || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    var depositSection = '<tr><td colspan="3" class="text-muted">No deposit payments recorded.</td></tr>';
    if (rowData.deposits && rowData.deposits.length > 0) {
        depositSection = rowData.deposits.map(function (dep) {
            return `
                <tr>
                    <td>${dep.payment_date || '-'}</td>
                    <td class="text-end text-success">${formatAmount(parseAmount(dep.amount || 0))}</td>
                    <td>${dep.remark || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    var paymentSection = '<tr><td colspan="5" class="text-muted">No payments recorded.</td></tr>';
    if (rowData.payments && rowData.payments.length > 0) {
        paymentSection = rowData.payments.map(function (pay) {
            var references = [];
            if (pay.payment_method) references.push(pay.payment_method);
            if (pay.cheq_no) references.push('Cheque: ' + pay.cheq_no);
            if (pay.ref_no) references.push('Ref: ' + pay.ref_no);
            return `
                <tr>
                    <td>${pay.entry_date || '-'}</td>
                    <td>${pay.receipt_no || '-'}</td>
                    <td class="text-end text-success">${formatAmount(parseAmount(pay.amount || 0))}</td>
                    <td>${references.join(' | ') || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    var recordedOutstanding = formatAmount(parseAmount(rowData.recorded_outstanding_raw || 0));
    var projectedOutstanding = formatAmount(parseAmount(rowData.projected_outstanding_raw || 0));

    return `
        <div class="detail-container">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <h6 class="fw-bold mb-2">Recorded Outstanding</h6>
                        <p class="mb-2"><strong>Total:</strong> Rs. ${recordedOutstanding}</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Return Date</th>
                                        <th>Item</th>
                                        <th class="text-end">Outstanding</th>
                                        <th class="text-end">Paid</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>${recordedSection}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <h6 class="fw-bold mb-2">Payment History</h6>
                        <p class="mb-2"><strong>Projected Outstanding:</strong> Rs. ${projectedOutstanding}</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Receipt No</th>
                                        <th class="text-end">Amount</th>
                                        <th>Method / Reference</th>
                                    </tr>
                                </thead>
                                <tbody>${paymentSection}</tbody>
                            </table>
                        </div>
                        <h6 class="fw-bold mb-2 mt-3">Deposit Payments</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>${depositSection}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}
