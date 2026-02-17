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
                    // Update Counts with Clickable Links
                    function createLink(count, type) {
                        return '<a href="javascript:void(0);" class="text-decoration-underline fw-bold job-count-link" data-type="' + type + '">' + count + '</a>';
                    }

                    $("#total_machines").html(createLink(data.total_machines, 'total_machines'));
                    $("#total_outsource_machines").html(createLink(data.total_outsource_machines, 'total_outsource_machines'));
                    $("#total_in_house_machines").html(createLink(data.total_in_house_machines, 'total_in_house_machines'));
                    $("#cannot_repair").html(createLink(data.cannot_repair, 'cannot_repair'));
                    $("#pending").html(createLink(data.pending, 'pending'));
                    $("#checking").html(createLink(data.checking, 'checking'));
                    $("#in_progress").html(createLink(data.in_progress, 'in_progress'));
                    $("#repaired_not_taken").html(createLink(data.repaired_not_taken, 'repaired_not_taken'));
                    $("#repaired_taken").html(createLink(data.repaired_taken, 'repaired_taken'));

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

    // Handle click on job count links
    $(document).on('click', '.job-count-link', function () {
        var type = $(this).data('type');
        var fromDate = $("#from_date").val();
        var toDate = $("#to_date").val();
        var title = $(this).closest('tr').find('td:first').text().trim().split('\n')[0]; // Get descriptive title

        $("#jobDetailsModalLabel").text('Details: ' + title);
        $("#jobDetailsModal").modal('show');
        loadJobDetails(type, fromDate, toDate);
    });

    function loadJobDetails(type, fromDate, toDate) {
        $("#modal-loader").show();
        $("#modal-table-container").hide();
        $("#jobDetailsTable tbody").empty();

        $.ajax({
            url: "ajax/php/repair-report.php",
            type: "POST",
            data: {
                action: "get_job_details",
                type: type,
                from: fromDate,
                to: toDate
            },
            dataType: "JSON",
            success: function (result) {
                $("#modal-loader").hide();
                $("#modal-table-container").show();

                if (result.status === "success") {
                    var rows = "";
                    if (result.data.length === 0) {
                        rows = "<tr><td colspan='8' class='text-center'>No records found</td></tr>";
                    } else {
                        var statusColors = {
                            'pending': 'secondary',
                            'checking': 'info',
                            'in_progress': 'warning',
                            'completed': 'success',
                            'delivered': 'primary',
                            'cannot_repair': 'danger'
                        };

                        $.each(result.data, function (index, job) {
                            var statusColor = statusColors[job.job_status] || 'secondary';
                            var dateShort = job.created_at.split(' ')[0]; // YYYY-MM-DD

                            rows += "<tr>" +
                                "<td>" + (index + 1) + "</td>" +
                                "<td class='fw-bold'>" + job.job_code + "</td>" +
                                "<td>" + dateShort + "</td>" +
                                "<td><span class='fw-bold'>" + (job.customer_name || '-') + "</span><br><small class='text-muted'>" + (job.customer_phone || '') + "</small></td>" +
                                "<td><small class='text-muted'>" + job.item_type + "</small><br>" + job.machine_name + "</td>" +
                                "<td>" + job.technical_issue + "</td>" +
                                "<td><span class='badge bg-" + statusColor + "'>" + job.job_status + "</span></td>" +
                                "<td class='text-end fw-bold'>" + job.total_cost + "</td>" +
                                "</tr>";
                        });
                    }
                    $("#jobDetailsTable tbody").html(rows);
                } else {
                    $("#jobDetailsTable tbody").html("<tr><td colspan='8' class='text-center text-danger'>Error loading data</td></tr>");
                }
            },
            error: function () {
                $("#modal-loader").hide();
                $("#jobDetailsTable tbody").html("<tr><td colspan='8' class='text-center text-danger'>Server error</td></tr>");
            }
        });
    }

    // Auto-load current month on page load
    $("#btn-filter").click();
});

function printModal() {
    var divToPrint = document.getElementById("jobDetailsTable");
    var title = $("#jobDetailsModalLabel").text();
    var newWin = window.open("", "Print-Window");

    newWin.document.open();
    newWin.document.write('<html><head><title>' + title + '</title>');
    newWin.document.write('<link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />');
    newWin.document.write('<style>body{padding: 20px;} table{width:100%; border-collapse: collapse;} th, td{border: 1px solid #ddd; padding: 8px; text-align: left;} .text-end{text-align: right;} .badge{border: 1px solid #000; color: #000; background: none !important; font-weight: bold;}</style>');
    newWin.document.write('</head><body onload="window.print()">');
    newWin.document.write('<h3 class="text-center mb-4">' + title + '</h3>');
    newWin.document.write(divToPrint.outerHTML);
    newWin.document.write('</body></html>');
    newWin.document.close();

    setTimeout(function () {
        newWin.close();
    }, 10);
}
