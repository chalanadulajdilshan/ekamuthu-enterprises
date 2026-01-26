$(document).ready(function () {

    // Initialize DataTable
    var table = $('#blacklisted-table').DataTable({
        "ajax": {
            "url": "ajax/php/report-blacklisted.php",
            "type": "POST",
            "data": { "action": "get_blacklisted_customers" }
        },
        "columns": [
            {
                "className": 'details-control',
                "orderable": false,
                "data": null,
                "defaultContent": '<div style="text-align:center;"><i class="uil uil-plus-circle text-success" style="font-size: 20px;"></i></div>'
            },
            { "data": "code" },
            { "data": "name" },
            { "data": "mobile_number" },
            { "data": "nic" },
            {
                "data": "blacklist_reason",
                "render": function (data, type, row) {
                    return data ? '<span class="text-danger">' + data + '</span>' : '-';
                }
            }
        ],
        "order": [[1, 'asc']]
    });

    // Add event listener for opening and closing details
    $('#blacklisted-table tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        var icon = $(this).find('i');

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('uil-minus-circle text-danger').addClass('uil-plus-circle text-success');
        }
        else {
            // Open this row
            format(row.data(), function (childContent) {
                row.child(childContent).show();
                tr.addClass('shown');
                icon.removeClass('uil-plus-circle text-success').addClass('uil-minus-circle text-danger');
            });
        }
    });

    // Format function for child row details
    function format(rowData, callback) {

        var divId = 'rentals-' + rowData.id;
        var loadingHtml = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Fetching outstanding rentals...</div>';

        // Execute AJAX to fetch details
        $.ajax({
            url: "ajax/php/report-blacklisted.php",
            type: "POST",
            data: {
                action: "get_customer_rentals",
                customer_id: rowData.id
            },
            dataType: "JSON",
            success: function (response) {
                var items = response.data;

                if (items.length === 0) {
                    callback('<div class="p-3 text-muted text-center">No outstanding rentals found for this customer.</div>');
                    return;
                }

                // Build Nested Table
                var html = '<div class="p-3 bg-light">';
                html += '<h6 class="mb-2">Outstanding Rent Invoices:</h6>';
                html += '<table class="table table-sm table-dark table-bordered mb-0" style="width: 100%;">';
                html += '<thead><tr><th>Bill No</th><th>Rental Date</th><th>Outstanding Items</th><th>Deposit</th><th>Status</th></tr></thead>';
                html += '<tbody>';

                items.forEach(function (item) {
                    html += '<tr>';
                    html += '<td>' + item.bill_number + '</td>';
                    html += '<td>' + item.rental_date + '</td>';
                    html += '<td>' + item.outstanding_items + ' / ' + item.total_items + '</td>';
                    html += '<td>' + item.deposit_total + '</td>';
                    html += '<td><span class="badge bg-warning">' + item.status + '</span></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';

                callback(html);
            },
            error: function () {
                callback('<div class="p-3 text-danger">Error fetching details.</div>');
            }
        });

        // Return placeholder or whatever (but we use callback so this return is ignored mostly if async, 
        // but DataTables expects synchronous return for immediate display unless we wait.
        // Actually DataTables child row expects a string or element.
        // To support Async, we usually return a placeholder container and then populate it.
        // But here I used a callback pattern which I implemented in the click handler logic above?
        // Wait, my click handler logic:
        /*
            format(row.data(), function(childContent) {
                 row.child( childContent ).show();  <-- This callback displays it
            });
        */
        // Yes, that works.
    }
});
