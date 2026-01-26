$(document).ready(function () {

    // Initialize DataTable
    var table = $('#loyaltyCustomerTable').DataTable({
        "processing": true,
        "serverSide": true,
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
        }
        else {
            // Open this row
            var rowData = row.data();
            format(rowData, function (html) {
                row.child(html).show();
                tr.addClass('shown');
                icon.removeClass('uil-plus-circle text-primary').addClass('uil-minus-circle text-danger');
            });
        }
    });

    // Format function for row details
    function format(rowData, callback) {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();

        // Show loading placeholder
        var loadingHtml = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><br>Loading invoices...</div>';

        $.ajax({
            url: 'ajax/php/loyalty-customers.php',
            type: 'POST',
            data: {
                action: 'get_customer_invoices',
                customer_id: rowData.id,
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'JSON',
            success: function (response) {
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
                    callback(html);
                } else {
                    callback('<div class="alert alert-danger m-3">Failed to load details</div>');
                }
            },
            error: function () {
                callback('<div class="alert alert-danger m-3">Error loading details</div>');
            }
        });

        return loadingHtml; // Initial return while waiting for AJAX
    }
});
