$(document).ready(function () {
    // Initial load
    getReport();

    $('#searchBtn').click(function () {
        getReport();
    });

    $('#resetBtn').click(function () {
        $('#reportForm')[0].reset();
        getReport();
    });

    $('#printBtn').click(function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var vehicleId = $('#vehicleId').val();
        
        var url = 'print-vehicle-repair-report.php?from_date=' + fromDate + '&to_date=' + toDate + '&vehicle_id=' + vehicleId + '&page_id=' + (typeof page_id !== 'undefined' ? page_id : '');
        window.open(url, '_blank');
    });
});

function getReport() {
    var fromDate = $('#fromDate').val();
    var toDate = $('#toDate').val();
    var vehicleId = $('#vehicleId').val();

    $.ajax({
        url: 'ajax/php/vehicle-repair-report.php?page_id=' + (typeof page_id !== 'undefined' ? page_id : ''),
        type: 'POST',
        data: {
            action: 'GET_REPORT',
            fromDate: fromDate,
            toDate: toDate,
            vehicleId: vehicleId,
            page_id: (typeof page_id !== 'undefined' ? page_id : '')
        },
        dataType: 'json',
        beforeSend: function () {
            // Show loading state if needed
            $('#reportTableBody').html('<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></td></tr>');
        },
        success: function (data) {
            if (data.status === 'success') {
                var html = '';
                var res = data.data;
                
                if (res.length > 0) {
                    $.each(res, function (i, row) {
                        html += '<tr>';
                        html += '<td>' + row.vehicle_no + '</td>';
                        html += '<td>' + row.ref_no + '</td>';
                        html += '<td>' + row.brand + '</td>';
                        html += '<td>' + row.model + '</td>';
                        html += '<td class="text-center">' + row.breakdown_count + '</td>';
                        html += '<td class="text-end">' + row.total_repair_expense_formatted + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">No data found</td></tr>';
                }

                $('#reportTableBody').html(html);
                
                // Update Summary Stats
                $('#statVehicleCount').text(data.summary.vehicle_count);
                $('#statTotalRepairExpenses').text(data.summary.total_repairs);
                $('#statTotalBreakdownCount').text(data.summary.total_breakdowns);
                
                // Update Footer
                $('#tblTotalBreakdownCount').text(data.summary.total_breakdowns);
                $('#tblTotalRepairExpenses').text(data.summary.total_repairs);

                // Reload DataTable if initialized
                if ($.fn.DataTable.isDataTable('#reportTable')) {
                    $('#reportTable').DataTable().destroy();
                    $('#reportTableBody').empty(); // Ensure it's empty
                }
                
                $('#reportTableBody').html(html);

                $('#reportTable').DataTable({
                    "paging": true,
                    "searching": true,
                    "info": true,
                    "order": [[0, "asc"]]
                });

            } else {
                Swal.fire('Error', 'Failed to fetch report data', 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'An internal error occurred', 'error');
        }
    });
}
