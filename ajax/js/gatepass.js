$(document).ready(function () {
    var gatepassItems = [];

    // Get New Code
    function getNewCode() {
        $.ajax({
            url: "ajax/php/gatepass.php",
            type: "POST",
            data: { action: 'get_new_code' },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#gatepass_code').val(response.code);
                    $('#gatepass_code_display').val(response.code);
                }
            }
        });
    }

    // Load Invoice Details and Items
    function loadInvoiceDetails(invoiceId) {
        $.ajax({
            url: "ajax/php/gatepass.php",
            type: "POST",
            data: {
                action: "get_invoice_details",
                invoice_id: invoiceId
            },
            dataType: "json",
            success: function (result) {
                if (result.status === "success") {
                    var invoice = result.invoice;
                    $("#invoice_id").val(invoice.id);
                    $("#name").val(invoice.customer_name);
                    $("#address").val(invoice.customer_address);
                    $("#id_number").val(invoice.customer_nic);
                    $("#search_bill_no").val(invoice.bill_number);

                    // Store items for picking
                    var totalRemaining = 0;
                    gatepassItems = result.items.map(function (item) {
                        var rem = parseFloat(item.remaining_quantity) || 0;
                        totalRemaining += rem;
                        return {
                            equipment_id: item.equipment_id,
                            sub_equipment_id: item.sub_equipment_id,
                            equipment_name: item.equipment_name,
                            sub_equipment_code: item.sub_equipment_code,
                            rent_type: item.rent_type,
                            billed_quantity: parseFloat(item.quantity) || 0,
                            prev_issued: parseFloat(item.already_issued) || 0,
                            remaining: rem,
                            quantity: rem, // Default to including all remaining
                            remarks: ""
                        };
                    });

                    if (totalRemaining > 0) {
                        $('#save_gatepass').show();
                    } else {
                        $('#save_gatepass').hide();
                        swal("Info", "All items for this invoice have already been issued via Gate Pass.", "info");
                    }

                    renderItemsTable();
                } else {
                    swal("Error!", result.message, "error");
                }
            },
            error: function () {
                swal("Error!", "Failed to load invoice details", "error");
            }
        });
    }

    function renderItemsTable() {
        var tbody = $("#gatepassItemsTableBody");
        tbody.empty();

        if (gatepassItems.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No items found for this invoice.</td></tr>');
            return;
        }

        gatepassItems.forEach(function (item, index) {
            var itemName = item.equipment_name;
            if (item.sub_equipment_code) {
                itemName += " (" + item.sub_equipment_code + ")";
            }

            var row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${itemName}</td>
                    <td class="text-center">${item.billed_quantity}</td>
                    <td class="text-center">${item.prev_issued || '-'}</td>
                    <td class="text-center text-primary fw-bold">${item.remaining}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm item-qty" data-index="${index}" 
                               value="${item.quantity}" min="0" max="${item.remaining}" ${item.remaining == 0 ? 'disabled' : ''}>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-remark" data-index="${index}" 
                               value="${item.remarks}" placeholder="Remarks">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="${index}">
                            <i class="uil uil-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Initial Load
    getNewCode();
    var urlParams = new URLSearchParams(window.location.search);
    var currentRentId = urlParams.get('rent_id') || $('#invoice_id').val();
    if (currentRentId) {
        loadInvoiceDetails(currentRentId);
    }

    // Quantity change handler
    $(document).on('change keyup', '.item-qty', function () {
        var index = $(this).data('index');
        var val = parseFloat($(this).val()) || 0;
        gatepassItems[index].quantity = val;
    });

    // Remark change handler
    $(document).on('change keyup', '.item-remark', function () {
        var index = $(this).data('index');
        gatepassItems[index].remarks = $(this).val();
    });

    // Remove item handler
    $(document).on('click', '.remove-item', function () {
        var index = $(this).data('index');
        gatepassItems.splice(index, 1);
        renderItemsTable();
    });

    // Bill Number Search
    $('#search_bill_no').on('keypress', function (e) {
        if (e.which == 13) {
            var billNo = $(this).val();
            if (billNo) {
                $.ajax({
                    url: "ajax/php/equipment-rent-master.php",
                    type: "POST",
                    data: { filter: true, search: { value: billNo } },
                    dataType: 'json',
                    success: function (res) {
                        if (res.data && res.data.length > 0) {
                            var rentId = res.data[0].id;
                            loadInvoiceDetails(rentId);
                        } else {
                            swal('Error', 'Bill Number not found!', 'error');
                        }
                    }
                });
            }
        }
    });

    // Save Gatepass
    $('#save_gatepass').click(function (e) {
        e.preventDefault();

        // Validation
        var name = $('#name').val();
        var issued_by = $('#issued_by').val();

        if (!name || !issued_by) {
            swal({
                type: 'error',
                title: 'Oops...',
                text: 'Name and Issued By are required!',
            });
            return;
        }

        if (gatepassItems.length === 0) {
            swal({
                type: 'error',
                title: 'Error',
                text: 'Please include at least one item.',
            });
            return;
        }

        var formData = {
            action: 'save',
            gatepass_code: $('#gatepass_code').val(),
            invoice_id: $('#invoice_id').val(),
            name: name,
            address: $('#address').val(),
            id_number: $('#id_number').val(),
            issued_by: issued_by,
            gatepass_date: $('#gatepass_date').val(),
            items: JSON.stringify(gatepassItems)
        };

        $.ajax({
            url: "ajax/php/gatepass.php",
            type: "POST",
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }, function () {
                        window.location.href = "gatepass-print.php?id=" + response.id;
                    });
                } else {
                    swal({
                        type: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function () {
                swal({
                    type: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred. Please try again.'
                });
            }
        });
    });

    // Gatepass List Modal Logic
    var gatepassSearchTimer;
    $('#gatepassSearchInput').on('input', function () {
        var term = $(this).val();
        clearTimeout(gatepassSearchTimer);
        gatepassSearchTimer = setTimeout(function () {
            loadGatepassList(term);
        }, 300);
    });

    $('#view_past_gatepasses').click(function () {
        loadGatepassList('');
    });

    function loadGatepassList(searchTerm) {
        var $tbody = $('#gatepassListTableBody');
        $tbody.html('<tr><td colspan="6" class="text-center text-muted py-3">Loading...</td></tr>');

        $.ajax({
            url: "ajax/php/gatepass.php",
            type: "POST",
            data: {
                action: 'filter',
                search: searchTerm
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    var list = response.data || [];
                    $tbody.empty();

                    if (list.length === 0) {
                        $tbody.html('<tr><td colspan="6" class="text-center text-muted py-3">No records found</td></tr>');
                        return;
                    }

                    list.forEach(function (row) {
                        var html = `<tr>
                            <td>${row.id}</td>
                            <td>${row.gatepass_code}</td>
                            <td>${row.bill_number || row.invoice_id}</td>
                            <td>${row.name}</td>
                            <td>${row.gatepass_date}</td>
                            <td>
                                <a href="gatepass-print.php?id=${row.id}" class="btn btn-sm btn-soft-primary" target="_blank" title="Print/View">
                                    <i class="uil uil-print"></i>
                                </a>
                                <button class="btn btn-sm btn-soft-danger delete-gatepass" data-id="${row.id}" title="Delete">
                                    <i class="uil uil-trash-alt"></i>
                                </button>
                            </td>
                        </tr>`;
                        $tbody.append(html);
                    });
                } else {
                    $tbody.html('<tr><td colspan="6" class="text-center text-danger py-3">Error loading data</td></tr>');
                }
            },
            error: function () {
                $tbody.html('<tr><td colspan="6" class="text-center text-danger py-3">Server error</td></tr>');
            }
        });
    }

    $(document).on('click', '.delete-gatepass', function () {
        var id = $(this).data('id');

        swal({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: "ajax/php/gatepass.php",
                    type: "POST",
                    data: {
                        action: 'delete',
                        id: id
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            swal({
                                type: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }, function () {
                                loadGatepassList($('#gatepassSearchInput').val());
                            });
                        } else {
                            swal({
                                type: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function () {
                        swal({
                            type: 'error',
                            title: 'Error',
                            text: 'An error occurred while deleting.'
                        });
                    }
                });
            }
        });
    });

});
