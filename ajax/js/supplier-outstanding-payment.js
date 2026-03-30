$(document).ready(function () {
    // Initialize Datepicker
    $(".date-picker").datepicker({
        dateFormat: "yy-mm-dd",
    });

    // Listen for supplier selection (common.js handles the DataTable and populates fields)
    // When modal closes after supplier selection, load outstanding items
    $('#supplierModal').on('hidden.bs.modal', function () {
        var supplierId = $('#supplier_id').val();
        if (supplierId) {
            loadOutstandingItems(supplierId);
        }
    });

    // Payment Method change -> Show/Hide fields
    $("#paymentMethod").change(function () {
        const method = $(this).val();

        $("#bankDetails").hide();
        $("#chequeDetails").hide();
        $("#transferDetails").hide();

        if (method == "2") {
            $("#bankDetails").show();
            $("#chequeDetails").show();
        } else if (method == "3") {
            $("#bankDetails").show();
            $("#transferDetails").show();
        }
    });

    // Load Branches when Bank changes
    $("#bank_id").change(function () {
        const bankId = $(this).val();
        const branchSelect = $("#branch_id");

        branchSelect.html('<option value="">Loading...</option>').prop("disabled", true);

        if (bankId) {
            $.ajax({
                url: "ajax/php/supplier-outstanding-payment.php",
                type: "POST",
                dataType: "json",
                data: { action: "get_branches", bank_id: bankId },
                success: function (response) {
                    let html = '<option value="">Select Branch</option>';
                    if (response.status === "success") {
                        response.branches.forEach(function (branch) {
                            html += '<option value="' + branch.id + '">' + branch.name + ' (' + branch.code + ')</option>';
                        });
                        branchSelect.html(html).prop("disabled", false);
                    } else {
                        branchSelect.html('<option value="">Error loading branches</option>');
                    }
                },
                error: function () {
                    branchSelect.html('<option value="">Error loading branches</option>');
                }
            });
        } else {
            branchSelect.html('<option value="">Select Bank First</option>').prop("disabled", true);
        }
    });

    // Refresh button
    $("#refreshBtn").click(function () {
        const supplierId = $("#supplier_id").val();
        if (supplierId) {
            loadOutstandingItems(supplierId);
        } else {
            Swal.fire("Please select a supplier first");
        }
    });

    // Search by Invoice Number
    $("#searchInvoiceBtn").click(function () {
        const invoiceNo = $("#search_invoice_number").val().trim();
        if (invoiceNo) {
            loadOutstandingItems(0, invoiceNo);
        } else {
            Swal.fire("Warning", "Please enter a GRN or Invoice number to search", "warning");
        }
    });

    $('#search_invoice_number').keypress(function (e) {
        if (e.which == 13) {
            $("#searchInvoiceBtn").click();
            return false;
        }
    });

    // Load Outstanding Items
    function loadOutstandingItems(supplierId, invoiceNumber) {
        invoiceNumber = invoiceNumber || '';
        $("#outstandingTableBody").html(
            '<tr><td colspan="6" class="text-center">Loading...</td></tr>'
        );
        $("#payBtn").prop("disabled", true);

        $.ajax({
            url: "ajax/php/supplier-outstanding-payment.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_outstanding_invoices",
                supplier_id: supplierId,
                invoice_number: invoiceNumber
            },
            success: function (response) {
                if (response.status === "success") {
                    if (response.supplier) {
                        $("#supplier_id").val(response.supplier.id);
                        $("#supplier_code").val(response.supplier.code);
                    }
                    renderTable(response.items);
                    $("#totalOutstandingDisplay").text(formatCurrency(response.total_outstanding));
                } else {
                    $("#outstandingTableBody").html(
                        '<tr><td colspan="6" class="text-center text-danger">' + response.message + '</td></tr>'
                    );
                }
            },
            error: function () {
                $("#outstandingTableBody").html(
                    '<tr><td colspan="6" class="text-center text-danger">Failed to load data</td></tr>'
                );
            }
        });
    }

    // Render Table
    function renderTable(items) {
        var html = "";
        if (items.length === 0) {
            html = '<tr><td colspan="6" class="text-center">No outstanding invoices found.</td></tr>';
        } else {
            items.forEach(function (item) {
                html += '<tr>' +
                    '<td>' +
                        '<div class="form-check">' +
                            '<input class="form-check-input item-checkbox" type="checkbox" data-id="' + item.invoice_id + '" data-amount="' + item.amount + '">' +
                        '</div>' +
                    '</td>' +
                    '<td>' + item.date + '</td>' +
                    '<td>' + item.grn_number + '</td>' +
                    '<td>' + item.invoice_no + '</td>' +
                    '<td class="text-end">' + formatCurrency(item.amount) + '</td>' +
                    '<td>' +
                        '<input type="number" class="form-control form-control-sm text-end payment-amount-input" ' +
                            'value="' + item.amount + '" max="' + item.amount + '" step="0.01" disabled>' +
                    '</td>' +
                '</tr>';
            });
        }
        $("#outstandingTableBody").html(html);
        updateSelectedTotal();
    }

    // Checkbox Change
    $(document).on("change", ".item-checkbox", function () {
        var row = $(this).closest("tr");
        var input = row.find(".payment-amount-input");

        if ($(this).is(":checked")) {
            input.prop("disabled", false);
        } else {
            input.prop("disabled", true);
            input.val(input.attr("max"));
        }
        updateSelectedTotal();
    });

    // Select All
    $("#selectAll").change(function () {
        var isChecked = $(this).is(":checked");
        $(".item-checkbox").prop("checked", isChecked).trigger("change");
    });

    // Amount Input Change
    $(document).on("input", ".payment-amount-input", function () {
        var max = parseFloat($(this).attr("max"));
        var val = parseFloat($(this).val());

        if (isNaN(val) || val < 0) val = 0;
        if (val > max) val = max;

        updateSelectedTotal();
    });

    // Update Total
    function updateSelectedTotal() {
        var total = 0;
        var count = 0;

        $(".item-checkbox:checked").each(function () {
            var row = $(this).closest("tr");
            var input = row.find(".payment-amount-input");
            var amount = parseFloat(input.val()) || 0;
            total += amount;
            count++;
        });

        $("#selectedTotal").text(formatCurrency(total));
        $("#payBtn").prop("disabled", count === 0 || total <= 0);
    }

    // Process Payment
    $("#payBtn").click(function () {
        var supplierId = $("#supplier_id").val();
        var paymentDate = $("#paymentDate").val();
        var paymentMethod = $("#paymentMethod").val();
        var bankId = $("#bank_id").val();
        var branchId = $("#branch_id").val();
        var chequeDate = $("#chequeDate").val();
        var chequeNo = $("#chequeNo").val();
        var transferDate = $("#transferDate").val();
        var accountNo = $("#accountNo").val();
        var refNo = $("#refNo").val();

        // Validation
        if (!supplierId) {
            Swal.fire("Error", "Please select a supplier", "error");
            return;
        }

        if (paymentMethod == "2") {
            if (!bankId || !branchId || !chequeNo || !chequeDate) {
                Swal.fire("Error", "Please fill in all Cheque details (Bank, Branch, Cheque No, Date)", "error");
                return;
            }
        } else if (paymentMethod == "3") {
            if (!bankId || !branchId || !accountNo || !transferDate) {
                Swal.fire("Error", "Please fill in all Transfer details (Bank, Branch, Account No, Date)", "error");
                return;
            }
        }

        var items = [];
        $(".item-checkbox:checked").each(function () {
            var row = $(this).closest("tr");
            var input = row.find(".payment-amount-input");
            var id = $(this).data("id");
            var amount = parseFloat(input.val()) || 0;

            if (amount > 0) {
                items.push({ id: id, amount: amount });
            }
        });

        if (items.length === 0) return;

        var methodText = $("#paymentMethod option:selected").text();

        Swal.fire({
            title: "Confirm Payment?",
            text: "You are about to pay " + $("#selectedTotal").text() + " via " + methodText,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Pay Now",
            confirmButtonColor: "#34c38f",
            cancelButtonColor: "#f46a6a"
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "ajax/php/supplier-outstanding-payment.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "save_supplier_payment",
                        supplier_id: supplierId,
                        payment_date: paymentDate,
                        payment_method_id: paymentMethod,
                        bank_id: bankId,
                        branch_id: branchId,
                        cheque_no: chequeNo,
                        ref_no: refNo,
                        cheque_date: chequeDate,
                        transfer_date: transferDate,
                        account_no: accountNo,
                        items: items
                    },
                    beforeSend: function () {
                        Swal.showLoading();
                    },
                    success: function (response) {
                        if (response.status === "success") {
                            Swal.fire({
                                icon: "success",
                                title: "Payment Successful",
                                text: response.message
                            }).then(function () {
                                $("#paymentForm")[0].reset();
                                $("#paymentMethod").val("1").trigger("change");
                                loadOutstandingItems(supplierId);
                                $("#selectedTotal").text("0.00");
                                $("#selectAll").prop("checked", false);
                            });
                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Failed to process payment", "error");
                    }
                });
            }
        });
    });

    function formatCurrency(amount) {
        return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,");
    }
});
