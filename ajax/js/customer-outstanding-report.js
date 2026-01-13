/**
 * Customer Outstanding Report JS
 * Handles customer selection and report data loading
 */

$(document).ready(function () {
    // Initialize DataTable for customer selection
    if ($.fn.DataTable.isDataTable('#customerTable')) {
        $('#customerTable').DataTable().destroy();
    }

    const customerTable = $('#customerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ajax/php/customer-master.php',
            type: 'POST',
            data: function (d) {
                d.filter = true;
                d.category = '1'; // Filter for category = 1 only
                d.status = 'active'; // Keep the active status filter
            },
            // No need to modify customer table styling here
            dataSrc: function (json) {
                console.log('Server response:', json); // Log the server response
                if (json && json.data) {
                    return json.data;
                }
                return [];
            },
            error: function (xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);
            }
        },
        // Columns configuration matching the modal table (6 columns)
        columns: [
            { data: 'id' },
            { data: 'code' },
            { data: 'name' },
            { data: 'mobile_number' },
            { data: 'email' },
            { data: 'outstanding' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true,
        createdRow: function(row, data, index) {
            // Ensure styling is not accidentally applied to email; target credit column (index 5)
            $('td:eq(5)', row).removeClass('text-danger');
        },
        // Enable server-side processing parameters
        serverParams: function (data) {
            // Map DataTables parameters to server-side parameters
            data.start = data.start || 0;
            data.length = data.length || 10;
            if (data.search && data.search.value) {
                data.search = data.search.value;
            }
        },
        // Handle server response
        error: function (xhr, error, thrown) {
            console.error('DataTables error:', error);
            console.error('Server response:', xhr.responseText);
            // Display a user-friendly error message
            alert('An error occurred while loading the data. Please check the console for details.');
        }
    });

    // Handle customer selection from the modal
    $('#customerTable tbody').on('click', 'tr', function () {
        const data = customerTable.row(this).data();
        if (data) {
            $('#customer_id').val(data.id);
            $('#customer_code').val(data.code);
            $('#customer_name').val(data.name);
            $('#customerModal').modal('hide');
            // Automatically load report after customer selection
            loadReportData();
        }
    });

    // Toggle between customer and date filters
    $('input[name="filterType"]').on('change', function () {
        if ($(this).val() === 'customer') {
            $('#customerFilter').show();
            $('#dateFilter').hide();
        } else {
            $('#customerFilter').hide();
            $('#dateFilter').show();
        }
    });

    // Set default dates
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#fromDate').val(formatDate(firstDay));
    $('#toDate').val(formatDate(today));

    // Format date to YYYY-MM-DD
    function formatDate(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    // Search button click handler
    $('#searchBtn').on('click', function () {
        loadReportData();
    });

    // Reset button click handler
    $('#resetBtn').on('click', function () {
        $('#reportForm')[0].reset();
        $('#customer_id').val('');
        $('#customer_code').val('');
        $('#reportTableBody').empty();
        $('[id^=total]').text('0.00');
    });

    // Load report data via AJAX
    function loadReportData() {
        const customerId = $('#customer_id').val();
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();

        console.log('Customer ID:', customerId);
        console.log('From Date:', fromDate);
        console.log('To Date:', toDate);

        // If no filters are provided, show all records
        if (!customerId && (!fromDate || !toDate)) {
            console.log('No filters applied, showing all records');
        }

        const requestData = {
            action: 'get_outstanding_report',
            customer_id: customerId || '',
            from_date: fromDate || '',
            to_date: toDate || ''
        };

        console.log('Sending request with data:', requestData);

        $.ajax({
            url: 'ajax/php/customer-outstanding-report.php',
            type: 'POST',
            dataType: 'json',
            data: requestData,
            beforeSend: function () {
                console.log('Sending request...');
                $('#reportTableBody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');
            },
            success: function (response) {
                console.log('Server response:', response);
                if (response && response.status === 'success') {
                    renderReportData(response.data);
                } else {
                    const errorMsg = response && response.message ? response.message : 'Error loading data';
                    console.error('Error response:', errorMsg);
                    alert(errorMsg);
                    $('#reportTableBody').html('<tr><td colspan="8" class="text-center">No data found</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error loading data. Please check console for details.');
                $('#reportTableBody').html('<tr><td colspan="8" class="text-center">Error loading data</td></tr>');
            },
            complete: function () {
                console.log('Request completed');
            }
        });
    }

    // Render report data in table
    function renderReportData(data) {
        const tbody = $('#reportTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center">No records found</td></tr>');
            $('[id^=total]').text('0.00');
            return;
        }

        let totalInvoice = 0;
        let totalPaid = 0;
        let totalOutstanding = 0;
        let totalOldOutstanding = 0;

        data.forEach(function (item) {
            // Calculate row highlighting based on days until due
            const daysUntilDue = parseInt(item.days_until_due || 0);
            let rowClass = '';
            let dueDateClass = 'due-date-cell';
            let dueDateText = item.due_date || 'N/A';
            
            if (daysUntilDue < 0) {
                // Overdue
                rowClass = 'overdue-row';
                dueDateClass += ' overdue-text';
                dueDateText += ` (${Math.abs(daysUntilDue)} days overdue)`;
            } else if (daysUntilDue <= 2) {
                // Due within 2 days (including today)
                rowClass = 'due-soon-row';
                dueDateClass += ' due-soon-text';
                if (daysUntilDue === 0) {
                    dueDateText += ' (Due Today)';
                } else {
                    dueDateText += ` (Due in ${daysUntilDue} day${daysUntilDue > 1 ? 's' : ''})`;
                }
            }

            const row = `
                <tr class="${rowClass}">
                    <td>${item.invoice_no || ''}</td>
                    <td>${item.customer_name || ''}${item.mobile_number ? ' - ' + item.mobile_number : ''}</td>
                    <td>${item.invoice_date || ''}</td>
                    <td class="${dueDateClass}">${dueDateText}</td>
                    <td class="text-end">${parseFloat(item.invoice_amount || 0).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(item.paid_amount || 0).toFixed(2)}</td>
                    <td class="text-end text-danger" style="background-color: #ffebee;">${parseFloat(item.outstanding || 0).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(item.old_outstanding || 0).toFixed(2)}</td>
                </tr>`;

            tbody.append(row);

            totalInvoice += parseFloat(item.invoice_amount || 0);
            totalPaid += parseFloat(item.paid_amount || 0);
            totalOutstanding += parseFloat(item.outstanding || 0);
            totalOldOutstanding += parseFloat(item.old_outstanding || 0);
        });

        // Update totals
        $('#totalInvoice').text(totalInvoice.toFixed(2));
        $('#totalPaid').text(totalPaid.toFixed(2));
        $('#totalOutstanding')
            .text(totalOutstanding.toFixed(2))
            .attr('style', 'background-color: #eb4034 !important; color: #ffffff !important;');
        $('#totalOldOutstanding').text(totalOldOutstanding.toFixed(2));
    }
});
