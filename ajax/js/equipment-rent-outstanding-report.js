$(document).ready(function () {
    var reportTable;
    var rentDetailsMap = {};

    // Override default customer modal binding (from common.js) to prevent missing-column warnings
    $('#customerModal').off('shown.bs.modal').on('shown.bs.modal', function () {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable('#customerTable')) {
            $('#customerTable').DataTable().destroy();
        }

        $('#customerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/php/customer-master.php',
                type: 'POST',
                data: function (d) {
                    d.filter = true;
                    d.category = 1;
                },
                dataSrc: function (json) {
                    return json.data;
                }
            },
            columns: [
                { data: 'key', title: '#ID' },
                { data: 'code', title: 'Code' },
                { data: 'name', title: 'Name' },
                { data: 'mobile_number', title: 'Mobile Number' },
                { data: 'email', title: 'Email', defaultContent: '' },
                { data: 'vat_no', title: 'VAT', defaultContent: '' },
                { data: 'outstanding', title: 'Outstanding', defaultContent: '0.00' }
            ],
            order: [[0, 'desc']],
            pageLength: 100
        });

        $('#customerTable tbody').off('click').on('click', 'tr', function () {
            var data = $('#customerTable').DataTable().row(this).data();
            if (data) {
                $('#customer_id').val(data.id);
                $('#customer_code').val(data.code);
                $('#customerModal').modal('hide');
            }
        });
    });

    function formatNumber(num) {
        return parseFloat(num || 0).toFixed(2);
    }

    function loadReport() {
        var asOfDate = $('#asOfDate').val();
        var customerId = $('#customer_id').val();

        if (!asOfDate) {
            swal("Error", "Please select As of Date", "error");
            return;
        }

        $('#summaryRow').show();

        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        $('#reportTableBody').html('<tr><td colspan="11" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: "ajax/php/equipment-rent-outstanding-report.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_outstanding_report",
                as_of_date: asOfDate,
                customer_id: customerId
            },
            success: function (result) {
                if (result.status === 'success') {
                    var rows = "";
                    var summary = result.summary || {};
                    var totalProjected = summary.projected_outstanding || '0.00';
                    var totalRecorded = summary.recorded_outstanding || '0.00';

                    $('#totalProjected, #tblTotalProjected').text(totalProjected);
                    $('#totalRecorded, #tblTotalRecorded').text(totalRecorded);

                    rentDetailsMap = {};

                    if (result.data && result.data.length > 0) {
                        result.data.forEach(function (item) {
                            rentDetailsMap[item.rent_id] = item;
                            rows += `
                                <tr>
                                    <td>${item.bill_number}</td>
                                    <td>${item.customer_name}</td>
                                    <td><small>${item.equipment}${item.sub_equipment ? ' / ' + item.sub_equipment : ''}</small></td>
                                    <td>${item.rental_date}</td>
                                    <td>${item.due_date}</td>
                                    <td class="text-end">${item.pending_qty}</td>
                                    <td class="text-end"><span class="badge rounded-pill bg-danger">${item.overdue_days}</span></td>
                                    <td class="text-end">${formatNumber(item.per_unit_daily)}</td>
                                    <td class="text-end fw-bold text-danger">${formatNumber(item.outstanding_amount)}</td>
                                    <td class="text-end text-warning fw-bold">${formatNumber(item.recorded_outstanding)}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary view-details" data-rent="${item.rent_id}">
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        rows = '<tr><td colspan="11" class="text-center">No overdue rentals found</td></tr>';
                    }

                    $('#reportTableBody').html(rows);

                    reportTable = $('#reportTable').DataTable({
                        order: [[6, 'desc']],
                        pageLength: 25,
                        columnDefs: [
                            {
                                orderable: false,
                                targets: [10]
                            }
                        ]
                    });
                } else {
                    swal("Error", result.message || "Failed to load report", "error");
                    $('#reportTableBody').html('<tr><td colspan="11" class="text-center text-danger">No data</td></tr>');
                }
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
                $('#reportTableBody').html('<tr><td colspan="11" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    $('#searchBtn').click(function () {
        loadReport();
    });

    $('#resetBtn').click(function () {
        location.reload();
    });

    $(".date-picker").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });

    // Default As of Date = today
    var today = new Date();
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var dd = String(today.getDate()).padStart(2, '0');
    var yyyy = today.getFullYear();
    var todayStr = yyyy + '-' + mm + '-' + dd;
    $('#asOfDate').val(todayStr);

    // Handle detail modal
    $('#reportTableBody').on('click', '.view-details', function () {
        var rentId = $(this).data('rent');
        var details = rentDetailsMap[rentId];

        if (!details) {
            swal('Not found', 'Unable to load details for this bill.', 'warning');
            return;
        }

        $('#detailsBillNo').text(details.bill_number);
        $('#detailsCustomer').text(details.customer_name);
        $('#detailsProjected').text(formatNumber(details.outstanding_amount));
        $('#detailsRecorded').text(formatNumber(details.recorded_outstanding));

        var outstandingRows = '';
        if (details.outstanding_details && details.outstanding_details.length > 0) {
            details.outstanding_details.forEach(function (out) {
                outstandingRows += `
                    <tr>
                        <td>${out.return_date || '-'}</td>
                        <td>${out.item || '-'}</td>
                        <td class="text-center">${formatNumber(out.return_qty)}</td>
                        <td class="text-end">${formatNumber(out.additional_payment)}</td>
                        <td class="text-end">${formatNumber(out.customer_paid)}</td>
                        <td class="text-end fw-bold text-danger">${formatNumber(out.outstanding_amount)}</td>
                        <td>${out.remark ? out.remark : '-'}</td>
                    </tr>
                `;
            });
        } else {
            outstandingRows = '<tr><td colspan="7" class="text-center text-muted">No recorded outstanding entries.</td></tr>';
        }
        $('#outstandingDetailsBody').html(outstandingRows);

        var paymentRows = '';
        if (details.payments && details.payments.length > 0) {
            details.payments.forEach(function (pay) {
                var refInfo = [];
                if (pay.cheq_no) refInfo.push('Cheque: ' + pay.cheq_no);
                if (pay.ref_no) refInfo.push('Ref: ' + pay.ref_no);
                paymentRows += `
                    <tr>
                        <td>${pay.entry_date || '-'}</td>
                        <td>${pay.receipt_no || '-'}</td>
                        <td>${pay.payment_method || '-'}</td>
                        <td class="text-end fw-bold text-success">${formatNumber(pay.amount)}</td>
                        <td>${refInfo.length ? refInfo.join(' | ') : '-'}</td>
                    </tr>
                `;
            });
        } else {
            paymentRows = '<tr><td colspan="5" class="text-center text-muted">No payments recorded.</td></tr>';
        }
        $('#paymentHistoryBody').html(paymentRows);

        $('#rentDetailsModal').modal('show');
    });

    $('#rentDetailsModal').on('hidden.bs.modal', function () {
        $('#outstandingDetailsBody').empty();
        $('#paymentHistoryBody').empty();
    });

    // Initial load
    loadReport();
});
