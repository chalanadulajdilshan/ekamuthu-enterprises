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

        $('#reportTableBody').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');

        var employeeId = $('#employeeId').val();

        $.ajax({
            url: "ajax/php/transport-summary-report.php",
            type: "POST",
            dataType: "json",
            data: {
                action: 'get_transport_summary_report',
                fromDate: fromDate,
                toDate: toDate,
                employeeId: employeeId
            },
            success: function (result) {
                if (result.status === "success") {

                    $('#statTotalDeliver').text(result.summary.total_deliver);
                    $('#statTotalPickup').text(result.summary.total_pickup);
                    $('#statTotalAmount').text(result.summary.total_amount);
                    
                    $('#printFromDate').text(fromDate);
                    $('#printToDate').text(toDate);
                    $('#printGenerated').text(new Date().toLocaleString());
                    
                    $('#printTotalDeliver').text(result.summary.total_deliver);
                    $('#printTotalPickup').text(result.summary.total_pickup);
                    $('#printTotalAmount').text(result.summary.total_amount);

                    var rows = "";
                    if (result.data.length > 0) {
                        result.data.forEach(function (item) {
                            rows += `
                                <tr>
                                    <td>${item.transport_date}</td>
                                    <td>${item.bill_number}</td>
                                    <td>${item.vehicle}</td>
                                    <td>${item.employee}</td>
                                    <td>${item.start_location}</td>
                                    <td>${item.end_location}</td>
                                    <td class="text-end">${item.deliver_amount}</td>
                                    <td class="text-end">${item.pickup_amount}</td>
                                    <td class="text-end">${item.total_amount}</td>
                                </tr>
                            `;
                        });
                    }

                    $('#reportTableBody').html(rows);
                    
                    $('#tblTotalDeliver').text(result.summary.total_deliver);
                    $('#tblTotalPickup').text(result.summary.total_pickup);
                    $('#tblTotalAmount').text(result.summary.total_amount);

                    reportTable = $('#reportTable').DataTable({
                        order: [[0, 'asc'], [1, 'asc']]
                    });

                } else {
                    swal("Error", result.message || "Failed to load report", "error");
                }
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
                $('#reportTableBody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    $('#searchBtn').click(function () {
        loadReport();
    });

    $('#resetBtn').click(function () {
        $('#fromDate').val(moment().startOf('month').format('YYYY-MM-DD'));
        $('#toDate').val(moment().format('YYYY-MM-DD'));
        $('#employeeId').val('');
        $('#searchBtn').click();
    });

    $('#printBtn').click(function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var employeeId = $('#employeeId').val();
        window.open('print-transport-summary.php?from_date=' + fromDate + '&to_date=' + toDate + '&employee_id=' + employeeId, '_blank');
    });

    // Set default date range to current month
    var date = new Date();
    var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    var lastDay = new Date(); // To today

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
    $('#toDate').val(formatDate(lastDay));

    // Trigger initial load
    loadReport();
});
