let reportTable;

$(document).ready(function () {

    loadReport();

    // Generate Report Button
    $('#generateBtn').click(function() {
        loadReport();
    });

    // Print Report
    $('#printBtn').click(function() {
        var customerId = $('#customer_id').val();
        var url = 'print-outstanding-report.php';
        var searchTerm = '';

        if ($.fn.DataTable.isDataTable('#reportTable')) {
            searchTerm = $('#reportTable').DataTable().search();
        }

        if (customerId) {
            url += '?customer_id=' + customerId;
            if (searchTerm) {
                url += '&q=' + encodeURIComponent(searchTerm);
            }
        } else if (searchTerm) {
            url += '?q=' + encodeURIComponent(searchTerm);
        }
        window.open(url, '_blank');
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

});

function loadReport() {
    var customerId = $('#customer_id').val();

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
                customer_id: customerId
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

function fillBillDetailsModal(data) {
    var fmt = function (val) { return formatAmount(parseAmount(val)); };

    $('#billModalInvoice').text(data.bill_number || '-');
    $('#billModalDate').text(data.rental_date || '-');
    $('#billModalCustomer').text(data.customer_name || '-');
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
    itemsBody.empty();
    if (data.items && data.items.length) {
        data.items.forEach(function (itm) {
            var statusBadge = itm.return_status === 'Returned'
                ? '<span class="badge bg-success">Returned</span>'
                : '<span class="badge bg-warning text-dark">Not Returned</span>';
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

    // Totals in modal summary
    $('#billModalRecordedTotal').text(fmt(data.recorded_outstanding_raw || data.recorded_outstanding));
    $('#billModalProjectedTotal').text(fmt(data.projected_outstanding_raw || data.projected_outstanding));
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
                    </div>
                </div>
            </div>
        </div>
    `;
}
