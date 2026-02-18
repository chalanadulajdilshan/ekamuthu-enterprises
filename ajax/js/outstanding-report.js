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
        if (customerId) {
            url += '?customer_id=' + customerId;
        }
        window.open(url, '_blank');
    });

    // Clear Customer
    $('#clearCustomer').click(function() {
        $('#customer_code').val('');
        $('#customer_id').val('');
        loadReport();
    });

    // Open modal with full bill details when clicking a row (excluding the toggle button)
    $('#reportTable tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('button.row-toggle').length) {
            return;
        }
        var row = reportTable.row($(this));
        if (!row.data()) {
            return;
        }
        showBillDetailsModal(row.data());
    });

    // Clear modal tables when hidden
    $('#billDetailsModal').on('hidden.bs.modal', function () {
        $('#outstandingDetailsBody').empty();
        $('#paymentHistoryBody').empty();
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
                if (json.grand_total_rent) {
                    $('#cardTotalRent').text('Rs. ' + json.grand_total_rent);
                }
                if (json.grand_total_paid) {
                    $('#cardTotalPaid').text('Rs. ' + json.grand_total_paid);
                }
                if (json.grand_total_balance) {
                    $('#cardTotalBalance').text('Rs. ' + json.grand_total_balance);
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
            { "data": "total_rent", "className": "text-end" },
            { "data": "total_paid", "className": "text-end" },
            { "data": "balance", "className": "text-end" }
        ],
        "footerCallback": function (row, data, start, end, display) {
            var api = this.api();

            // Remove formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '') * 1 :
                    typeof i === 'number' ?
                        i : 0;
            };

            // Total over all pages
            var totalRent = api.column(6).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            var totalPaid = api.column(7).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            var totalBalance = api.column(8).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(6).footer()).html(totalRent.toFixed(2));
            $(api.column(7).footer()).html(totalPaid.toFixed(2));
            $(api.column(8).footer()).html(totalBalance.toFixed(2));
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

function showBillDetailsModal(rowData) {
    $('#detailsBillNo').text(rowData.bill_number || '-');
    $('#detailsCustomer').text(rowData.customer_name || '-');
    $('#detailsProjected').text(formatNumber(rowData.projected_outstanding_raw));
    $('#detailsRecorded').text(formatNumber(rowData.recorded_outstanding_raw));

    var outstandingRows = '<tr><td colspan="7" class="text-center text-muted">No recorded outstanding entries.</td></tr>';
    if (rowData.recorded_details && rowData.recorded_details.length > 0) {
        outstandingRows = rowData.recorded_details.map(function (item) {
            return `
                <tr>
                    <td>${item.return_date || '-'}</td>
                    <td>${item.item || '-'}</td>
                    <td class="text-center">${formatNumber(item.return_qty)}</td>
                    <td class="text-end">${formatNumber(item.additional_payment)}</td>
                    <td class="text-end">${formatNumber(item.customer_paid)}</td>
                    <td class="text-end fw-bold text-danger">${formatNumber(item.outstanding_amount)}</td>
                    <td>${item.remark ? item.remark : '-'}</td>
                </tr>
            `;
        }).join('');
    }
    $('#outstandingDetailsBody').html(outstandingRows);

    var paymentRows = '<tr><td colspan="5" class="text-center text-muted">No payments recorded.</td></tr>';
    if (rowData.payments && rowData.payments.length > 0) {
        paymentRows = rowData.payments.map(function (pay) {
            var refInfo = [];
            if (pay.cheq_no) refInfo.push('Cheque: ' + pay.cheq_no);
            if (pay.ref_no) refInfo.push('Ref: ' + pay.ref_no);
            return `
                <tr>
                    <td>${pay.entry_date || '-'}</td>
                    <td>${pay.receipt_no || '-'}</td>
                    <td>${pay.payment_method || '-'}</td>
                    <td class="text-end fw-bold text-success">${formatNumber(pay.amount)}</td>
                    <td>${refInfo.length ? refInfo.join(' | ') : '-'}</td>
                </tr>
            `;
        }).join('');
    }
    $('#paymentHistoryBody').html(paymentRows);

    $('#billDetailsModal').modal('show');
}

function formatNumber(val) {
    var num = Number(val || 0);
    return num.toFixed(2);
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
                    <td class="text-end text-success">${Number(pay.amount || 0).toFixed(2)}</td>
                    <td>${references.join(' | ') || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    var recordedOutstanding = Number(rowData.recorded_outstanding_raw || 0).toFixed(2);
    var projectedOutstanding = Number(rowData.projected_outstanding_raw || 0).toFixed(2);

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
