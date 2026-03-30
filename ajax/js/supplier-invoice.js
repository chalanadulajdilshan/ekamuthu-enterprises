jQuery(document).ready(function () {

    // DataTable config for item master modal
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/php/item-master.php",
            type: "POST",
            data: function (d) {
                d.filter = true;
                d.status = 1;
                d.stock_only = 0;
            },
            dataSrc: function (json) {
                return json.data;
            },
            error: function (xhr) {
                console.error("Server Error Response:", xhr.responseText);
            }
        },
        columns: [
            { data: "key", title: "#ID" },
            { data: "code", title: "Code" },
            { data: "name", title: "Name" },
            { data: "brand", title: "Brand" },
            { data: "category", title: "Category" },
            { data: "list_price", title: "List Price" },
            { data: "invoice_price", title: "Sales Price" },
            { data: "qty", title: "Quantity" }
        ],
        order: [[0, 'desc']],
        pageLength: 100
    });

    // Supplier DataTable for supplier modal
    var supplierTable = $('#supplierTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/php/supplier-master.php",
            type: "POST",
            data: function (d) {
                d.filter = true;
            },
            dataSrc: function (json) {
                return json.data;
            }
        },
        columns: [
            { data: "key", title: "#ID" },
            { data: "code", title: "Code" },
            { data: "name", title: "Name" },
            { data: "mobile_number", title: "Mobile" },
            { data: "email", title: "Email" },
            { data: "credit_limit", title: "Credit Limit" },
            { data: "status_label", title: "Status" }
        ],
        order: [[0, 'desc']],
        pageLength: 100
    });

    // On item row click - load selected item
    $('#datatable tbody').on('click', 'tr', function () {
        var data = table.row(this).data();
        if (!data) return;

        $('#item_id').val(data.id);
        $('#itemCode').val(data.code);
        $('#itemName').val(data.name);
        $('#qty').val(1);
        $('#rate').val('');
        $('#itemDiscount').val(0);
        $('#itemAmount').val('');

        calculateItemAmount();

        setTimeout(() => $('#qty').focus(), 200);
        $('#main_item_master').modal('hide');
    });

    // On supplier row click - load selected supplier
    $('#supplierTable tbody').on('click', 'tr', function () {
        var data = supplierTable.row(this).data();
        if (!data) return;

        $('#supplier_id').val(data.id);
        $('#supplier_code').val(data.code);
        $('#supplier_name').val(data.name);

        $('#supplierModal').modal('hide');
    });

    // Bank change - load branches
    $('#bank_name').on('change', function () {
        var bankId = $(this).val();
        var branchSelect = $('#branch_name');

        branchSelect.html('<option value="">Loading...</option>').prop('disabled', true);

        if (bankId) {
            $.ajax({
                url: 'ajax/php/supplier-invoice.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'get_branches', bank_id: bankId },
                success: function (response) {
                    var html = '<option value="">Select Branch</option>';
                    if (response.status === 'success') {
                        response.branches.forEach(function (branch) {
                            html += '<option value="' + branch.id + '">' + branch.name + ' (' + branch.code + ')</option>';
                        });
                        branchSelect.html(html).prop('disabled', false);
                    } else {
                        branchSelect.html('<option value="">Error loading branches</option>');
                    }
                },
                error: function () {
                    branchSelect.html('<option value="">Error loading branches</option>');
                }
            });
        } else {
            branchSelect.html('<option value="">Select Bank First</option>').prop('disabled', true);
        }
    });

    // Payment type toggle
    $('#payment_type').on('change', function () {
        var type = $(this).val();

        // Hide all payment sections
        $('.payment-section, .cheque-section, .credit-section').hide();

        if (type === 'cash') {
            $('#cash_section').show();
            $('#cash_amount').val($('#grandTotal').val());
        } else if (type === 'cheque') {
            $('#cheque_section').show();
        } else if (type === 'credit') {
            $('#credit_section').show();
        }
    });

    // Cheque image preview
    $('#cheque_image').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#cheque_preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#cheque_preview').hide();
        }
    });

    // Reset form
    $("#new").click(function (e) {
        e.preventDefault();
        resetForm();
    });

    // Add item button
    $('#addItemBtn').click(function () {
        addItem();
    });

    // GRN search modal - row click to load
    $('#grn_table tbody').on('click', 'tr.select-grn', function () {
        var id = $(this).data('id');
        loadSupplierInvoice(id);
        $('#grn_number_modal').modal('hide');
    });

    // Save button
    $('#create').click(function (e) {
        e.preventDefault();
        saveSupplierInvoice('create');
    });

    // Update button
    $('#update').click(function (e) {
        e.preventDefault();
        saveSupplierInvoice('update');
    });

    // Print button
    $('#printBtn').click(function (e) {
        e.preventDefault();
        var id = $('#supplier_invoice_id').val();
        if (!id) {
            swal("Warning", "Please select a supplier invoice first!", "warning");
            return;
        }
        window.open('print-supplier-invoice.php?id=' + id, '_blank');
    });

    // Delete button
    $('.delete-supplier-invoice').click(function (e) {
        e.preventDefault();
        var id = $('#supplier_invoice_id').val();
        if (!id) return;

        swal({
            title: "Are you sure?",
            text: "This supplier invoice will be permanently deleted!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: 'ajax/php/supplier-invoice.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function (response) {
                    if (response.status === 'success') {
                        swal({
                            title: "Deleted!",
                            text: "Supplier invoice has been deleted.",
                            type: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        swal("Error!", "Failed to delete.", "error");
                    }
                }
            });
        });
    });

    // ======================== FUNCTIONS ========================

    function addItem() {
        var item_id = $('#item_id').val().trim();
        var code = $('#itemCode').val().trim();
        var name = $('#itemName').val().trim();
        var unit = $('#itemUnit').val().trim();
        var qty = parseFloat($('#qty').val()) || 0;
        var rate = parseFloat($('#rate').val()) || 0;
        var discount = parseFloat($('#itemDiscount').val()) || 0;
        var amount = parseFloat($('#itemAmount').val()) || 0;

        if (!code || qty <= 0 || rate <= 0) {
            swal({
                title: "Validation Error!",
                text: "Please enter valid item code, quantity, and rate.",
                type: 'error',
                timer: 2500,
                showConfirmButton: false
            });
            return;
        }

        // Check duplicate
        var duplicate = false;
        $('#invoiceItemsBody tr').each(function () {
            var existingCode = $(this).find('td:first').text().trim();
            if (existingCode === code) {
                duplicate = true;
                return false;
            }
        });

        if (duplicate) {
            swal({
                title: "Duplicate Item!",
                text: 'Item "' + code + '" is already added.',
                type: 'warning',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        $('#noItemRow').remove();

        var row = '<tr data-item-id="' + item_id + '">' +
            '<td>' + code + '</td>' +
            '<td>' + name + '</td>' +
            '<td>' + unit + '</td>' +
            '<td>' + qty + '</td>' +
            '<td>' + rate.toFixed(2) + '</td>' +
            '<td>' + discount.toFixed(2) + '</td>' +
            '<td>' + amount.toFixed(2) + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-danger remove-item-btn">Remove</button></td>' +
            '</tr>';

        $('#invoiceItemsBody').append(row);

        // Clear input fields
        $('#item_id, #itemCode, #itemName, #itemUnit, #qty, #rate, #itemDiscount, #itemAmount').val('');
        $('#itemDiscount').val(0);

        updateGrandTotal();
    }

    // Remove item row (delegated)
    $(document).on('click', '.remove-item-btn', function () {
        $(this).closest('tr').remove();
        updateGrandTotal();

        if ($('#invoiceItemsBody tr').length === 0) {
            $('#invoiceItemsBody').append(
                '<tr id="noItemRow"><td colspan="8" class="text-center text-muted">No items added</td></tr>'
            );
        }
    });

    function updateGrandTotal() {
        var total = 0;
        $('#invoiceItemsBody tr').each(function () {
            var amountText = $(this).find('td:eq(6)').text().trim();
            var amount = parseFloat(amountText) || 0;
            total += amount;
        });

        $('#grandTotal').val(total.toFixed(2));

        // Update cash amount if cash is selected
        if ($('#payment_type').val() === 'cash') {
            $('#cash_amount').val(total.toFixed(2));
        }
    }

    function collectItems() {
        var items = [];
        $('#invoiceItemsBody tr').each(function () {
            var $row = $(this);
            if ($row.attr('id') === 'noItemRow') return;

            items.push({
                item_id: $row.data('item-id') || 0,
                item_code: $row.find('td:eq(0)').text().trim(),
                item_name: $row.find('td:eq(1)').text().trim(),
                unit: $row.find('td:eq(2)').text().trim(),
                qty: parseFloat($row.find('td:eq(3)').text()) || 0,
                rate: parseFloat($row.find('td:eq(4)').text()) || 0,
                discount: parseFloat($row.find('td:eq(5)').text()) || 0,
                amount: parseFloat($row.find('td:eq(6)').text()) || 0
            });
        });
        return items;
    }

    function saveSupplierInvoice(mode) {
        var supplierId = $('#supplier_id').val();
        var grnNo = $('#grn_no').val();
        var items = collectItems();

        if (!supplierId) {
            swal({ title: "Error!", text: "Please select a supplier.", type: 'error', timer: 2500, showConfirmButton: false });
            return;
        }
        if (items.length === 0) {
            swal({ title: "Error!", text: "Please add at least one item.", type: 'error', timer: 2500, showConfirmButton: false });
            return;
        }

        var paymentType = $('#payment_type').val();
        if (!paymentType) {
            swal({ title: "Error!", text: "Please select a payment type.", type: 'error', timer: 2500, showConfirmButton: false });
            return;
        }

        // Validate cheque details
        if (paymentType === 'cheque') {
            if (!$('#cheque_no').val().trim()) {
                swal({ title: "Error!", text: "Please enter cheque number.", type: 'error', timer: 2500, showConfirmButton: false });
                return;
            }
            if (!$('#cheque_date').val().trim()) {
                swal({ title: "Error!", text: "Please enter cheque date.", type: 'error', timer: 2500, showConfirmButton: false });
                return;
            }
        }

        // Validate credit period
        if (paymentType === 'credit') {
            if (!$('#credit_period').val() || parseInt($('#credit_period').val()) <= 0) {
                swal({ title: "Error!", text: "Please enter a valid credit period.", type: 'error', timer: 2500, showConfirmButton: false });
                return;
            }
        }

        var formData = new FormData();
        formData.append('grn_no', grnNo);
        formData.append('order_no', $('#order_no').val());
        formData.append('supplier_id', supplierId);
        formData.append('invoice_no', $('#invoice_no').val());
        formData.append('invoice_date', $('#invoice_date').val());
        formData.append('delivery_date', $('#delivery_date').val());
        formData.append('payment_type', paymentType);
        formData.append('cheque_no', $('#cheque_no').val() || '');
        formData.append('cheque_date', $('#cheque_date').val() || '');
        formData.append('bank_name', $('#bank_name option:selected').text().trim() || '');
        formData.append('branch_name', $('#branch_name option:selected').text().trim() || '');
        formData.append('credit_period', $('#credit_period').val() || 0);
        formData.append('items', JSON.stringify(items));

        // Add cheque image if present
        var chequeFile = $('#cheque_image')[0].files[0];
        if (chequeFile) {
            formData.append('cheque_image', chequeFile);
        }

        if (mode === 'create') {
            formData.append('action', 'create_supplier_invoice');
        } else {
            formData.append('action', 'update_supplier_invoice');
            formData.append('id', $('#supplier_invoice_id').val());
        }

        $.ajax({
            url: 'ajax/php/supplier-invoice.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    swal({
                        title: "Success!",
                        text: mode === 'create' ? "Supplier invoice created successfully!" : "Supplier invoice updated successfully!",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    swal("Error!", response.message || "Something went wrong.", "error");
                }
            },
            error: function (xhr) {
                console.error("AJAX Error:", xhr.responseText);
                swal("Error!", "Server error occurred.", "error");
            }
        });
    }

    function loadSupplierInvoice(id) {
        $.ajax({
            url: 'ajax/php/supplier-invoice.php',
            type: 'POST',
            data: { action: 'get_supplier_invoice', id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    var data = response.data;

                    $('#supplier_invoice_id').val(data.id);
                    $('#grn_no').val(data.grn_number);
                    $('#supplier_id').val(data.supplier_id);
                    $('#supplier_code').val(data.supplier_code);
                    $('#supplier_name').val(data.supplier_name);
                    $('#order_no').val(data.order_no);
                    $('#invoice_no').val(data.invoice_no);
                    $('#invoice_date').val(data.invoice_date);
                    $('#delivery_date').val(data.delivery_date);
                    $('#payment_type').val(data.payment_type).trigger('change');

                    if (data.payment_type === 'cheque') {
                        $('#cheque_no').val(data.cheque_no);
                        $('#cheque_date').val(data.cheque_date);
                        // Match bank by name text
                        $('#bank_name option').each(function () {
                            if ($(this).text().trim() === data.bank_name) {
                                $(this).prop('selected', true);
                            }
                        });
                        // Trigger bank change to load branches, then select the branch
                        var bankId = $('#bank_name').val();
                        if (bankId) {
                            $.ajax({
                                url: 'ajax/php/supplier-invoice.php',
                                type: 'POST',
                                dataType: 'json',
                                data: { action: 'get_branches', bank_id: bankId },
                                success: function (response) {
                                    var html = '<option value="">Select Branch</option>';
                                    if (response.status === 'success') {
                                        response.branches.forEach(function (branch) {
                                            var optText = branch.name + ' (' + branch.code + ')';
                                            var selected = (optText === data.branch_name) ? ' selected' : '';
                                            html += '<option value="' + branch.id + '"' + selected + '>' + optText + '</option>';
                                        });
                                        $('#branch_name').html(html).prop('disabled', false);
                                    }
                                }
                            });
                        }
                        if (data.cheque_image) {
                            $('#cheque_preview').attr('src', 'uploads/cheques/' + data.cheque_image).show();
                        }
                    }

                    if (data.payment_type === 'credit') {
                        $('#credit_period').val(data.credit_period);
                    }

                    // Load items
                    $('#invoiceItemsBody').empty();
                    if (data.items.length > 0) {
                        data.items.forEach(function (item) {
                            var row = '<tr data-item-id="' + item.item_id + '">' +
                                '<td>' + item.item_code + '</td>' +
                                '<td>' + item.item_name + '</td>' +
                                '<td>' + (item.unit || '') + '</td>' +
                                '<td>' + parseFloat(item.quantity) + '</td>' +
                                '<td>' + parseFloat(item.rate).toFixed(2) + '</td>' +
                                '<td>' + parseFloat(item.discount_percentage).toFixed(2) + '</td>' +
                                '<td>' + parseFloat(item.amount).toFixed(2) + '</td>' +
                                '<td><button type="button" class="btn btn-sm btn-danger remove-item-btn">Remove</button></td>' +
                                '</tr>';
                            $('#invoiceItemsBody').append(row);
                        });
                    } else {
                        $('#invoiceItemsBody').append(
                            '<tr id="noItemRow"><td colspan="8" class="text-center text-muted">No items added</td></tr>'
                        );
                    }

                    updateGrandTotal();

                    // Toggle buttons
                    $('#create').hide();
                    $('#update').show();
                    $('.delete-supplier-invoice').show();
                    $('#printBtn').show();
                }
            }
        });
    }

    function resetForm() {
        $('#form-data')[0].reset();
        $('#supplier_invoice_id, #supplier_id, #item_id').val('');
        $('#supplier_code, #supplier_name').val('');
        $('#invoiceItemsBody').html(
            '<tr id="noItemRow"><td colspan="8" class="text-center text-muted">No items added</td></tr>'
        );
        $('#grandTotal').val('0.00');
        $('#cash_amount').val('');
        $('#cheque_preview').hide();
        $('.payment-section, .cheque-section, .credit-section').hide();

        $('#create').show();
        $('#update').hide();
        $('.delete-supplier-invoice').hide();
        $('#printBtn').hide();

        location.reload();
    }

});

// Global function for calculating item amount
function calculateItemAmount() {
    var qty = parseFloat($('#qty').val()) || 0;
    var rate = parseFloat($('#rate').val()) || 0;
    var discount = parseFloat($('#itemDiscount').val()) || 0;

    var subtotal = qty * rate;
    var discountAmt = subtotal * (discount / 100);
    var amount = subtotal - discountAmt;

    $('#itemAmount').val(amount.toFixed(2));
}
