$(document).ready(function() {
    let dagProfitTable;

    // Initialize DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#dagProfitReportTable')) {
            dagProfitTable = $('#dagProfitReportTable').DataTable();
            return;
        }

        dagProfitTable = $('#dagProfitReportTable').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "ordering": true,
            "info": true,
            "paging": true,
            "searching": true,
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "language": {
                "emptyTable": "No data available in table",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "lengthMenu": "Show _MENU_ entries",
                "loadingRecords": "Loading...",
                "processing": "Processing...",
                "search": "Search:",
                "zeroRecords": "No matching records found"
            },
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger btn-sm',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-info btn-sm',
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });
    }

    // Update DataTable with new data
    function updateDataTable(data, totals) {
        // Clear existing data
        dagProfitTable.clear();

        if (data.length === 0) {
            dagProfitTable.row.add([
                '<td colspan="12" class="text-center text-muted">No data found for the selected criteria</td>',
                '', '', '', '', '', '', '', '', '', '', ''
            ]);
        } else {
            data.forEach(function(row) {
                const profitClass = parseFloat(row.profit.replace(/,/g, '')) >= 0 ? 'text-success' : 'text-danger';
                
                dagProfitTable.row.add([
                    row.serial_number,
                    row.dag_received_date,
                    row.customer_name,
                    row.previous_customer_name,
                    row.company_name,
                    row.company_issued_date,
                    row.company_delivery_date,
                    row.job_number,
                    row.size_name,
                    '<div class="text-end">' + row.casing_cost + '</div>',
                    '<div class="text-end">' + row.total_amount + '</div>',
                    '<div class="text-end ' + profitClass + '"><strong>' + row.profit + '</strong></div>'
                ]);
            });
        }

        // Update totals
        $('#total_casing_cost').text(totals.total_casing_cost);
        $('#total_amount').text(totals.total_amount);
        $('#total_profit').text(totals.total_profit);

        // Draw the table
        dagProfitTable.draw();
    }

    // Generate Report Button Click
    $('#view_dag_profit_report').click(function(e) {
        e.preventDefault();
        generateDagProfitReport();
    });

    // Export Report Button Click
    $('#export_dag_profit_report').click(function(e) {
        e.preventDefault();
        exportDagProfitReport();
    });

    // Generate DAG Profit Report
    function generateDagProfitReport() {
        const formData = {
            action: 'get_dag_profit_report',
            from_date: $('#from_date').val(),
            to_date: $('#to_date').val(),
            customer_id: $('#customer_id').val(),
            company_id: $('#company_id').val(),
            serial_number: $('#serial_number').val(),
            job_number: $('#job_number').val()
        };

        // Show loading
        showLoading();

        $.ajax({
            url: 'ajax/php/dag-profit-report.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if (response.status === 'success') {
                    populateReportTable(response.data, response.totals);
                    updateDateRangeDisplay(formData.from_date, formData.to_date, response.record_count);
                    
                    // Show appropriate message based on results
                    if (response.record_count === 0) {
                        if (response.debug_info) {
                            Swal.fire({
                                icon: 'info',
                                title: 'No Data Found',
                                text: response.debug_info + '. Please check if DAG items have been added to the system.',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                icon: 'info',
                                title: 'No Records Found',
                                text: 'No DAG profit records found for the selected criteria.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    } else {
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: `Report generated successfully with ${response.record_count} records.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Failed to generate report'
                    });
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to generate report. Please try again.'
                });
            }
        });
    }

    // Populate Report Table
    function populateReportTable(data, totals) {
        // Clear the table body first
        const tbody = $('#dagProfitReportTable tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="12" class="text-center text-muted">No data found for the selected criteria</td>
                </tr>
            `);
        } else {
            data.forEach(function(row) {
                const profitClass = parseFloat(row.profit.replace(/,/g, '')) >= 0 ? 'text-success' : 'text-danger';
                
                tbody.append(`
                    <tr>
                        <td>${row.serial_number}</td>
                        <td>${row.dag_received_date}</td>
                        <td>${row.customer_name}</td>
                        <td>${row.previous_customer_name}</td>
                        <td>${row.company_name}</td>
                        <td>${row.company_issued_date}</td>
                        <td>${row.company_delivery_date}</td>
                        <td>${row.job_number}</td>
                        <td>${row.size_name}</td>
                        <td class="text-end">${row.casing_cost}</td>
                        <td class="text-end">${row.total_amount}</td>
                        <td class="text-end ${profitClass}"><strong>${row.profit}</strong></td>
                    </tr>
                `);
            });
        }

        // Update totals
        $('#total_casing_cost').text(totals.total_casing_cost);
        $('#total_amount').text(totals.total_amount);
        $('#total_profit').text(totals.total_profit);

        // Update DataTable instead of reinitializing
        if (dagProfitTable) {
            updateDataTable(data, totals);
        }
    }

    // Update Date Range Display
    function updateDateRangeDisplay(fromDate, toDate, recordCount) {
        let dateRangeText = '';
        if (fromDate && toDate) {
            dateRangeText = `Report Period: ${fromDate} to ${toDate} | Total Records: ${recordCount}`;
        } else {
            dateRangeText = `All Time Report | Total Records: ${recordCount}`;
        }
        $('#dagProfitReportDateRange').html(`<div class="alert alert-info">${dateRangeText}</div>`);
    }

    // Export DAG Profit Report
    function exportDagProfitReport() {
        const formData = {
            action: 'export_dag_profit_report',
            from_date: $('#from_date').val(),
            to_date: $('#to_date').val(),
            customer_id: $('#customer_id').val(),
            company_id: $('#company_id').val(),
            serial_number: $('#serial_number').val(),
            job_number: $('#job_number').val()
        };

        // Create a temporary form for file download
        const form = $('<form>', {
            method: 'POST',
            action: 'ajax/php/dag-profit-report.php',
            target: '_blank'
        });

        // Add form data
        $.each(formData, function(key, value) {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: value
            }));
        });

        // Submit form
        form.appendTo('body').submit().remove();

        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Export Started!',
            text: 'Your report is being downloaded...',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Show Loading
    function showLoading() {
        $('#view_dag_profit_report').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
    }

    // Hide Loading
    function hideLoading() {
        $('#view_dag_profit_report').prop('disabled', false).html('<i class="uil uil-chart-line me-1"></i> Generate Report');
    }

    // Form validation
    function validateForm() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();

        if (fromDate && toDate && fromDate > toDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Date Range',
                text: 'From date cannot be greater than to date.'
            });
            return false;
        }

        return true;
    }

    // Clear filters
    function clearFilters() {
        $('#dag-profit-form')[0].reset();
        
        // Reset date fields to today
        const today = new Date().toISOString().split('T')[0];
        $('.date-picker').val(today);
        
        // Clear the table
        $('#dagProfitReportTable tbody').html(`
            <tr>
                <td colspan="12" class="text-center text-muted">Click "Generate Report" to view data</td>
            </tr>
        `);
        
        // Clear totals
        $('#total_casing_cost').text('0.00');
        $('#total_amount').text('0.00');
        $('#total_profit').text('0.00');
        
        // Clear date range display
        $('#dagProfitReportDateRange').empty();
    }

    // Add clear filters button functionality if exists
    $(document).on('click', '#clear_filters', function(e) {
        e.preventDefault();
        clearFilters();
    });

    // Form submit handler
    $('#dag-profit-form').submit(function(e) {
        e.preventDefault();
        if (validateForm()) {
            generateDagProfitReport();
        }
    });

    // Initialize on page load
    initDataTable();
});

// Utility function to format currency
function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Utility function to parse currency
function parseCurrency(amount) {
    return parseFloat(amount.toString().replace(/,/g, '')) || 0;
}
