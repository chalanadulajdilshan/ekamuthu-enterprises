$(document).ready(function () {

    // Initialize DataTable
    var table = $('#loyaltyCustomerTable').DataTable({
        "processing": true,
        "serverSide": true,
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'print',
                text: '<i class="uil uil-print"></i> Print',
                className: 'btn btn-soft-secondary',
                title: 'Loyalty Customers',
                action: function (e, dt, button, config) {
                    var self = this;
                    var rows = dt.rows({ page: 'current' });
                    var promises = [];

                    rows.every(function () {
                        var tr = $(this.node());
                        var rowData = this.data();
                        if (!$(tr).data('child-html')) {
                            promises.push(fetchChildHtml(rowData).then(function (html) {
                                $(tr).data('child-html', html);
                            }));
                        }
                    });

                    $.when.apply($, promises).always(function () {
                        $.fn.dataTable.ext.buttons.print.action.call(self, e, dt, button, config);
                    });
                },
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                },
                customize: function (win) {
                    var startDate = $('#start_date').val();
                    var endDate = $('#end_date').val();
                    var minSales = $('#min_sales').val();

                    var info = '<div style="margin-bottom:10px;">' +
                        '<strong>Period:</strong> ' + startDate + ' to ' + endDate +
                        (minSales ? ' | <strong>Min Sales:</strong> Rs. ' + minSales : '') +
                        '</div>';

                    $(win.document.body).find('h1').after(info);
                    $(win.document.body).css('font-size', '12px');
                    $(win.document.body).find('table').addClass('compact').css('font-size', '12px');

                    // Append expanded child details under each related customer row
                    var printRows = $(win.document.body).find('table tbody tr');
                    table.rows({ page: 'current' }).every(function (idx) {
                        var node = this.node();
                        var childHtml = $(node).data('child-html');
                        if (childHtml) {
                            var printRow = printRows.eq(idx);
                            if (printRow.length) {
                                printRow.after('<tr class="child-row"><td colspan="7">' + childHtml + '</td></tr>');
                            }
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
