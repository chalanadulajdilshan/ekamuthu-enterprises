$(document).ready(function () {
    var reportTable;

    // Override default customer modal binding (from common.js) to prevent missing-column warnings
    $('#customerModal').off('shown.bs.modal').on('shown.bs.modal', function () {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable('#customerTable')) {
            $('#customerTable').DataTable().destroy();
        }

        $('#customerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/php/customer-master.php',
                type: 'POST',
                data: function (d) {
                    d.filter = true;
                    d.category = 1;
                },
                dataSrc: function (json) {
                    return json.data;
                }
            },
            columns: [
                { data: 'key', title: '#ID' },
                { data: 'code', title: 'Code' },
                { data: 'name', title: 'Name' },
                { data: 'mobile_number', title: 'Mobile Number' },
                { data: 'email', title: 'Email', defaultContent: '' },
                { data: 'vat_no', title: 'VAT', defaultContent: '' },
                { data: 'outstanding', title: 'Outstanding', defaultContent: '0.00' }
            ],
            order: [[0, 'desc']],
            pageLength: 100
        });

        $('#customerTable tbody').off('click').on('click', 'tr', function () {
            var data = $('#customerTable').DataTable().row(this).data();
            if (data) {
                $('#customer_id').val(data.id);
                $('#customer_code').val(data.code);
                $('#customerModal').modal('hide');
            }
        });
    });

    function formatNumber(num) {
        return parseFloat(num || 0).toFixed(2);
    }

    function loadReport() {
        var asOfDate = $('#asOfDate').val();
        var customerId = $('#customer_id').val();

        if (!asOfDate) {
            swal("Error", "Please select As of Date", "error");
            return;
        }

        $('#summaryRow').show();

        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        $('#reportTableBody').html('<tr><td colspan="9" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: "ajax/php/equipment-rent-outstanding-report.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_outstanding_report",
                as_of_date: asOfDate,
                customer_id: customerId
            },
            success: function (result) {
                if (result.status === 'success') {
                    var rows = "";
                    var totalOutstanding = result.summary ? result.summary.total_outstanding : '0.00';

                    if (result.data && result.data.length > 0) {
                        result.data.forEach(function (item) {
                            rows += `
                                <tr>
                                    <td>${item.bill_number}</td>
                                    <td>${item.customer_name}</td>
                                    <td><small>${item.equipment}${item.sub_equipment ? ' / ' + item.sub_equipment : ''}</small></td>
                                    <td>${item.rental_date}</td>
                                    <td>${item.due_date}</td>
                                    <td class="text-end">${item.pending_qty}</td>
                                    <td class="text-end"><span class="badge rounded-pill bg-danger">${item.overdue_days}</span></td>
                                    <td class="text-end">${formatNumber(item.per_unit_daily)}</td>
                                    <td class="text-end fw-bold text-danger">${formatNumber(item.outstanding_amount)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        rows = '<tr><td colspan="9" class="text-center">No overdue rentals found</td></tr>';
                    }

                    $('#reportTableBody').html(rows);
                    $('#totalOutstanding, #tblTotalOutstanding').text(totalOutstanding);

                    reportTable = $('#reportTable').DataTable({
                        order: [[6, 'desc']],
                        pageLength: 25
                    });
                } else {
                    swal("Error", result.message || "Failed to load report", "error");
                    $('#reportTableBody').html('<tr><td colspan="9" class="text-center text-danger">No data</td></tr>');
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

    $(".date-picker").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });

    // Default As of Date = today
    var today = new Date();
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var dd = String(today.getDate()).padStart(2, '0');
    var yyyy = today.getFullYear();
    var todayStr = yyyy + '-' + mm + '-' + dd;
    $('#asOfDate').val(todayStr);

    // Initial load
    loadReport();
});
