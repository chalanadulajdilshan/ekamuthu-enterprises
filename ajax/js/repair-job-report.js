$(document).ready(function () {

    var reportTable;

    function loadReport() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var status = $('#statusFilter').val();

        if ((!fromDate || !toDate) && status !== 'pending' && status !== 'all') {
            swal("Error", "Please select a valid date range", "error");
            return;
        }

        // Show summary section
        $('#summarySection').show();

        // If table is already initialized, just reload its data
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().ajax.reload();
            return;
        }

        // Initialize DataTable with AJAX source
        reportTable = $('#reportTable').DataTable({
            "processing": true,
            "serverSide": false, // Client-side processing as before
            "ajax": {
                "url": "ajax/php/repair-job-report.php",
                "type": "POST",
                "data": function (d) {
                    d.action = "get_repair_job_report";
                    d.from_date = $('#fromDate').val();
                    d.to_date = $('#toDate').val();
                    d.status = $('#statusFilter').val();
                    d.employee_id = $('#employeeFilter').val();
                    d.search_query = $('#searchQuery').val();
                },
                "dataSrc": function (json) {
                    if (json.status !== "success") {
                        swal("Error", json.message || "Failed to load report", "error");
                        return [];
                    }

                    // Update Top Summary Cards
                    $('#statTotalJobs').text(json.summary.total_jobs);
                    $('#statTotalRevenue').text(json.summary.total_revenue);
                    $('#statTotalCommission').text(json.summary.total_commission);
                    $('#statRepairCharges').text(json.summary.total_repair_charges);

                    // Update Employee Summary Table
                    var empBody = '';
                    if (json.employee_summary && json.employee_summary.length > 0) {
                        $('#employeeSummaryRow').show();
                        json.employee_summary.forEach(function (emp) {
                            empBody += `<tr>
                                <td>${emp.name}</td>
                                <td class="text-center">${emp.pending || 0}</td>
                                <td class="text-center">${emp.in_progress || 0}</td>
                                <td class="text-center">${emp.completed || 0}</td>
                                <td class="text-center">${emp.delivered || 0}</td>
                                <td class="text-center">${emp.cannot_repair || 0}</td>
                                <td class="text-center fw-bold text-primary">${emp.total || 0}</td>
                            </tr>`;
                        });
                    } else {
                        $('#employeeSummaryRow').hide();
                    }
                    $('#employeeSummaryBody').html(empBody);

                    return json.data || [];
                }
            },
            "columns": [
                { "data": "job_code" },
                { "data": "item_breakdown_date" },
                { "data": "item_completed_date" },
                { "data": "customer_name" },
                { "data": "machine_name" },
                { "data": "machine_code" },
                { "data": "status" },
                { "data": "employee_name" },
                { "data": "repair_charge", "className": "text-end" },
                { "data": "commission_amount", "className": "text-end" },
                { "data": "item_cost", "className": "text-end" },
                { "data": "total_cost", "className": "text-end" },
                {
                    "data": null,
                    "className": "text-center",
                    "render": function (data, type, row) {
                        return `<a href="print-repair-job.php?id=${row.id}" target="_blank" class="btn btn-soft-info btn-sm">
                                    <i class="mdi mdi-printer me-1"></i> View Bill
                                </a>`;
                    }
                }
            ],
            "order": [[1, "desc"]],
            "pageLength": 25,
            "createdRow": function (row, data, dataIndex) {
                $(row).attr('data-id', data.id);
            },
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api();

                var totalRepairCharge = 0;
                var totalCommission = 0;
                var totalItemCost = 0;
                var totalCost = 0;

                data.forEach(function (item) {
                    totalRepairCharge += parseFloat(item.val_repair_charge || 0);
                    totalCommission += parseFloat(item.val_commission || 0);
                    totalItemCost += parseFloat(item.val_item_cost || 0);
                    totalCost += parseFloat(item.val_total_cost || 0);
                });

                $('#tblTotalRepairCharge').text(totalRepairCharge.toFixed(2));
                $('#tblTotalCommission').text(totalCommission.toFixed(2));
                $('#tblTotalItemCost').text(totalItemCost.toFixed(2));
                $('#tblTotalCost').text(totalCost.toFixed(2));
            }
        });

        // Row click event removed as per request to use Action buttons instead.
        /*
        $('#reportTable tbody').on('click', 'tr', function () {
            var repairJobId = $(this).attr('data-id');
            if (repairJobId && typeof repairJobPageId !== 'undefined') {
                window.location.href = `repair-job.php?job_id=${repairJobId}&page_id=${repairJobPageId}`;
            }
        });
        */
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

    // Print Button Click - open dedicated print page
    $('#printBtn').click(function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var status = $('#statusFilter').val();
        var employeeId = $('#employeeFilter').val();
        var searchQuery = $('#searchQuery').val();

        var url = `print-repair-job-report.php?from_date=${fromDate}&to_date=${toDate}&status=${status}&employee_id=${employeeId}&search_query=${searchQuery}`;
        window.open(url, '_blank');
    });

    // Set default dates: Explicitly clear as per request (ove
    // rriding any global defaults)
    $('#fromDate').val("");
    $('#toDate').val("");

    // Load initial data - Removed as per request to have empty filters by default
    loadReport();
});
