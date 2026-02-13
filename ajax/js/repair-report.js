$(document).ready(function () {

    // Generate Report
    $("#btn-filter").click(function () {
        var fromDate = $("#from_date").val();
        var toDate = $("#to_date").val();

        if (!fromDate || !toDate) {
            swal("Error!", "Please select date range", "error");
            return;
        }

        $.ajax({
            url: "ajax/php/repair-report.php",
            type: "POST",
            data: {
                action: "get_report",
                from: fromDate,
                to: toDate
            },
            dataType: "JSON",
            beforeSend: function () {
                $("#btn-filter").html('<i class="fa fa-spinner fa-spin"></i> Generating...');
                $("#btn-filter").prop('disabled', true);
            },
            success: function (result) {
                $("#btn-filter").html('<i class="uil uil-filter me-1"></i> Generate Report');
                $("#btn-filter").prop('disabled', false);

                if (result.status === "success") {
                    var data = result.data;

                    // Update Financials
                    $("#outsource_cost").text(data.outsource_cost);
                    $("#commission").text(data.commission);
                    $("#total_income").text(data.total_income);
                    $("#total_profit").text(data.total_profit);

                    // Update Counts
                    $("#total_machines").text(data.total_machines);
                    $("#total_outsource_machines").text(data.total_outsource_machines);
                    $("#total_in_house_machines").text(data.total_in_house_machines);
                    $("#cannot_repair").text(data.cannot_repair);
                    $("#pending").text(data.pending);
                    $("#checking").text(data.checking);
                    $("#in_progress").text(data.in_progress);
                    $("#repaired_not_taken").text(data.repaired_not_taken);
                    $("#repaired_taken").text(data.repaired_taken);

                    // Update Print Dates
                    $("#print_from_date").text(fromDate);
                    $("#print_to_date").text(toDate);

                } else {
                    swal("Error!", "Failed to generate report", "error");
                }
            },
            error: function () {
                $("#btn-filter").html('<i class="uil uil-filter me-1"></i> Generate Report');
                $("#btn-filter").prop('disabled', false);
                swal("Error!", "Server error occurred", "error");
            }
        });
    });

    // Auto-load current month on page load
    $("#btn-filter").click();
});
