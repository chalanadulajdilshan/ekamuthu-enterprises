$(document).ready(function () {
    var auditTable;

    function loadAuditReport() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var userId = $('#user_id').val();

        if ($.fn.DataTable.isDataTable('#auditTable')) {
            $('#auditTable').DataTable().destroy();
        }

        $('#auditTableBody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: "ajax/php/audit-report.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_audit_report",
                from_date: fromDate,
                to_date: toDate,
                user_id: userId
            },
            success: function (result) {
                if (result.status === "success") {
                    var rows = "";
                    if (result.data.length > 0) {
                        result.data.forEach(function (item, index) {
                            var badgeClass = item.type === 'Rent' ? 'bg-primary' : 'bg-warning';
                            rows += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td><span class="badge ${badgeClass}">${item.type}</span></td>
                                    <td>${item.bill_no}</td>
                                    <td>${item.date}</td>
                                    <td>${item.customer_name || 'N/A'}</td>
                                    <td><strong>${item.creator_name || 'System'}</strong></td>
                                    <td>${item.created_at_time ? moment(item.created_at_time).format('YYYY-MM-DD hh:mm A') : 'N/A'}</td>
                                </tr>
                            `;
                        });
                    }

                    $('#auditTableBody').html(rows);

                    $('#auditTable').DataTable({
                        "order": [[6, "desc"]],
                        "pageLength": 25
                    });
                } else {
                    swal("Error", result.message || "Failed to load report", "error");
                }
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
            }
        });
    }

    // Initialize Date Pickers
    $('.date-picker').datepicker({
        dateFormat: 'yy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    // Set default date range to current month
    var date = new Date();
    var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);

    $('#fromDate').datepicker('setDate', firstDay);
    $('#toDate').datepicker('setDate', lastDay);

    // Initial Load
    loadAuditReport();

    // Event Handlers
    $('#searchBtn').click(function () {
        loadAuditReport();
    });

    $('#resetBtn').click(function () {
        $('#reportForm')[0].reset();
        $('#fromDate').datepicker('setDate', firstDay);
        $('#toDate').datepicker('setDate', lastDay);
        loadAuditReport();
    });

    $('#printBtn').click(function () {
        window.print();
    });
});
