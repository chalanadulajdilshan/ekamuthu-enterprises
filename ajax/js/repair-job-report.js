$(document).ready(function () {

    var reportTable;

    function loadReport() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var status = $('#statusFilter').val();

        if (!fromDate || !toDate) {
            swal("Error", "Please select a valid date range", "error");
            return;
        }

        // Show summary section
        $('#summarySection').show();

        // Destroy existing table if exists
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        // Show loading state...
        $('#reportTableBody').html('<tr><td colspan="9" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: "ajax/php/repair-job-report.php",
            type: "POST",
            data: {
                action: "get_repair_job_report",
                from_date: fromDate,
                to_date: toDate,
                status: status
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {

                    // Update Summary Cards
                    $('#statTotalJobs').text(result.summary.total_jobs);
                    $('#statTotalRevenue').text(result.summary.total_revenue);
                    $('#statTotalCommission').text(result.summary.total_commission);
                    $('#statRepairCharges').text(result.summary.total_repair_charges);

                    // Populate Table
                    var rows = "";
                    var tblTotalRepairCharge = 0;
                    var tblTotalCommission = 0;
                    var tblTotalItemCost = 0;
                    var tblTotalCost = 0;

                    if (result.data.length > 0) {
                        result.data.forEach(function (item) {
                            rows += `
                                <tr style="cursor: pointer;" onclick="window.location.href='repair-job.php?job_id=${item.id}'">
                                    <td>${item.job_code}</td>
                                    <td>${item.created_at}</td>
                                    <td>${item.customer_name}</td>
                                    <td>${item.machine_name}</td>
                                    <td>${item.status}</td>
                                    <td class="text-end">${item.repair_charge}</td>
                                    <td class="text-end">${item.commission_amount}</td>
                                    <td class="text-end">${item.item_cost}</td>
                                    <td class="text-end">${item.total_cost}</td>
                                </tr>
                            `;

                            tblTotalRepairCharge += parseFloat(item.val_repair_charge);
                            tblTotalCommission += parseFloat(item.val_commission);
                            tblTotalItemCost += parseFloat(item.val_item_cost);
                            tblTotalCost += parseFloat(item.val_total_cost);
                        });
                    }

                    $('#reportTableBody').html(rows);

                    // Update Footer Totals
                    $('#tblTotalRepairCharge').text(tblTotalRepairCharge.toFixed(2));
                    $('#tblTotalCommission').text(tblTotalCommission.toFixed(2));
                    $('#tblTotalItemCost').text(tblTotalItemCost.toFixed(2));
                    $('#tblTotalCost').text(tblTotalCost.toFixed(2));

                    // Re-initialize DataTable
                    reportTable = $('#reportTable').DataTable({
                        dom: 'Bfrtip',
                        buttons: [
                            'copy', 'csv', 'excel', 'pdf', 'print'
                        ],
                        order: [[1, 'desc']] // Order by Date
                    });

                    // Row click event
                    $('#reportTable tbody').off('click').on('click', 'tr', function () {
                        var data = reportTable.row(this).data();
                        // Data is an array in the order of columns, but we built it with strings. 
                        // Wait, we can't easily access hidden ID if we didn't add it as a column or data attribute.
                        // Actually, result.data is our source object array, but DataTable renders array of values if we didn't specify columns: [data:...] option in init.
                        // But here we built HTML manually in rows += ...
                        // So the DataTable is initialized on existing DOM.
                        // We do NOT have the ID in the DOM (tr/td).

                        // Let's modify the row building to include data-id attribute on TR
                    });

                } else {
                    swal("Error", result.message || "Failed to load report", "error");
                }
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
                $('#reportTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    // Search Button Click
    $('#searchBtn').click(function () {
        loadReport();
    });

    // Reset Button Click
    $('#resetBtn').click(function () {
        // Reset dates to default (Today/First Day of Month handled mainly in php/html initialization but we can reset to empty or reload page)
        // Simple reload or specific reset
        location.reload();
    });

    // Print Button Click
    $('#printBtn').click(function () {
        window.print();
    });

    // Load initial data (Optional - maybe wait for user to click search)
    // loadReport();
});
