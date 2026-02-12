$(document).ready(function () {
    var reportTable;

    function loadReport() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var paymentMethod = $('#payment_method').val();

        if (!fromDate || !toDate) {
            swal("Error", "Please select a valid date range", "error");
            return;
        }

        $('#summarySection').show();

        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        $('#reportTableBody').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: "ajax/php/equipment-rent-sales-report.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_sales_report",
                from_date: fromDate,
                to_date: toDate,
                payment_method: paymentMethod
            },
            success: function (result) {
                if (result.status === "success") {

                    // Update Summary Cards
                    $('#statTotalDeposit').text(result.summary.total_deposit);
                    $('#statTotalTransport').text(result.summary.total_transport);
                    $('#statTotalAdditional').text(result.summary.total_additional);
                    $('#statTotalRefund').text(result.summary.total_refund);
                    $('#statTotalRevenue').text(result.summary.total_revenue);

                    var rows = "";

                    if (result.data.length > 0) {
                        result.data.forEach(function (item) {
                            rows += `
                                <tr>
                                    <td>${item.bill_number}</td>
                                    <td>${item.date}</td>
                                    <td>${item.customer_name}</td>
                                    <td><small>${item.items}</small></td>
                                    <td class="text-end text-primary">${item.deposit}</td>
                                    <td class="text-end">${item.transport}</td>
                                    <td class="text-end text-danger">${item.additional}</td>
                                    <td class="text-end text-success">${item.refund}</td>
                                    <td class="text-end fw-bold">${item.revenue}</td>
                                </tr>
                            `;
                        });
                    }

                    $('#reportTableBody').html(rows);

                    // Update Footer Totals
                    $('#tblTotalDeposit').text(result.summary.total_deposit);
                    $('#tblTotalTransport').text(result.summary.total_transport);
                    $('#tblTotalAdditional').text(result.summary.total_additional);
                    $('#tblTotalRefund').text(result.summary.total_refund);
                    $('#tblTotalRevenue').text(result.summary.total_revenue);

                    reportTable = $('#reportTable').DataTable({
                        order: [[1, 'desc']],
                        pageLength: 25
                    });

                } else {
                    swal("Error", "Failed to load report", "error");
                }
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
                $('#reportTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error loading data</td></tr>');
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
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var paymentMethod = $('#payment_method').val();
        if (fromDate && toDate) {
            window.open("print-equipment-report.php?from=" + fromDate + "&to=" + toDate + "&payment_method=" + paymentMethod, "_blank");
        } else {
            swal("Error", "Please select a date range first", "error");
        }
    });

    $('#printDailyBtn').click(function () {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = today.getFullYear();
        var currentDate = yyyy + '-' + mm + '-' + dd;
        var paymentMethod = $('#payment_method').val();

        window.open("print-equipment-report.php?from=" + currentDate + "&to=" + currentDate + "&payment_method=" + paymentMethod, "_blank");
    });

    $('#printReturnIncomeBtn').click(function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var paymentMethod = $('#payment_method').val();
        if (fromDate && toDate) {
            window.open("print-return-income-report.php?from=" + fromDate + "&to=" + toDate + "&payment_method=" + paymentMethod, "_blank");
        } else {
            swal("Error", "Please select a date range first", "error");
        }
    });

    $('#printDailyReturnBtn').click(function () {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = today.getFullYear();
        var currentDate = yyyy + '-' + mm + '-' + dd;
        var paymentMethod = $('#payment_method').val();

        window.open("print-return-income-report.php?from=" + currentDate + "&to=" + currentDate + "&payment_method=" + paymentMethod, "_blank");
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
    $('#toDate').val(formatDate(lastDay));

    // Trigger initial load
    loadReport();
});
