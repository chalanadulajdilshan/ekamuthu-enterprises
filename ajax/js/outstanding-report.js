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


});

function loadReport() {
    var customerId = $('#customer_id').val();

    if ($.fn.DataTable.isDataTable('#reportTable')) {
        $('#reportTable').DataTable().destroy();
    }

    $('#reportTable').DataTable({
        "ajax": {
            "url": "ajax/php/outstanding-report.php",
            "type": "POST",
            "data": {
                action: 'get_outstanding_report',
                customer_id: customerId
            }
        },
        "columns": [
            { "data": "customer_name" },
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
            var totalRent = api.column(1).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            var totalPaid = api.column(2).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            var totalBalance = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(1).footer()).html(totalRent.toFixed(2));
            $(api.column(2).footer()).html(totalPaid.toFixed(2));
            $(api.column(3).footer()).html(totalBalance.toFixed(2));
        }
    });
}
