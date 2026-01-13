/**
 * Old Outstanding Report JS
 * Handles customer selection and report data loading
 */

$(document).ready(function () {
    // Initialize DataTable for customer selection
    if ($.fn.DataTable.isDataTable('#oldOutstandingCustomerTable')) {
        $('#oldOutstandingCustomerTable').DataTable().destroy();
    }

    const customerTable = $('#oldOutstandingCustomerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ajax/php/customer-master.php',
            type: 'POST',
            data: function (d) {
                d.filter = true;
                d.category = '1'; // Filter for category = 1 only
                d.status = 'active'; // Keep the active status filter
                d.old_outstanding_only = true; // Add filter for old outstanding > 0
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
                $('#oldOutstandingCustomerTable tbody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data</td></tr>');
            }
        },
        // Update the columns configuration to handle is_vat properly
        columns: [
            { data: 'id' },
            { data: 'code' },
            { data: 'name' },
            { data: 'mobile_number' },
            { data: 'email' },
            { data: 'category' },
            { data: 'credit_limit' },  // Credit Discount
            { data: 'old_outstanding' },   // Old Outstanding amount for this page
            {
                data: 'is_vat',
                render: function(data) {
                    return (data === 1 || data === '1') ? 'Yes' : 'No';
                }
            },
            {
                data: 'status_label',
                orderable: false
            }
        ],
        order: [[0, "desc"]],
        pageLength: 100,
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
    $('#oldOutstandingCustomerTable tbody').on('click', 'tr', function () {
        const data = customerTable.row(this).data();
        if (data) {
            $('#customer_id').val(data.id);
            $('#customer_code').val(data.code);
            $('#customer_name').val(data.name);
            $('#oldOutstandingCustomerModal').modal('hide');
            // Automatically load report after customer selection
            loadReportData();
        }
    });

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

        console.log('Customer ID:', customerId);

        const requestData = {
            action: 'get_old_outstanding_report',
            customer_id: customerId || ''
        };

        console.log('Sending request with data:', requestData);

        $.ajax({
            url: 'ajax/php/old-outstanding-report.php',
            type: 'POST',
            dataType: 'json',
            data: requestData,
            beforeSend: function () {
                console.log('Sending request...');
                $('#reportTableBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
            },
            success: function (response) {
                console.log('Server response:', response);
                if (response && response.status === 'success') {
                    renderReportData(response.data);
                } else {
                    const errorMsg = response && response.message ? response.message : 'Error loading data';
                    console.error('Error response:', errorMsg);
                    alert(errorMsg);
                    $('#reportTableBody').html('<tr><td colspan="4" class="text-center">No data found</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error loading data. Please check console for details.');
                $('#reportTableBody').html('<tr><td colspan="4" class="text-center">Error loading data</td></tr>');
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
            tbody.html('<tr><td colspan="4" class="text-center">No records found</td></tr>');
            $('[id^=total]').text('0.00');
            return;
        }

        let totalOldOutstanding = 0;

        data.forEach(function (item) {
            const oldOutstandingValue = parseFloat(item.old_outstanding || 0);
            const formattedValue = formatNumber(oldOutstandingValue);

            const row = `
                <tr>
                    <td>${item.code || ''}</td>
                    <td>${item.name || ''}${item.name_2 ? ' ' + item.name_2 : ''}</td>
                    <td>${item.mobile_number || ''}</td>
                    <td class="text-end text-danger" style="background-color: #fff3cd;">${formattedValue}</td>
                </tr>`;

            tbody.append(row);

            totalOldOutstanding += oldOutstandingValue;
        });

        // Update totals with formatted number
        const formattedTotal = formatNumber(totalOldOutstanding);
        $('#totalOldOutstanding').text(formattedTotal)
            .attr('style', 'background-color: #ff9800 !important; color: #ffffff !important;');
    }

    // Number formatting function
    function formatNumber(num) {
        return num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
