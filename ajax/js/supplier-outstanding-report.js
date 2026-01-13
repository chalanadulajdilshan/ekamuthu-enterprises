/**
 * Supplier Outstanding Report JS
 * Handles supplier selection and report data loading
 */

$(document).ready(function () {
    // Toggle between supplier/date filter
    $('input[name="filterType"]').on('change', function () {
        if ($(this).val() === 'supplier') {
            $('#supplierFilter').show();
            $('#dateFilter').hide();
        } else {
            $('#supplierFilter').hide();
            $('#dateFilter').show();
        }
    });

    // Default date range = current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#fromDate').val(formatDate(firstDay));
    $('#toDate').val(formatDate(today));

    function formatDate(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }

    // Search
    $('#searchBtn').on('click', function () {
        loadReportData();
    });

    // Reset
    $('#resetBtn').on('click', function () {
        $('#reportForm')[0].reset();
        $('#customer_id').val('');
        $('#code').val('');
        $('#reportTableBody').empty();
        $('[id^=total]').text('0.00');
        // Reset to current month
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        $('#fromDate').val(formatDate(firstDay));
        $('#toDate').val(formatDate(today));
    });

    // Load report data
    function loadReportData() {
        const supplierId = $('#customer_id').val();
        
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();

        const requestData = {
            action: 'get_outstanding_report',
            supplier_id: supplierId || '',  // Changed to match PHP expected parameter
            from_date: fromDate || '',
            to_date: toDate || ''
        };
        
        console.log('Sending request with data:', requestData);

        console.log('Request data:', requestData);

        $.ajax({
            url: 'ajax/php/supplier-outstanding-report.php',
            type: 'POST',
            dataType: 'json',
            data: requestData,
            beforeSend: function () {
                $('#reportTableBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');
            },
            success: function (response) {
                console.log('Server response:', response);
                if (response && response.status === 'success') {
                    renderReportData(response.data);
                } else {
                    const errorMsg = response && response.message ? response.message : 'Error loading data';
                    alert(errorMsg);
                    $('#reportTableBody').html('<tr><td colspan="6" class="text-center">No data found</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error loading data. Please check console for details.');
                $('#reportTableBody').html('<tr><td colspan="6" class="text-center">Error loading data</td></tr>');
            },
            complete: function () {
                console.log('Request completed');
            }
        });
    }

    // Render report rows
    function renderReportData(data) {
        const tbody = $('#reportTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center">No records found</td></tr>');
            $('[id^=total]').text('0.00');
            return;
        }

        let totalInvoice = 0;
        let totalPaid = 0;
        let totalOutstanding = 0;

        data.forEach(function (item) {
            const row = `
                <tr>
                    <td>${item.arn_no || ''}</td>
                    <td>${item.invoice_date || ''}</td>
                    <td>${item.supplier_name || ''}</td>
                    <td class="text-end">${parseFloat(item.invoice_amount || 0).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(item.paid_amount || 0).toFixed(2)}</td>
                    <td class="text-end text-danger" style="background-color: #ffebee;">${parseFloat(item.outstanding || 0).toFixed(2)}</td>
                </tr>`;

            tbody.append(row);

            totalInvoice += parseFloat(item.invoice_amount || 0);
            totalPaid += parseFloat(item.paid_amount || 0);
            totalOutstanding += parseFloat(item.outstanding || 0);
        });

        // Update totals
        $('#totalInvoice').text(totalInvoice.toFixed(2));
        $('#totalPaid').text(totalPaid.toFixed(2));
        $('#totalOutstanding')
            .text(totalOutstanding.toFixed(2))
            .css({
                'background-color': '#eb4034',
                'color': '#ffffff',
                'font-weight': 'bold'
            });
    }
});

$(document).ready(function () {
    var supplierTable;

    // Remove any generic handlers added from common.js for this modal
    $('#AllSupplierModal').off('shown.bs.modal');

    $('#AllSupplierModal').on('shown.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#allSupplierTable')) {
            $('#allSupplierTable').DataTable().destroy();
        }

        supplierTable = $('#allSupplierTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/customer-master.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                    d.category = [2, 3]; // Supplier (2) or Both (3)
                },
                dataSrc: function (json) {
                    return json.data || [];
                },
                error: function (xhr) {
                    console.error("Server Error Response:", xhr.responseText);
                }
            },
            columns: [
                { data: "id", title: "#ID" },
                { data: "code", title: "Code" },
                { data: "display_name", title: "Name" },
                { data: "mobile_number", title: "Mobile Number" },
                { data: "email", title: "Email" },
                { data: "category", title: "Category" },
                { data: "province", title: "Province" },
                { data: "credit_limit", title: "Credit Discount" },
                { data: "vat_no", title: "Is Vat" },
                { data: "status_label", title: "Status" }
            ],
            order: [[0, 'desc']],
            pageLength: 100
        });

        // Handle row selection in supplier modal
        $('#allSupplierTable tbody')
            .off('click')
            .on('click', 'tr', function () {
                var data = supplierTable.row(this).data();
                if (!data) return;

                // Fill the supplier fields in your form
                $('#customer_id').val(data.id || '');
                $('#code').val(data.code || '');

                // Close the modal
                $('#AllSupplierModal').modal('hide');
            });
    });
});
