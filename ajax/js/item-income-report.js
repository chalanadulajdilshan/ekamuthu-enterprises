$(document).ready(function () {
    function formatNumber(num) {
        return parseFloat(num || 0).toFixed(2);
    }

    function renderSummary(summary) {
        $('#sumRental').text(summary.rental);
        $('#sumExtraDay').text(summary.extra_day);
        $('#sumPenalty').text(summary.penalty);
        $('#sumAdditional').text(summary.additional);
        $('#sumDamage').text(summary.damage);
        $('#sumRefund').text(summary.refund);
        $('#sumNet').text(summary.net);

        // Footer totals
        $('#sumRentalFoot').text(summary.rental);
        $('#sumExtraDayFoot').text(summary.extra_day);
        $('#sumPenaltyFoot').text(summary.penalty);
        $('#sumAdditionalFoot').text(summary.additional);
        $('#sumDamageFoot').text(summary.damage);
        $('#sumRefundFoot').text(summary.refund);
        $('#sumNetFoot').text(summary.net);
    }

    function loadReport() {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();

        if (!fromDate || !toDate) {
            swal('Error', 'Please select a valid date range', 'error');
            return;
        }

        $('#summarySection').show();
        $('#reportTableBody').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: 'ajax/php/item-income-report.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'get_item_income_report',
                from_date: fromDate,
                to_date: toDate
            },
            success: function (res) {
                if (res.status !== 'success') {
                    swal('Error', res.message || 'Failed to load data', 'error');
                    return;
                }

                let rows = '';
                if (res.data.length > 0) {
                    res.data.forEach(function (item) {
                        rows += `
                            <tr>
                                <td>${item.item_label}</td>
                                <td class="text-end">${item.total_return_qty}</td>
                                <td class="text-end">${formatNumber(item.rental_amount)}</td>
                                <td class="text-end">${formatNumber(item.extra_day_amount)}</td>
                                <td class="text-end">${formatNumber(item.penalty_amount)}</td>
                                <td class="text-end text-success">${formatNumber(item.additional_payment)}</td>
                                <td class="text-end text-danger">${formatNumber(item.damage_amount)}</td>
                                <td class="text-end text-danger">${formatNumber(item.refund_amount)}</td>
                                <td class="text-end fw-bold">${formatNumber(item.net_income)}</td>
                            </tr>`;
                    });
                } else {
                    rows = '<tr><td colspan="10" class="text-center">No records found</td></tr>';
                }

                $('#reportTableBody').html(rows);
                renderSummary(res.summary);

                if ($.fn.DataTable.isDataTable('#reportTable')) {
                    $('#reportTable').DataTable().destroy();
                }

                $('#reportTable').DataTable({
                    order: [[0, 'asc']],
                    pageLength: 50
                });
            },
            error: function () {
                swal('Error', 'Server error occurred', 'error');
                $('#reportTableBody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    $('#searchBtn').click(loadReport);

    $('#resetBtn').click(function () {
        location.reload();
    });

    $('#printBtn').click(function () {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        if (fromDate && toDate) {
            window.open('print-return-income-report.php?from=' + fromDate + '&to=' + toDate, '_blank');
        } else {
            swal('Error', 'Please select a date range first', 'error');
        }
    });

    // Default to current month
    const date = new Date();
    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);

    function formatDate(d) {
        const month = ('0' + (d.getMonth() + 1)).slice(-2);
        const day = ('0' + d.getDate()).slice(-2);
        return [d.getFullYear(), month, day].join('-');
    }

    $('#fromDate').val(formatDate(firstDay));
    $('#toDate').val(formatDate(lastDay));

    loadReport();
});
