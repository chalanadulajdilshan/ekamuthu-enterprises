$(document).ready(function () {
    var table;

    function loadFrequency() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();

        if (!fromDate || !toDate) {
            swal("Error", "Please select a valid date range", "error");
            return;
        }

        if ($.fn.DataTable.isDataTable('#frequencyTable')) {
            $('#frequencyTable').DataTable().destroy();
        }

        $('#frequencyTableBody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: 'ajax/php/equipment-rent-frequency-report.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_top_rent_customers',
                from_date: fromDate,
                to_date: toDate
            },
            success: function (res) {
                if (res.status !== 'success') {
                    swal("Error", res.message || "Failed to load data", "error");
                    return;
                }

                var rows = '';
                var totalRentals = 0;
                var totalAmount = 0;
                if (res.data.length > 0) {
                    res.data.forEach(function (row) {
                        totalRentals += parseInt(row.rent_count || 0, 10);
                        totalAmount += parseFloat(row.total_amount || 0);
                        rows += `
                            <tr class="clickable-row" data-customer-id="${row.customer_id}" data-customer-name="${row.customer_name}" data-customer-code="${row.customer_code}">
                                <td class="dt-control text-center text-primary"><i class="mdi mdi-plus-circle-outline fs-4"></i></td>
                                <td>${row.rank}</td>
                                <td>${row.customer_code || ''}</td>
                                <td>${row.customer_name || ''}</td>
                                <td>${row.mobile_number || ''}</td>
                                <td class="text-center fw-bold">${row.rent_count}</td>
                                <td class="text-end">${Number(row.total_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td>${row.last_rental_date || ''}</td>
                            </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="6" class="text-center">No records found</td></tr>';
                }

                $('#frequencyTableBody').html(rows);

                $('#statTotalCustomers').text(res.data.length);
                $('#statTotalRentals').text(totalRentals);
                $('#statTotalAmount').text(totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#printDateRange').text('From ' + fromDate + ' to ' + toDate);

                table = $('#frequencyTable').DataTable({
                    order: [[5, 'desc']],
                    pageLength: 25,
                    columnDefs: [
                        { targets: 0, orderable: false, searchable: false }
                    ]
                });
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
            }
        });
    }

    $('#searchBtn').on('click', function () {
        loadFrequency();
    });

    $('#resetBtn').on('click', function () {
        location.reload();
    });

    $('#printBtn').on('click', function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();

        if (!fromDate || !toDate) {
            swal("Error", "Please select a valid date range", "error");
            return;
        }

        var url = 'print-equipment-rent-frequency.php?from_date=' + fromDate + '&to_date=' + toDate;
        window.open(url, '_blank');
    });

    // Delegated click handler for expandable rows
    $('#frequencyTableBody').on('click', 'td.dt-control', function (e) {
        e.stopPropagation();
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
            $(this).find('i').removeClass('mdi-minus-circle-outline').addClass('mdi-plus-circle-outline');
        } else {
            // Open this row
            const customerId = tr.data('customer-id');
            const fromDate = $('#fromDate').val();
            const toDate = $('#toDate').val();

            // Show loading placeholder
            row.child('<div class="text-center p-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading bills...</div>').show();
            tr.addClass('shown');
            $(this).find('i').removeClass('mdi-plus-circle-outline').addClass('mdi-minus-circle-outline');

            $.ajax({
                url: 'ajax/php/equipment-rent-frequency-report.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_customer_bills',
                    customer_id: customerId,
                    from_date: fromDate,
                    to_date: toDate
                },
                success: function (res) {
                    if (res.status === 'success') {
                        row.child(formatBillsTable(res.data)).show();
                    } else {
                        row.child(`<div class="text-center p-3 text-danger">Error: ${res.message}</div>`).show();
                    }
                },
                error: function () {
                    row.child('<div class="text-center p-3 text-danger">Failed to load bills data.</div>').show();
                }
            });
        }
    });

    function formatBillsTable(bills) {
        if (!bills || bills.length === 0) {
            return '<div class="p-3 text-center bg-light border">No bills found for this period.</div>';
        }

        let html = `
            <div class="p-4 bg-light border-top border-bottom">
                <h6 class="mb-3"><i class="mdi mdi-receipt me-1"></i> Customer Bills Details</h6>
                <table class="table table-sm table-bordered bg-white mb-0">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 150px;">Bill No</th>
                            <th style="width: 120px;">Date</th>
                            <th class="text-end" style="width: 150px;">Total Amount</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>`;

        bills.forEach((bill, index) => {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td class="fw-bold text-primary">${bill.bill_number}</td>
                    <td>${bill.date}</td>
                    <td class="text-end fw-bold text-success">${Number(bill.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${bill.remarks || '-'}</td>
                </tr>`;
        });

        html += `
                    </tbody>
                </table>
            </div>`;

        return html;
    }

    // Default date range current month
    var now = new Date();
    var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    function fmt(d) {
        var m = '' + (d.getMonth() + 1);
        var day = '' + d.getDate();
        var y = d.getFullYear();
        if (m.length < 2) m = '0' + m;
        if (day.length < 2) day = '0' + day;
        return [y, m, day].join('-');
    }

    $('#fromDate').val(fmt(firstDay));
    $('#toDate').val(fmt(lastDay));

    loadFrequency();
});
