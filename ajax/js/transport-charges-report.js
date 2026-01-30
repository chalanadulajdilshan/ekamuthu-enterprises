$(document).ready(function () {
    var reportTable;

    function loadReport() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();

        if (!fromDate || !toDate) {
            swal("Error", "Please select a valid date range", "error");
            return;
        }

        $('#summarySection').show();

        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        $('#reportTableBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: "ajax/php/transport-charges-report.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_transport_charges_report",
                from_date: fromDate,
                to_date: toDate
            },
            success: function (result) {
                if (result.status === "success") {

                    $('#statTotalTransport').text(result.summary.total_transport_cost);

                    var rows = "";
                    var totalTransport = 0;

                    if (result.data.length > 0) {
                        result.data.forEach(function (item) {
                            rows += `
                                <tr>
                                    <td>${item.bill_number}</td>
                                    <td>${item.created_at}</td>
                                    <td>${item.customer_name}</td>
                                    <td>${item.code}</td>
                                    <td>${item.status}</td>
                                    <td class="text-end">${item.transport_cost}</td>
                                </tr>
                            `;
                            totalTransport += parseFloat(item.val_transport_cost);
                        });
                    }

                    $('#reportTableBody').html(rows);
                    $('#tblTotalTransport').text(totalTransport.toFixed(2));

                    reportTable = $('#reportTable').DataTable({
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                        order: [[1, 'desc']]
                    });

                } else {
                    swal("Error", "Failed to load report", "error");
                }
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
                $('#reportTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    $('#searchBtn').click(function () {
        loadReport();
    });

    $('#resetBtn').click(function () {
        location.reload();
    });

    $('#printBtn').click(function () {
        window.print();
    });

    // Set default date range to current month
    var date = new Date();
    var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);

    // Format Date to YYYY-MM-DD
    function formatDate(d) {
        var month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    $('#fromDate').val(formatDate(firstDay));
    $('#toDate').val(formatDate(lastDay)); // Or use today's date based on preference, but usually "this month" implies full range or until today. Use today to be safe? User said "this month".

    // Trigger initial load
    loadReport();
});
