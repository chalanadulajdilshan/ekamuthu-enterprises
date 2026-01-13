$(document).ready(function() {
    // Load customers on page load
    loadCustomers();

    // Set today's date for return_date field
    $("#return_date").val($.datepicker.formatDate('yy-mm-dd', new Date()));

    // Load next sales return reference number
    loadNextReturnNo();

    // Invoice search functionality
    $("#invoice_no").on("change", function() {
        const invoiceNo = $(this).val().trim();
        if (invoiceNo) {
            checkInvoiceExists(invoiceNo);
        }
    });

    // New button functionality
    $("#new").click(function(e) {
        e.preventDefault();
        resetForm();
        setEditMode();
    });

    // Save button functionality
    $("#create").click(function(e) {
        e.preventDefault();
        saveSalesReturn();
    });

    // Print button functionality
    $("#print").click(function(e) {
        e.preventDefault();
        // Implement print functionality
        swal({
            title: 'Print Feature',
            text: 'Print functionality will be implemented soon.',
            type: 'info',
            confirmButtonText: 'OK'
        });
    });

    // Delete button functionality
    $("#delete-category").click(function(e) {
        e.preventDefault();
        const returnId = $("#return_id").val();
        if (returnId) {
            deleteSalesReturn(returnId);
        } else {
            swal({
                title: 'Warning',
                text: 'Please select a sales return to delete',
                type: 'warning',
                confirmButtonText: 'OK'
            });
        }
    });

    // Invoice modal search - removed old handler

    // Load invoice items when invoice is selected
    $("#invoice_no").on("change", function() {
        const invoiceNo = $(this).val().trim();
        if (invoiceNo) {
            loadInvoiceItems(invoiceNo);
        }
    });

    // Load invoices when modal is shown
    $("#invoiceModal").on("shown.bs.modal", function() {
        displayInvoicesList();
    });

    // Load sales returns when sales return modal is shown
    $("#salesReturnModal").on("shown.bs.modal", function() {
        console.log("Sales return modal shown, calling displaySalesReturnsList");
        displaySalesReturnsList();
    });
});

// Load customers for dropdown
function loadCustomers() {
    $.ajax({
        url: "ajax/php/customer-master.php",
        type: "POST",
        dataType: "json",
        data: { action: "load_customers" },
        success: function(data) {
            // This would populate customer dropdown if needed
            console.log("Customers loaded:", data);
        },
        error: function(xhr, status, error) {
            console.error("Error loading customers:", error);
        }
    });
}

// Load next sales return number for Ref No field
function loadNextReturnNo() {
    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: { action: "get_next_return_no" },
        success: function(response) {
            if (response.status === 'success' && response.return_no) {
                $("#return_no").val(response.return_no);
            } else {
                console.warn("Failed to get next return number", response);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error getting next return number:", error);
        }
    });
}

// Check if invoice exists and load details
function checkInvoiceExists(invoiceNo) {
    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "check_invoice_exists",
            invoice_no: invoiceNo
        },
        success: function(response) {
            if (response.exists) {
                populateInvoiceData(response.invoice, response.customer, response.department);
            } else {
                swal({
                    title: 'Invoice Not Found',
                    text: 'The selected invoice was not found. Please check the invoice number.',
                    type: 'warning',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error checking invoice:", error);
            swal({
                title: 'Error',
                text: 'Error checking invoice. Please try again.',
                type: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

// Populate invoice data in form
function populateInvoiceData(invoice, customer, department) {
    console.log("Populating invoice data:", invoice);
    $("#invoice_id").val(invoice.id);
    $("#customer_id").val(customer.id);
    $("#customer_code").val(customer.code || customer.id);
    $("#customer_name").val(customer.name);
    $("#customer_address").val(customer.address);
    $("#department_id").val(department.id).trigger('change');

    // Set payment type and other invoice details
    $("#payment_type").val(invoice.payment_type == 1 ? "Cash" : "Credit");
    $("#invoice_date").val(invoice.invoice_date);

    // Load invoice items after invoice_id is set
    console.log("Loading items for invoice ID:", invoice.id);
    loadInvoiceItems(invoice.id);
}

// Clear invoice data
function clearInvoiceData() {
    $("#invoice_id, #customer_id, #customer_code, #customer_name, #customer_address, #payment_type, #invoice_date").val("");
    $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items added</td></tr>');
    calculateTotals();
}

// Load invoice items
function loadInvoiceItems(invoiceId) {
    console.log("loadInvoiceItems called with ID:", invoiceId);
    
    if (!invoiceId) {
        console.error("No invoice ID provided");
        return;
    }

    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "fetch_invoice_items",
            invoice_id: invoiceId
        },
        success: function(response) {
            console.log("Invoice items response:", response);
            if (response.items && response.items.length > 0) {
                populateInvoiceItems(response.items);
            } else {
                console.warn("No items found in response");
                $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items found for this invoice</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading invoice items:", error);
            console.error("XHR:", xhr.responseText);
            $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-danger">Error loading items</td></tr>');
        }
    });
}

// Populate invoice items table
function populateInvoiceItems(items) {
    console.log("Populating", items.length, "items");
    console.log("Items data:", items);
    let html = "";

    if (items && items.length > 0) {
        items.forEach(function(item, index) {
            console.log("Processing item", index, ":", item);
            const itemSubtotal = (item.quantity * item.unit_price) - (item.discount || 0);

            // Show available quantity in the invoice qty column
            const invoiceQtyDisplay = item.original_quantity !== item.quantity ? 
                `${parseFloat(item.quantity).toFixed(2)} <small class="text-muted">(of ${parseFloat(item.original_quantity).toFixed(2)})</small>` : 
                parseFloat(item.quantity).toFixed(2);

            html += `
                <tr data-item-id="${item.item_id}">
                    <td>${item.item_code}</td>
                    <td>${item.item_name}</td>
                    <td class="text-end">${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td class="text-end">${invoiceQtyDisplay}</td>
                    <td>
                        <input type="number" class="form-control item-return-qty" value="0" min="0" step="0.01" max="${item.quantity}" data-max="${item.quantity}" data-price="${item.unit_price}" data-discount="${item.discount || 0}">
                    </td>
                    <td class="text-end item-subtotal">0.00</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        console.log("Generated HTML:", html);
    } else {
        html = '<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items available for return from this invoice</td></tr>';
    }

    $("#invoiceItemsBody").html(html);
    console.log("Set HTML to table body");
    calculateTotals();
}

// Populate return items table
function populateReturnItems(items) {
    console.log("populateReturnItems called with", items.length, "items");
    console.log("Items data:", items);
    let html = "";

    if (items && items.length > 0) {
        console.log("Processing", items.length, "items...");
        items.forEach(function(item, index) {
            console.log("Processing item", index, ":", item);
            html += `
                <tr data-item-id="${item.item_id}">
                    <td>${item.item_code}</td>
                    <td>${item.item_name}</td>
                    <td class="text-end">${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(item.original_quantity || item.quantity).toFixed(2)}</td>
                    <td>
                        <input type="number" class="form-control item-return-qty" value="${item.quantity}" min="0" step="0.01" max="${item.original_quantity || item.quantity}" data-max="${item.original_quantity || item.quantity}" data-price="${item.unit_price}" data-discount="${item.discount || 0}" readonly>
                    </td>
                    <td class="text-end item-subtotal">${(item.quantity * item.unit_price - (item.discount || 0)).toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-item" style="display: none;">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        console.log("Generated HTML length:", html.length);
        console.log("Generated HTML preview:", html.substring(0, 200) + "...");
    } else {
        html = '<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items found</td></tr>';
        console.log("No items, using default HTML");
    }

    console.log("About to set HTML to #invoiceItemsBody");
    console.log("Element exists:", $("#invoiceItemsBody").length);
    $("#invoiceItemsBody").html(html);
    console.log("HTML set successfully");
    
    // Verify the HTML was set
    setTimeout(function() {
        console.log("Verifying table content:", $("#invoiceItemsBody").html().substring(0, 100) + "...");
    }, 100);
    
    calculateTotals();
}

// Calculate totals
function calculateTotals() {
    let grandTotal = 0;

    $("#invoiceItemsBody tr").each(function() {
        const row = $(this);
        if (!row.attr('id') || row.attr('id') !== 'noItemRow') {
            const returnQtyInput = row.find(".item-return-qty");
            const returnQty = parseFloat(returnQtyInput.val()) || 0;
            const price = parseFloat(returnQtyInput.data("price")) || 0;
            const discount = parseFloat(returnQtyInput.data("discount")) || 0;

            // Calculate subtotal for this row
            const rowSubtotal = (price * returnQty) - (discount * (returnQty / parseFloat(returnQtyInput.attr("max"))));
            
            // Update the row subtotal display
            row.find(".item-subtotal").text(rowSubtotal.toFixed(2));
            
            grandTotal += rowSubtotal;
        }
    });

    // Update the grand total if you have a field for it
    $("#finalTotal").val(grandTotal.toFixed(2));
}

// Save sales return
function saveSalesReturn() {
    console.log("Starting save process...");
    
    // Validate required fields
    if (!$('#return_date').val()) {
        swal({
            title: "Validation Error",
            text: "Please select a return date",
            type: 'warning',
            confirmButtonText: 'OK'
        }, function() {
            $('#return_date').focus();
        });
        return;
    }
    
    if (!$('#invoice_no').val()) {
        swal({
            title: "Validation Error", 
            text: "Please select an invoice",
            type: 'warning',
            confirmButtonText: 'OK'
        }, function() {
            $('#invoice_no').focus();
        });
        return;
    }
    
    // Optional: Check if customer_id is available (not required for save)
    if (!$('#customer_id').val()) {
        console.warn("Customer ID is missing, but proceeding with save anyway");
    }
    
    const returnData = {
        return_date: $("#return_date").val(),
        invoice_no: $("#invoice_no").val(),
        customer_id: $("#customer_id").val(),
        total_amount: $("#finalTotal").val(),
        return_reason: $("#remark").val(),
        remarks: $("#remark").val(),
        is_damaged: $("#is_damaged").is(":checked") ? 1 : 0
    };
    
    console.log("Return data:", returnData);

    const returnItems = [];
    $("#invoiceItemsBody tr").each(function() {
        const row = $(this);
        if (!row.attr('id') || row.attr('id') !== 'noItemRow') {
            const returnQtyInput = row.find(".item-return-qty");
            const returnQty = parseFloat(returnQtyInput.val()) || 0;
            if (returnQty > 0) {
                const price = parseFloat(returnQtyInput.data("price")) || 0;
                const discount = parseFloat(returnQtyInput.data("discount")) || 0;
                const maxQty = parseFloat(returnQtyInput.attr("max")) || 0;
                const proportionalDiscount = discount * (returnQty / maxQty);
                
                returnItems.push({
                    item_id: row.data("item-id"),
                    quantity: returnQty,
                    unit_price: price,
                    discount: proportionalDiscount,
                    tax: 0,
                    net_amount: (price * returnQty) - proportionalDiscount,
                    remarks: ""
                });
            }
        }
    });
    
    console.log("Return items:", returnItems);

    if (returnItems.length === 0) {
        swal({
            title: "Validation Error",
            text: 'Please enter return quantity for at least one item',
            type: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Show loading state
    $("#create").prop('disabled', true).html('<i class="uil uil-save me-1"></i> Saving...');

    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "save_sales_return",
            return_data: JSON.stringify(returnData),
            return_items: JSON.stringify(returnItems)
        },
        success: function(response) {
            console.log("Save response:", response);
            if (response.status === 'success') {
                swal({
                    title: "Success",
                    text: 'Sales return saved successfully! Return No: ' + response.return_no,
                    type: response.type || 'success',
                    confirmButtonText: 'OK'
                }, function() {
                    // Update the form with the generated return number
                    $("#return_no").val(response.return_no);
                    // Show delete button since we now have a saved return
                    $("#delete-category").show();
                    resetForm();
                });
            } else {
                swal({
                    title: "Error",
                    text: response.message || 'Unknown error occurred',
                    type: response.type || 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error saving sales return:", error);
            console.error("XHR response:", xhr.responseText);
            swal({
                title: 'Error',
                text: 'Error saving sales return. Please try again.',
                type: 'error',
                confirmButtonText: 'OK'
            });
        },
        complete: function() {
            $("#create").prop('disabled', false).html('<i class="uil uil-save me-1"></i> Save');
        }
    });
}

// Reset form
function resetForm() {
    $("#form-data")[0].reset();
    $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items added</td></tr>');
    $("#return_id").val(""); // Clear return ID
    calculateTotals();

    // After reset, load a fresh reference number for the next return
    loadNextReturnNo();
}

// Search/filter invoices in the table
$(document).on("keyup", "#invoiceSearch", function() {
    const searchTerm = $(this).val().toLowerCase();
    
    $("#invoiceTableBody tr").each(function() {
        const row = $(this);
        const text = row.text().toLowerCase();
        
        if (text.indexOf(searchTerm) > -1) {
            row.show();
        } else {
            row.hide();
        }
    });
});

// Search/filter sales returns in the table
$(document).on("keyup", "#grnSearch", function() {
    const searchTerm = $(this).val().toLowerCase();

    $("#grnTableBody tr").each(function() {
        const row = $(this);
        const text = row.text().toLowerCase();

        if (text.indexOf(searchTerm) > -1) {
            row.show();
        } else {
            row.hide();
        }
    });
});

// Display sales returns list in modal
function displaySalesReturnsList() {
    console.log("displaySalesReturnsList called");
    $("#grnTableBody").html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

    console.log("Making AJAX call to get sales returns");

    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "get_sales_returns"
        },
        success: function(response) {
            console.log("Sales returns AJAX success response:", response);

            if (response.returns && response.returns.length > 0) {
                console.log("Found", response.returns.length, "sales returns");
                let html = '';

                response.returns.forEach(function(return_item, index) {
                    html += `
                        <tr class="select-grn-row" style="cursor: pointer;" data-return-no="${return_item.return_no}" data-return-id="${return_item.id}">
                            <td>${index + 1}</td>
                            <td>${return_item.return_no}</td>
                            <td>${return_item.return_date}</td>
                            <td>${return_item.invoice_no}</td>
                            <td>${return_item.customer_name}</td>
                            <td class="text-end">${parseFloat(return_item.total_amount).toFixed(2)}</td>
                        </tr>
                    `;
                });

                console.log("Setting HTML with", response.returns.length, "sales returns");
                $("#grnTableBody").html(html);
            } else {
                console.log("No sales returns found in response");
                $("#grnTableBody").html('<tr><td colspan="6" class="text-center text-muted">No sales returns found</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading sales returns:", error);
            console.error("XHR status:", status);
            console.error("XHR response:", xhr.responseText);
            $("#grnTableBody").html('<tr><td colspan="6" class="text-center text-danger">Error loading sales returns</td></tr>');
        }
    });
}

// Handle sales return selection from table row
$(document).on("click", ".select-grn-row", function() {
    const returnNo = $(this).data("return-no");
    const returnId = $(this).data("return-id");
    console.log("Selected return:", returnNo, "ID:", returnId);
    $("#return_no").val(returnNo);
    $("#return_id").val(returnId);
    $("#salesReturnModal").modal('hide');

    // Load the return details and set to view-only mode
    if (returnId) {
        console.log("Calling loadSalesReturnDetails with ID:", returnId);
        loadSalesReturnDetails(returnId);
        // setViewOnlyMode() moved inside the AJAX success callback
    }
});

// Load sales return details when selected
function loadSalesReturnDetails(returnId) {
    console.log("Loading sales return details for ID:", returnId);

    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "get_sales_return_details",
            return_id: returnId
        },
        success: function(response) {
            console.log("Sales return details response:", response);

            if (response.return && response.items) {
                // Populate form with return data
                populateReturnData(response.return, response.items);
                // Set view-only mode after data is populated
                setViewOnlyMode();
            } else {
                swal({
                    title: 'Error',
                    text: 'Failed to load sales return details',
                    type: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading sales return details:", error);
            swal({
                title: 'Error',
                text: 'Error loading sales return details. Please try again.',
                type: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

// Populate return data in form
function populateReturnData(return_data, items) {
    console.log("populateReturnData called with return_data:", return_data);
    console.log("populateReturnData called with items:", items);

    $("#return_id").val(return_data.id);
    $("#return_no").val(return_data.return_no);
    $("#return_date").val(return_data.return_date);
    $("#invoice_no").val(return_data.invoice_no);
    $("#customer_id").val(return_data.customer.id);
    $("#customer_code").val(return_data.customer.code || return_data.customer.id);
    $("#customer_name").val(return_data.customer.name);
    $("#customer_address").val(return_data.customer.address);

    if (return_data.invoice_date) {
        $("#invoice_date").val(return_data.invoice_date);
    } else {
        $("#invoice_date").val("");
    }
    if (typeof return_data.payment_type !== "undefined" && return_data.payment_type !== null) {
        $("#payment_type").val(return_data.payment_type == 1 ? "Cash" : "Credit");
    } else {
        $("#payment_type").val("");
    }

    // Set damage checkbox state
    $("#is_damaged").prop("checked", return_data.is_damaged == 1);

    // Populate department if available
    if (return_data.department && return_data.department.id) {
        $("#department_id").val(return_data.department.id).trigger('change');
    }

    $("#remark").val(return_data.remarks);

    // Load invoice details based on invoice_no
    if (return_data.invoice_no) {
        // Note: We don't call checkInvoiceExists here because it would load invoice items
        // and overwrite our return items. We already have customer info from return data.
    }

    // Populate items table
    if (items && items.length > 0) {
        console.log("Calling populateReturnItems with", items.length, "items");
        populateReturnItems(items);
    } else {
        console.log("No items to populate or items array is empty");
    }
}

// Set form to view-only mode when a return is selected
function setViewOnlyMode() {
    console.log("Setting view-only mode");

    // Hide save button, show delete button for existing returns
    $("#create").hide();
    $("#delete-category").show();

    // Make form inputs read-only
    $("#return_date").prop("readonly", true).addClass("bg-light");
    $("#invoice_no").prop("readonly", true).addClass("bg-light");
    $("#remark").prop("readonly", true).addClass("bg-light");

    // Disable select dropdowns
    $("#department_id").prop("disabled", true).addClass("bg-light");

    // Disable damage checkbox
    $("#is_damaged").prop("disabled", true);

    // Make all item inputs read-only
    $(".item-return-qty").prop("readonly", true).addClass("bg-light");

    // Remove event handlers for editing
    $(".remove-item").hide();

    // Add visual indicator
    if (!$(".view-mode-indicator").length) {
        $("<div class='alert alert-info view-mode-indicator'><i class='fas fa-eye me-2'></i>You are viewing an existing sales return. Click 'New' to create a new one.</div>").insertAfter(".row.mb-4");
    }
}

// Set form to edit mode when new button is clicked
function setEditMode() {
    console.log("Setting edit mode");

    // Show save button, hide delete button initially
    $("#create").show();
    $("#delete-category").hide();

    // Make form inputs editable
    $("#return_date").prop("readonly", false).removeClass("bg-light");
    $("#invoice_no").prop("readonly", false).removeClass("bg-light");
    $("#remark").prop("readonly", false).removeClass("bg-light");

    // Enable select dropdowns
    $("#department_id").prop("disabled", false).removeClass("bg-light");

    // Enable damage checkbox
    $("#is_damaged").prop("disabled", false);

    // Clear readonly from item inputs
    $(".item-return-qty").prop("readonly", false).removeClass("bg-light");

    // Show remove buttons
    $(".remove-item").show();

    // Remove view mode indicator
    $(".view-mode-indicator").remove();
}

// Delete sales return
function deleteSalesReturn(returnId) {
    swal({
        title: 'Are you sure?',
        text: 'Do you want to delete this sales return? This action cannot be undone.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }, function(isConfirm) {
        if (isConfirm) {
            $.ajax({
                url: "ajax/php/sales-return.php",
                type: "POST",
                dataType: "json",
                data: {
                    action: "delete_sales_return",
                    return_id: returnId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        swal({
                            title: 'Success',
                            text: 'Sales return deleted successfully!',
                            type: response.type || 'success',
                            confirmButtonText: 'OK'
                        }, function() {
                            resetForm();
                        });
                    } else {
                        swal({
                            title: 'Error',
                            text: response.message || 'Failed to delete sales return',
                            type: response.type || 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error deleting sales return:", error);
                    swal({
                        title: 'Error',
                        text: 'Error deleting sales return. Please try again.',
                        type: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Bind calculation events
$(document).on("input", ".item-return-qty", function() {
    // Validate max quantity
    const max = parseFloat($(this).attr("max"));
    const val = parseFloat($(this).val());
    if (val > max) {
        $(this).val(max);
        swal({
            title: 'Quantity Limit Exceeded',
            text: 'Return quantity cannot exceed available quantity of ' + max.toFixed(2),
            type: 'warning',
            confirmButtonText: 'OK'
        });
    }
    calculateTotals();
});

// Remove item row
$(document).on("click", ".remove-item", function() {
    $(this).closest("tr").remove();
    
    // Check if no items left
    if ($("#invoiceItemsBody tr").length === 0) {
        $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items added</td></tr>');
    }
    
    calculateTotals();
});

// Display invoices list in modal
function displayInvoicesList() {
    console.log("displayInvoicesList called");
    $("#invoiceTableBody").html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');
    
    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "fetch_invoices"
        },
        success: function(response) {
            console.log("Response received:", response);
            
            if (response.status === 'success' && response.invoices && response.invoices.length > 0) {
                let html = '';
                
                response.invoices.forEach(function(invoice, index) {
                    html += `
                        <tr class="select-invoice-row" style="cursor: pointer;" data-invoice-no="${invoice.invoice_no}">
                            <td>${index + 1}</td>
                            <td>${invoice.invoice_no}</td>
                            <td>${invoice.invoice_date}</td>
                            <td>-</td>
                            <td>${invoice.customer_name}</td>
                            <td class="text-end">${parseFloat(invoice.grand_total).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                console.log("Setting HTML with", response.invoices.length, "invoices");
                $("#invoiceTableBody").html(html);
            } else {
                console.log("No invoices or invalid response");
                $("#invoiceTableBody").html('<tr><td colspan="6" class="text-center text-muted">No invoices found</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading invoices:", error);
            console.error("XHR:", xhr);
            $("#invoiceTableBody").html('<tr><td colspan="6" class="text-center text-danger">Error loading invoices</td></tr>');
        }
    });
}

// Handle invoice selection from table row
$(document).on("click", ".select-invoice-row", function() {
    const invoiceNo = $(this).data("invoice-no");
    $("#invoice_no").val(invoiceNo);
    $("#invoiceModal").modal('hide');
    checkInvoiceExists(invoiceNo);
});

