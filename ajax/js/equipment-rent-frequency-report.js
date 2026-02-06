$(document).ready(function () {
    var table;

    function loadFrequency() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();

        if (!fromDate || !toDate) {
            swal("Error", "Please select a valid date range", "error");
            return;
        }

        if ($.fn.DataTable.isDataTable('#frequencyTable')) {
            $('#frequencyTable').DataTable().destroy();
        }

        $('#frequencyTableBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: 'ajax/php/equipment-rent-frequency-report.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_top_rent_customers',
                from_date: fromDate,
                to_date: toDate
            },
            success: function (res) {
                if (res.status !== 'success') {
                    swal("Error", res.message || "Failed to load data", "error");
                    return;
                }

                var rows = '';
                var totalRentals = 0;
                var totalAmount = 0;
                if (res.data.length > 0) {
                    res.data.forEach(function (row) {
                        totalRentals += parseInt(row.rent_count || 0, 10);
                        totalAmount += parseFloat(row.total_amount || 0);
                        rows += `
                            <tr>
                                <td>${row.rank}</td>
                                <td>${row.customer_code || ''}</td>
                                <td>${row.customer_name || ''}</td>
                                <td>${row.mobile_number || ''}</td>
                                <td class="text-center fw-bold">${row.rent_count}</td>
                                <td class="text-end">${Number(row.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td>${row.last_rental_date || ''}</td>
                            </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="6" class="text-center">No records found</td></tr>';
                }

                $('#frequencyTableBody').html(rows);

                $('#statTotalCustomers').text(res.data.length);
                $('#statTotalRentals').text(totalRentals);
                $('#statTotalAmount').text(totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#printDateRange').text('From ' + fromDate + ' to ' + toDate);

                table = $('#frequencyTable').DataTable({
                    order: [[4, 'desc']],
                    pageLength: 25
                });
            },
            error: function () {
                swal("Error", "Server error occurred", "error");
            }
        });
    }

    $('#searchBtn').on('click', function () {
        loadFrequency();
    });

    $('#resetBtn').on('click', function () {
        location.reload();
    });

    $('#printBtn').on('click', function () {
        window.print();
    });

    // Default date range current month
    var now = new Date();
    var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    function fmt(d) {
        var m = '' + (d.getMonth() + 1);
        var day = '' + d.getDate();
        var y = d.getFullYear();
        if (m.length < 2) m = '0' + m;
        if (day.length < 2) day = '0' + day;
        return [y, m, day].join('-');
    }

    $('#fromDate').val(fmt(firstDay));
    $('#toDate').val(fmt(lastDay));

    loadFrequency();
});
