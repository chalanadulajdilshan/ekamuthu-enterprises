$(document).ready(function () {

    // Build common header info for print views
    function buildPrintInfo() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        var minSales = $('#min_sales').val();

        return '<div style="margin-bottom:10px;">' +
            '<strong>Period:</strong> ' + startDate + ' to ' + endDate +
            (minSales ? ' | <strong>Min Sales:</strong> Rs. ' + minSales : '') +
            '</div>';
    }

    // Initialize DataTable
    var table = $('#loyaltyCustomerTable').DataTable({
        "processing": true,
        "serverSide": true,
        "dom": 'Bfrtip',
        "buttons": [
            {
                text: '<i class="uil uil-print"></i> Print (Summary)',
                className: 'btn btn-soft-secondary',
                action: function (e, dt, button, config) {
                    // Fetch all data from server for printing
                    $.ajax({
                        url: 'ajax/php/loyalty-customers.php',
                        type: 'POST',
                        data: {
                            filter: true,
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val(),
                            min_sales: $('#min_sales').val(),
                            start: 0,
                            length: -1, // Get all records
                            draw: 1
                        },
                        dataType: 'JSON',
                        success: function (response) {
                            // Build print HTML
                            var printWindow = window.open('', '', 'height=600,width=800');
                            var info = buildPrintInfo();
                            
                            var html = '<html><head><title>Loyalty Customers</title>';
                            html += '<style>body { font-size: 12px; } table { border-collapse: collapse; width: 100%; font-size: 12px; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } .text-center { text-align: center; } .text-end { text-align: right; } .fw-bold { font-weight: bold; }</style>';
                            html += '</head><body>';
                            html += '<h1>Loyalty Customers</h1>';
                            html += info;
                            html += '<table><thead><tr><th>#</th><th>Code</th><th>Customer Name</th><th>Mobile Number</th><th class="text-center">Total Bills</th><th class="text-end">Total Amount (Rs.)</th><th class="text-center">Points</th></tr></thead><tbody>';
                            
                            $.each(response.data, function (i, row) {
                                html += '<tr>';
                                html += '<td>' + row.key + '</td>';
                                html += '<td>' + row.code + '</td>';
                                html += '<td>' + row.name + '</td>';
                                html += '<td>' + row.mobile + '</td>';
                                html += '<td class="text-center">' + row.bill_count + '</td>';
                                html += '<td class="text-end fw-bold">' + row.total_value + '</td>';
                                html += '<td class="text-center fw-bold">' + row.points + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table></body></html>';
                            
                            printWindow.document.write(html);
                            printWindow.document.close();
                            printWindow.focus();
                            setTimeout(function() {
                                printWindow.print();
                                printWindow.close();
                            }, 250);
                        }
                    });
                }
            },
            {
                text: '<i class="uil uil-print"></i> Print (with Bills)',
                className: 'btn btn-soft-secondary',
                action: function (e, dt, button, config) {
                    // Fetch all data from server for printing
                    $.ajax({
                        url: 'ajax/php/loyalty-customers.php',
                        type: 'POST',
                        data: {
                            filter: true,
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val(),
                            min_sales: $('#min_sales').val(),
                            start: 0,
                            length: -1, // Get all records
                            draw: 1
                        },
                        dataType: 'JSON',
                        success: function (response) {
                            // Fetch invoices for all customers
                            var promises = [];
                            var customersWithInvoices = [];
                            
                            $.each(response.data, function (i, row) {
                                var promise = $.ajax({
                                    url: 'ajax/php/loyalty-customers.php',
                                    type: 'POST',
                                    data: {
                                        action: 'get_customer_invoices',
                                        customer_id: row.id,
                                        start_date: $('#start_date').val(),
                                        end_date: $('#end_date').val()
                                    },
                                    dataType: 'JSON'
                                }).then(function (invoiceResponse) {
                                    customersWithInvoices.push({
                                        customer: row,
                                        invoices: invoiceResponse.data
                                    });
                                });
                                promises.push(promise);
                            });
                            
                            // After all invoices are fetched, build print HTML
                            $.when.apply($, promises).always(function () {
                                var printWindow = window.open('', '', 'height=600,width=800');
                                var info = buildPrintInfo();
                                
                                var html = '<html><head><title>Loyalty Customers with Bills</title>';
                                html += '<style>body { font-size: 12px; } table { border-collapse: collapse; width: 100%; font-size: 12px; margin-bottom: 20px; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } .text-center { text-align: center; } .text-end { text-align: right; } .fw-bold { font-weight: bold; } .invoice-table { margin-left: 20px; margin-top: 10px; margin-bottom: 10px; } .invoice-table th { background-color: #e8e8e8; }</style>';
                                html += '</head><body>';
                                html += '<h1>Loyalty Customers with Bills</h1>';
                                html += info;
                                html += '<table><thead><tr><th>#</th><th>Code</th><th>Customer Name</th><th>Mobile Number</th><th class="text-center">Total Bills</th><th class="text-end">Total Amount (Rs.)</th><th class="text-center">Points</th></tr></thead><tbody>';
                                
                                $.each(customersWithInvoices, function (i, item) {
                                    var row = item.customer;
                                    html += '<tr>';
                                    html += '<td>' + row.key + '</td>';
                                    html += '<td>' + row.code + '</td>';
                                    html += '<td>' + row.name + '</td>';
                                    html += '<td>' + row.mobile + '</td>';
                                    html += '<td class="text-center">' + row.bill_count + '</td>';
                                    html += '<td class="text-end fw-bold">' + row.total_value + '</td>';
                                    html += '<td class="text-center fw-bold">' + row.points + '</td>';
                                    html += '</tr>';
                                    
                                    // Add invoices
                                    if (item.invoices && item.invoices.length > 0) {
                                        html += '<tr><td colspan="7">';
                                        html += '<table class="invoice-table"><thead><tr><th>Bill Number</th><th>Date</th><th class="text-end">Amount (Rs.)</th><th>Status</th></tr></thead><tbody>';
                                        $.each(item.invoices, function (j, invoice) {
                                            html += '<tr>';
                                            html += '<td>' + invoice.bill_number + '</td>';
                                            html += '<td>' + invoice.date + '</td>';
                                            html += '<td class="text-end">' + invoice.total_amount + '</td>';
                                            html += '<td>' + invoice.status_label + '</td>';
                                            html += '</tr>';
                                        });
                                        html += '</tbody></table>';
                                        html += '</td></tr>';
                                    }
                                });
                                
                                html += '</tbody></table></body></html>';
                                
                                printWindow.document.write(html);
                                printWindow.document.close();
                                printWindow.focus();
                                setTimeout(function() {
                                    printWindow.print();
                                    printWindow.close();
                                }, 250);
                            });
                        }
                    });
                }
            }
        ],
        "ajax": {
            "url": "ajax/php/loyalty-customers.php",
            "type": "POST",
            "data": function (d) {
                d.filter = true;
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.min_sales = $('#min_sales').val();
            }
        },
        "columns": [
            {
                "className": 'details-control',
                "orderable": false,
                "data": null,
                "defaultContent": '<i class="uil uil-plus-circle text-primary font-size-20" style="cursor: pointer;"></i>'
            },
            { "data": "key" },
            { "data": "code" },
            { "data": "name" },
            { "data": "mobile" },
            {
                "data": "bill_count",
                "className": "text-center",
                "render": function (data, type, row) {
                    return '<span class="badge bg-soft-info font-size-14">' + data + '</span>';
                }
            },
            {
                "data": "total_value",
                "className": "text-end fw-bold"
            },
            {
                "data": "points",
                "className": "text-center fw-bold text-primary"
            },
            {
                "data": "action",
                "className": "text-center",
                "orderable": false
            }
        ],
        "order": [[5, 'desc']] // Sort by Bill Count DESC by default
    });

    // Filter Button Click
    $('#filterBtn').click(function () {
        table.ajax.reload();
    });

    // Open Add Points Modal
    $('#loyaltyCustomerTable tbody').on('click', '.add-points-btn', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');

        $('#point_customer_id').val(id);
        $('#point_customer_name').val(name);
        $('#point_value').val('');
        $('#point_description').val('');
        $('#point_type').val('earn');

        $('#loyaltyPointsModal').modal('show');
    });

    // Save Points
    $('#savePointsBtn').click(function () {
        var customerId = $('#point_customer_id').val();
        var points = $('#point_value').val();
        var type = $('#point_type').val();
        var description = $('#point_description').val();

        if (!points || points <= 0) {
            swal("Error", "Please enter valid points", "error");
            return;
        }

        $.ajax({
            url: 'ajax/php/loyalty-customers.php',
            type: 'POST',
            data: {
                action: 'save_points',
                customer_id: customerId,
                points: points,
                type: type,
                description: description
            },
            dataType: 'JSON',
            success: function (response) {
                if (response.status === 'success') {
                    $('#loyaltyPointsModal').modal('hide');
                    swal("Success", "Points transaction saved successfully", "success");
                    table.ajax.reload(null, false);
                } else {
                    swal("Error", response.message, "error");
                }
            },
            error: function () {
                swal("Error", "Server Error", "error");
            }
        });
    });

    // Add event listener for opening and closing details
    $('#loyaltyCustomerTable tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        var icon = $(this).find('i');

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('uil-minus-circle text-danger').addClass('uil-plus-circle text-primary');
            $(tr).removeData('child-html');
        }
        else {
            // Open this row
            var rowData = row.data();
            row.child(loadingPlaceholder()).show();
            fetchChildHtml(rowData).then(function (html) {
                row.child(html).show();
                tr.addClass('shown');
                icon.removeClass('uil-plus-circle text-primary').addClass('uil-minus-circle text-danger');
                $(tr).data('child-html', html);
            }).fail(function () {
                row.child('<div class="alert alert-danger m-3">Error loading details</div>').show();
            });
        }
    });

    // Build loading placeholder
    function loadingPlaceholder() {
        return '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><br>Loading invoices...</div>';
    }

    // Fetch detail HTML (returns Promise)
    function fetchChildHtml(rowData) {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        return $.ajax({
            url: 'ajax/php/loyalty-customers.php',
            type: 'POST',
            data: {
                action: 'get_customer_invoices',
                customer_id: rowData.id,
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'JSON'
        }).then(function (response) {
            if (response.status === 'success') {
                var html = '<div class="p-3 bg-light rounded">';
                html += '<h6 class="mb-3">Invoice Details (' + startDate + ' to ' + endDate + ')</h6>';
                html += '<table class="table table-sm table-bordered bg-white mb-0">';
                html += '<thead class="table-light"><tr><th>Bill No</th><th>Date</th><th class="text-end">Amount</th><th>Status</th></tr></thead>';
                html += '<tbody>';

                if (response.data.length > 0) {
                    $.each(response.data, function (i, inv) {
                        html += '<tr>';
                        html += '<td>' + inv.bill_number + '</td>';
                        html += '<td>' + inv.date + '</td>';
                        html += '<td class="text-end">' + inv.total_amount + '</td>';
                        html += '<td>' + inv.status_label + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="4" class="text-center">No invoices found within this period</td></tr>';
                }

                html += '</tbody></table></div>';
                return html;
            } else {
                return '<div class="alert alert-danger m-3">Failed to load details</div>';
            }
        }, function () {
            return '<div class="alert alert-danger m-3">Error loading details</div>';
        });
    }
});
