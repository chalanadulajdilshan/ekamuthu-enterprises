$(document).ready(function () {

    // Hide preloader
    $("#page-preloader").fadeOut();

    // Initialize Customer Search DataTable
    var customerTable = $('#oldOutstandingCustomerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ajax/php/customer-master.php',
            type: 'POST',
            data: function (d) {
                d.filter = true;
                // d.old_outstanding_only = true; // Use this if you only want to see customers with outstanding
            }
        },
        columns: [
            { data: 'key' },
            { data: 'code' },
            { data: 'name' },
            { data: 'mobile_number' },
            { data: 'nic' },
            { data: 'address' },
            { data: 'old_outstanding' }
        ],
        pageLength: 10,
        order: [[2, "asc"]]
    });

    // Open Modal
    $("#btnSelectCustomer").click(function () {
        $('#oldOutstandingCustomerModal').modal('show');
    });

    // Handle Customer Selection from Modal
    $('#oldOutstandingCustomerTable tbody').on('click', 'tr', function () {
        var data = customerTable.row(this).data();
        if (data) {
            $("#customer_select").val(data.id);
            $("#customer_name_display").val(data.name + " (" + data.code + ")");
            $('#oldOutstandingCustomerModal').modal('hide');
            $("#customer_select").trigger('change');
        }
    });

    // Initialize Invoice Select2 (Payment Form)
    $('#pay_invoice_id').select2({
        placeholder: "Select Invoice",
        allowClear: true
    });

    // Initialize DataTable
    var table = $('#detailsTable').DataTable({
        pageLength: 10,
        order: [[1, "desc"]], // Sort by Date desc
        columns: [
            { data: 'invoice_no' },
            { data: 'date' },
            {
                data: 'status',
                render: function (data, type, row) {
                    return data === 'Not Paid'
                        ? '<span class="badge bg-danger">Not Paid</span>'
                        : '<span class="badge bg-success">Paid</span>';
                }
            },
            {
                data: 'amount',
                className: 'text-end',
                render: function (data, type, row) {
                    return parseFloat(data).toFixed(2);
                }
            }
        ]
    });

    // Initialize Payment History Table
    var historyTable = $('#paymentHistoryTable').DataTable({
        pageLength: 5,
        order: [[0, "desc"]], // Sort by Date desc
        columns: [
            { data: 'collect-date' },
            { data: 'invoice_no' },
            {
                data: 'amount',
                className: 'text-end',
                render: function (data) { return parseFloat(data).toFixed(2); }
            },
            { data: 'remark' }
        ]
    });

    // Handle Customer Change
    $('#customer_select').on('change', function () {
        var customerId = $(this).val();

        if (customerId) {
            $("#summary_section").fadeIn();
            $("#pay_customer_id").val(customerId);

            // Load all sections
            loadSummary(customerId);
            loadDetails(customerId);
            loadPendingInvoices(customerId);
            loadPaymentHistory(customerId);
        } else {
            $("#summary_section").fadeOut();
        }
    });

    function loadSummary(id) {
        $.ajax({
            url: "ajax/php/customer-master.php",
            type: "POST",
            data: { action: "get_old_outstanding_summary", customer_id: id },
            dataType: "JSON",
            success: function (data) {
                if (data.status !== "error") {
                    $("#stat_total").text(parseFloat(data.total).toFixed(2));
                    $("#stat_paid").text(parseFloat(data.paid).toFixed(2));
                    $("#stat_payable").text(parseFloat(data.payable).toFixed(2));
                }
            }
        });
    }

    function loadDetails(id) {
        $.ajax({
            url: "ajax/php/customer-master.php",
            type: "POST",
            data: { action: "get_old_outstanding_details", customer_id: id },
            dataType: "JSON",
            success: function (data) {
                table.clear().rows.add(data).draw();
            }
        });
    }

    function loadPendingInvoices(id) {
        $.ajax({
            url: "ajax/php/customer-master.php",
            type: "POST",
            data: { action: "get_pending_invoices", customer_id: id },
            dataType: "JSON",
            success: function (data) {
                var options = '<option value="">Select Invoice...</option>';
                $.each(data, function (i, item) {
                    options += `<option value="${item.id}" data-amount="${item.amount}">${item.invoice_no} (Balance: ${item.amount})</option>`;
                });
                $("#pay_invoice_id").html(options).trigger('change');
            }
        });
    }

    function loadPaymentHistory(id) {
        $.ajax({
            url: "ajax/php/customer-master.php",
            type: "POST",
            data: { action: "get_old_outstanding_payments", customer_id: id },
            dataType: "JSON",
            success: function (data) {
                historyTable.clear().rows.add(data).draw();
            }
        });
    }

    // Handle Payment Submission
    $("#btnPay").click(function () {
        var formData = new FormData($("#paymentForm")[0]);
        formData.append("action", "save_old_outstanding_payment");

        if (!$("#pay_invoice_id").val() || !$("#pay_date").val() || !$("#pay_amount").val()) {
            swal("Error", "Please fill required fields", "error");
            return;
        }

        var selectedOption = $("#pay_invoice_id").find(":selected");
        var maxAmount = parseFloat(selectedOption.data("amount")) || 0;
        var payAmount = parseFloat($("#pay_amount").val()) || 0;

        if (payAmount > maxAmount) {
            swal("Error", "Payment amount cannot be greater than the invoice outstanding amount (" + maxAmount.toFixed(2) + ")", "error");
            return;
        }

        $("#btnPay").prop("disabled", true).text("Processing...");

        $.ajax({
            url: "ajax/php/customer-master.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "JSON",
            success: function (response) {
                $("#btnPay").prop("disabled", false).html('<i class="uil uil-money-bill me-1"></i> Pay Now');

                if (response.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Payment recorded successfully!",
                        type: "success",
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Reset Form
                    $("#paymentForm")[0].reset();
                    $("#pay_customer_id").val($("#customer_select").val()); // Restore ID
                    $("#pay_invoice_id").val(null).trigger('change');

                    // Reload All Data
                    var custId = $("#customer_select").val();
                    loadSummary(custId);
                    loadDetails(custId);
                    loadPendingInvoices(custId);
                    loadPaymentHistory(custId);
                } else {
                    swal("Error", response.message, "error");
                }
            },
            error: function () {
                $("#btnPay").prop("disabled", false).html('<i class="uil uil-money-bill me-1"></i> Pay Now');
                swal("Error", "System encountered an error", "error");
            }
        });
    });

});
