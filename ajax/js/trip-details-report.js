$(document).ready(function () {
    const fromDateInput = $('#fromDate');
    const toDateInput = $('#toDate');
    const vehicleIdInput = $('#vehicleId');
    const customerIdInput = $('#customerId');
    const searchBtn = $('#searchBtn');
    const resetBtn = $('#resetBtn');
    const printBtn = $('#printBtn');
    const reportTableBody = $('#reportTableBody');

    // Stats
    const statTotalToll = $('#statTotalToll');
    const statTotalHelper = $('#statTotalHelper');
    const statTotalCost = $('#statTotalCost');
    const summarySection = $('#summarySection');

    // Table footers
    const tblTotalToll = $('#tblTotalToll');
    const tblTotalHelper = $('#tblTotalHelper');
    const tblTotalCost = $('#tblTotalCost');

    // Set default dates (first of month to today)
    const today = new Date().toISOString().split('T')[0];
    const firstDayOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];

    fromDateInput.val(firstDayOfMonth);
    toDateInput.val(today);

    function fetchReport() {
        const fromDate = fromDateInput.val();
        const toDate = toDateInput.val();
        const vehicleId = vehicleIdInput.val();
        const customerId = customerIdInput.val();

        // Update print meta
        $('#printFromDate').text(fromDate || '-');
        $('#printToDate').text(toDate || '-');
        $('#printGenerated').text(new Date().toLocaleString());

        $.ajax({
            url: 'ajax/php/trip-details-report.php',
            type: 'POST',
            data: {
                action: 'get_trip_details_report',
                fromDate: fromDate,
                toDate: toDate,
                vehicleId: vehicleId,
                customerId: customerId
            },
            beforeSend: function () {
                reportTableBody.html('<tr><td colspan="14" class="text-center">Loading report...</td></tr>');
            },
            success: function (response) {
                if (response.status === 'success') {
                    renderTable(response.data);
                    renderSummary(response.summary);
                    summarySection.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to fetch report'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while fetching the report'
                });
            }
        });
    }

    function renderTable(data) {
        if (!data || data.length === 0) {
            reportTableBody.html('<tr><td colspan="14" class="text-center">No trips found for the selected criteria</td></tr>');
            return;
        }

        let html = '';
        data.forEach(row => {
            html += `
                <tr>
                    <td>${row.transport_date}</td>
                    <td>${row.trip_number}</td>
                    <td>${row.trip_category}</td>
                    <td class="small">${row.customer}</td>
                    <td>${row.vehicle}</td>
                    <td>${row.employee}</td>
                    <td class="text-end">${row.start_meter}</td>
                    <td class="text-end">${row.end_meter}</td>
                    <td class="text-end fw-bold text-primary">${row.km}</td>
                    <td>${row.start_location} / ${row.end_location}</td>
                    <td class="text-end">${row.toll}</td>
                    <td class="text-end">${row.helper_payment}</td>
                    <td class="text-end">${row.pay_amount}</td>
                    <td class="text-end fw-bold">${row.total_cost}</td>
                </tr>
            `;
        });
        reportTableBody.html(html);
    }

    function renderSummary(summary) {
        statTotalToll.text(summary.total_toll);
        statTotalHelper.text(summary.total_helper);
        statTotalCost.text(summary.total_cost);

        tblTotalToll.text(summary.total_toll);
        tblTotalHelper.text(summary.total_helper);
        tblTotalCost.text(summary.total_cost);
    }

    searchBtn.click(fetchReport);

    resetBtn.click(function () {
        fromDateInput.val(firstDayOfMonth);
        toDateInput.val(today);
        vehicleIdInput.val('').trigger('change');
        customerIdInput.val('').trigger('change');
        fetchReport();
    });

    printBtn.click(function () {
        window.print();
    });

    // Initial load
    // fetchReport(); 
});
