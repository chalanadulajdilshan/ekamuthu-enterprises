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

    // Delegated click handler for row click -> open modal with invoices and rented items
    $('#frequencyTableBody').on('click', 'tr.clickable-row', function () {
        const customerId = $(this).data('customer-id');
        const customerName = $(this).data('customer-name');
        const customerCode = $(this).data('customer-code');
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();

        $('#invoiceModalLabel').text('Invoices - ' + (customerName || ''));
        $('#invoiceModalSub').text('Customer Code: ' + (customerCode || '-') + ' | From ' + fromDate + ' to ' + toDate);
        $('#invoiceModalBody').html('<div class="text-center text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading invoices...</div>');

        var modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
        modal.show();

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
                    $('#invoiceModalBody').html(renderInvoices(res.data));
                } else {
                    $('#invoiceModalBody').html(`<div class="alert alert-danger mb-0">${res.message || 'Failed to load invoices.'}</div>`);
                }
            },
            error: function () {
                $('#invoiceModalBody').html('<div class="alert alert-danger mb-0">Server error occurred while loading invoices.</div>');
            }
        });
    });

    function renderInvoices(bills) {
        if (!bills || bills.length === 0) {
            return '<div class="p-3 text-center text-muted">No invoices found for this period.</div>';
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 150px;">Invoice No</th>
                            <th style="width: 120px;">Date</th>
                            <th class="text-end" style="width: 140px;">Total Amount</th>
                            <th>Items Rented</th>
                        </tr>
                    </thead>
                    <tbody>`;

        bills.forEach((bill, index) => {
            const items = bill.items || [];
            let itemHtml = '';
            if (items.length === 0) {
                itemHtml = '<div class="text-muted">No items</div>';
            } else {
                itemHtml = '<ul class="mb-0 ps-3">' + items.map(it => `<li>${it.item_name || 'Item'} - Qty: ${it.quantity || 0}</li>`).join('') + '</ul>';
            }

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td class="fw-bold text-primary">${bill.bill_number}</td>
                    <td>${bill.date}</td>
                    <td class="text-end fw-bold text-success">${Number(bill.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${itemHtml}</td>
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
