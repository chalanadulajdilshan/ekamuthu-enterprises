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

                    return json.data || [];
                }
            },
            "columns": [
                { "data": "job_code" },
                { "data": "created_at" },
                { "data": "customer_name" },
                { "data": "machine_name" },
                { "data": "status" },
                { "data": "employee_name" },
                { "data": "repair_charge", "className": "text-end" },
                { "data": "commission_amount", "className": "text-end" },
                { "data": "item_cost", "className": "text-end" },
                { "data": "total_cost", "className": "text-end" }
            ],
            "order": [[1, "desc"]],
            "pageLength": 25,
            "createdRow": function (row, data, dataIndex) {
                $(row).attr('data-id', data.id);
                $(row).css('cursor', 'pointer');
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

        // Row click event using delegation
        $('#reportTable tbody').on('click', 'tr', function () {
            var repairJobId = $(this).attr('data-id');
            if (repairJobId && typeof repairJobPageId !== 'undefined') {
                window.location.href = `repair-job.php?id=${repairJobId}&page_id=${repairJobPageId}`;
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

    // Print Button Click - open dedicated print page
    $('#printBtn').click(function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var status = $('#statusFilter').val();

        var url = `print-repair-job-report.php?from_date=${fromDate}&to_date=${toDate}&status=${status}`;
        window.open(url, '_blank');
    });

    // Set default dates: 1st of current month to Today
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

    // Use format Date helper if available or standard YYYY-MM-DD
    function formatDate(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    $('#fromDate').val(formatDate(firstDay));
    $('#toDate').val(formatDate(today));

    // Load initial data
    loadReport();
});
