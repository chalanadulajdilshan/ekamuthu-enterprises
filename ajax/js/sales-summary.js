$(document).ready(function () {
    // Initialize customer DataTable if it doesn't exist
    if (!$.fn.DataTable.isDataTable('#customerTable')) {
        var customerTable = $('#customerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/php/customer-master.php',
                type: 'POST',
                data: function (d) {
                    d.action = 'fetch_customers';
                    d.status = 'active'; // Only show active customers
                },
                dataSrc: function (json) {
                    console.log('Customer data response:', json);
                    if (json) {
                        if (json.error) {
                            console.error('Server error:', json.message);
                            return [];
                        }
                        return json.data || [];
                    }
                    console.error('Invalid response format:', json);
                    return [];
                },
                error: function (xhr, error, thrown) {
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        response: xhr.responseText,
                        error: error,
                        thrown: thrown
                    });
                    // Show user-friendly message
                    alert('Error loading customer data. Please check console for details.');
                    // Return empty data to clear processing message
                    return [];
                }
            },
            // Update the columns configuration to handle is_vat properly
columns: [
    { data: 'id' },
    { data: 'code' },
    { data: 'name' },
    { data: 'mobile_number' },
    { data: 'email' },
    { data: 'category_name' },
    { data: 'credit_limit' },  // Credit Limit
    { data: 'outstanding' },   // Outstanding amount
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
            order: [[2, 'asc']], // Sort by name by default
            pageLength: 10,
            responsive: true,
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // Handle customer selection
        $('#customerTable tbody').on('click', 'tr', function () {
            const data = customerTable.row(this).data();
            if (data) {
                $('#customer_id').val(data.id);
                $('#customer_code').val(data.code);
                $('#customer_name').val(data.name);
                $('#customerModal').modal('hide');
                
                // Trigger change event to refresh any dependent fields
                $('#customer_id').trigger('change');
                
                // Reload sales data if table exists
                if (typeof salesTable !== 'undefined' && $.isFunction(salesTable.ajax.reload)) {
                    salesTable.ajax.reload();
                }
            }
        });
    }

    // Disable customer inputs if "Check All Customers" is checked
    $('#checkAllCustomers').on('change', function () {
        const checked = $(this).is(':checked');
        if (checked) {
            $('#customer_code, #customer_name, #customer_address').val('').prop('readonly', true);
            $('#customer_id').val('');
        } else {
            $('#customer_code, #customer_name, #customer_address').prop('readonly', false);
        }
        
        // Trigger change to reload data
        if (typeof salesTable !== 'undefined' && $.isFunction(salesTable.ajax.reload)) {
            salesTable.ajax.reload();
        }
    });

    // Disable date inputs if date range selected
    $('#date_range').on('change', function () {
        const val = $(this).val();
        if (val) {
            $('#from_date, #to_date').val('').prop('disabled', true);
        } else {
            $('#from_date, #to_date').prop('disabled', false);
        }
    });

    $('#from_date, #to_date').on('input change', function () {
        if ($('#from_date').val() || $('#to_date').val()) {
            $('#date_range').val('').prop('disabled', true);
        } else {
            $('#date_range').prop('disabled', false);
        }
    });

    function getReportData() {
        const allCustomers = $('#checkAllCustomers').is(':checked');
        const customer_code = $('#customer_code').val();
        const from_date = $('#from_date').val();
        const to_date = $('#to_date').val();
        const date_range = $('#date_range').val();
        const status = $('#selectStatus').val();

        if (!allCustomers && !customer_code) {
            alert('Please select a customer or check "All Customers"');
            return null;
        }

        if (!status) {
            alert('Please select a status');
            return null;
        }

        if (!date_range && (!from_date || !to_date)) {
            alert('Please select either a date range or from-to dates');
            return null;
        }

        if (date_range && (from_date || to_date)) {
            alert('Please select either a date range or from-to dates, not both');
            return null;
        }

        return {
            all_customers: allCustomers ? 1 : 0,
            customer_code,
            from_date,
            to_date,
            date_range,
            status
        };
    }

    // View Report (HTML preview)
    function loadReport() {
        const data = getReportData();
        if (!data) return;

        $.ajax({
            url: 'ajax/php/sales-summary.php',
            method: 'POST',
            data: data,
            beforeSend: function () {
                $('#reportResult').html('<p>Loading report...</p>');
            },
            success: function (res) {
                $('#reportResult').html(res);
            },
            error: function () {
                $('#reportResult').html('<p class="text-danger">Failed to load report.</p>');
            }
        });
    }

    // Generate PDF Report
    function generatePDF() {
        const data = getReportData();
        if (!data) return;

        $.ajax({
            url: 'ajax/php/generate_pdf.php',
            method: 'POST',
            data: data,
            beforeSend: function () {
                $('#reportResult').html('<p>Generating PDF...</p>');
            },
            success: function (response) {
                try {
                    const res = JSON.parse(response);
                    if (res.pdf_url) {
                        window.open(res.pdf_url, '_blank');
                    } else {
                        $('#reportResult').html('<p class="text-danger">PDF generation failed.</p>');
                    }
                } catch (e) {
                    $('#reportResult').html('<p class="text-danger">Invalid response from server.</p>');
                }
            },
            error: function () {
                $('#reportResult').html('<p class="text-danger">PDF request failed.</p>');
            }
        });
    }

    // Events
    $('#selectStatus').on('change', loadReport);
    $('#print_btn').on('click', generatePDF);

});
