jQuery(document).ready(function () {
    // Set a default department ID if not set
    if (!$('#filter_department_id').val()) {
        var firstDeptId = $('#filter_department_id option:first').val();
        if (firstDeptId) {
            $('#filter_department_id').val(firstDeptId);
        }
    }

    // Add zero quantity toggle button
    const zeroQtyToggle = `
        <div class="form-check form-switch d-inline-block ms-2">
            <input class="form-check-input" type="checkbox" id="showZeroQty">
            <label class="form-check-label" for="showZeroQty">Show Zero Quantity Items</label>
        </div>
    `;
    $('.dataTables_filter').append(zeroQtyToggle);

    // DataTable config
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/php/item-master.php",
            type: "POST",
            data: function (d) {
                d.action = 'fetch_for_stock_adjustment';
                d.department_id = $('#filter_department_id').val();
                d.show_zero_qty = $('#showZeroQty').is(':checked');
                console.log('Sending request with:', d);
                return d;
            },
            dataSrc: function (json) {
                console.log("Server Response:", json);
                
                // Check if the response has an error
                if (json.error) {
                    console.error("Error from server:", json.message);
                    return [];
                }
                
                // Make sure data is an array
                if (!Array.isArray(json.data)) {
                    console.error("Expected data to be an array, got:", typeof json.data);
                    return [];
                }
                
                return json.data;
            },
            error: function (xhr, error, thrown) {
                console.error("AJAX Error:", error);
                console.error("Status:", xhr.status);
                console.error("Response:", xhr.responseText);
                // Return empty array to prevent DataTables error
                return [];
            }
        },
        columns: [
            { 
                data: null,
                title: "#",
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                },
                orderable: false
            },
            { data: "code", title: "Code" },
            { data: "name", title: "Name" },
            { data: "category", title: "Category" },
            { data: "list_price", title: "List Price" },
            { data: "invoice_price", title: "Invoice Price" },
            {
                data: "available_qty",
                title: "Available Qty",
                render: function (data, type, row) {
                    return parseInt(data) || 0;
                }
            },
        ],
        order: [[0, 'desc']],
        pageLength: 100,
        error: function(settings, techNote, message) {
            console.error('DataTables Error:', message);
            // Show a user-friendly error message
            $('.dataTables_empty').html(
                '<div class="alert alert-danger">' +
                '   <i class="fa fa-exclamation-triangle"></i> ' +
                '   Error loading data. Please try again or contact support.' +
                '</div>'
            );
        }
    });

    // Department filter change handler
    $('#filter_department_id').on('change', function () {
        table.ajax.reload();
    });

    // Toggle zero quantity items
    $(document).on('change', '#showZeroQty', function() {
        table.ajax.reload();
    });

    // Search item handler with debounce
    let searchTimeout;
    $('#search_item').on('keyup', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            table.search($('#search_item').val()).draw();
        }, 500);
    });

    let focusAfterModal = false;

    // On row click, load selected item into input fields
    $('#datatable tbody').on('click', 'tr', function () {
        var data = table.row(this).data();
        if (!data) return;

        $('#item_id').val(data.id);
        $('#itemCode').val(data.code);
        $('#itemName').val(data.name);
        $('#itemQty').val(1);
        $('#available_qty').val(data.available_qty || 0);

        // Enable the adjustment type radio buttons
        $('input[name="adjustment_type"]').prop('disabled', false);
        
        // Set focusAfterModal flag
        focusAfterModal = true;
        
        // Hide the modal after selection
        $('#department_stock').modal('hide');
    });

    // Handle modal hidden event
    $('#department_stock').on('hidden.bs.modal', function () {
        if (focusAfterModal) {
            setTimeout(() => {
                $('#itemQty').trigger('focus');
                focusAfterModal = false;
            }, 100);
        }
    });

    // Rest of your existing code...
    $('#department_stock').on('hidden.bs.modal', function () {
        if (focusAfterModal) {
            $('#itemQty').focus();
            focusAfterModal = false;
        }
    });


    //remove all added items department change
    $('#department_id').on('change', function () {
        const table = $('#show_table');
        table.html(`
        <tr id="noItemRow">
            <td colspan="8" class="text-center text-muted">No items added</td>
        </tr>
    `);

        // Clear inputs
        $('#item_id, #itemCode, #itemName, #itemQty, #available_qty').val('');
    });


    document.querySelector('#add_item').addEventListener('click', function () {
        const item_id = document.getElementById('item_id').value.trim();
        const itemCode = document.getElementById('itemCode').value.trim();
        const itemName = document.getElementById('itemName').value.trim();
        const itemQty = document.getElementById('itemQty').value.trim();

        if (!itemCode || !itemName || !itemQty || parseInt(itemQty) <= 0) {
            swal({
                title: "Error!",
                text: "Please enter valid item code, name, and quantity",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        const table = document.getElementById('show_table');

        const existingItems = table.querySelectorAll('input[name="item_codes[]"]');
        for (const input of existingItems) {
            if (input.value === item_id) {
                swal({
                    title: "Duplicate!",
                    text: "This item is already added to the table.",
                    type: "warning",
                    timer: 2000,
                    showConfirmButton: false,
                });
                return;
            }
        }

        // Remove "No items added" row if it exists
        const noItemRow = document.getElementById('noItemRow');
        if (noItemRow) {
            noItemRow.remove();
        }

        const rowCount = table.querySelectorAll('tr').length;
        const serialKey = rowCount + 1;

        const row = document.createElement('tr');
        row.innerHTML = `
             <td> ${serialKey}</td>
    <td><input type="hidden" name="item_codes[]" value="${item_id}">${itemCode}</td>
    <td><input type="hidden" name="item_names[]" value="${itemName}">${itemName}</td>
    <td><input type="hidden" name="item_qtys[]" value="${itemQty}">${itemQty}</td>
    <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
`;

        table.appendChild(row);
        row.querySelector('.remove-row').addEventListener('click', function () {
            row.remove();
            const rows = table.querySelectorAll('tr');
            if (rows.length === 0) {
                table.innerHTML = `
            <tr id="noItemRow">
                <td colspan="8" class="text-center text-muted">No items added</td>
            </tr>`;
            } else {
                // Re-index the serial keys
                rows.forEach((tr, index) => {
                    tr.querySelector('td').textContent = index + 1;
                });
            }
        });
        // Clear input fields
        document.getElementById('itemCode').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('itemQty').value = '';
        document.getElementById('itemCode').focus();

    });

    document.getElementById('itemQty').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission if inside a form
            document.getElementById('add_item').click(); // Trigger the same logic
        }
    });

    // Add this event listener for when the modal is shown
    $('#department_stock').on('show.bs.modal', function (event) {
        // Clear previous values
        $('#available_qty').val('0');
        
        // If we have a selected item, get its available quantity
        var selectedItemId = $('#item_id').val();
        var departmentId = $('#filter_department_id').val();
        
        if (selectedItemId && departmentId) {
            getAvailableQuantity(selectedItemId, departmentId, function(qty) {
                $('#available_qty').val(qty);
            });
        }
    });

    // Update the item selection handler to update available quantity
    $('#datatable tbody').on('click', 'tr', function () {
        var data = table.row(this).data();
        if (!data) return;

        $('#item_id').val(data.id);
        $('#itemCode').val(data.code);
        $('#itemName').val(data.name);
        $('#itemQty').val(1);
        var departmentId = $('#filter_department_id').val();
        if (data.id && departmentId) {
            getAvailableQuantity(data.id, departmentId, function(qty) {
                $('#available_qty').val(qty);
            });
        } else {
            $('#available_qty').val('0');
        }
    });

    $('#create').on('click', function () {
        const departmentId = $('#filter_department_id').val();
        const adjustmentType = $('input[name="adjustment_type"]:checked').val();
        const specialInstructions = $('#special_instructions').val();

        // Debug logging
        console.log('Department ID:', departmentId);
        console.log('Adjustment Type:', adjustmentType);
        
        // Check table rows
        const allRows = $('#show_table tr');
        const itemRows = allRows.not('#noItemRow');
        console.log('All rows:', allRows.length);
        console.log('Item rows:', itemRows.length);
        console.log('Row HTML:', $('#show_table').html());

        const hasItems = itemRows.length > 0;

        if (!departmentId || !adjustmentType || !hasItems) {
            console.log('Validation failed - missing:', {
                departmentId: !departmentId,
                adjustmentType: !adjustmentType,
                hasItems: !hasItems
            });
            swal({
                title: "Error!",
                text: "Please complete all required fields and add at least one item.",
                type: 'error',
                timer: 2500,
                showConfirmButton: false
            });
            return;
        }
        if (!specialInstructions) {
            swal({
                title: "Error!",
                text: "Please enter special instructions.",
                type: 'error',
                timer: 2500,
                showConfirmButton: false
            });
            return;
        }
        const formData = new FormData();
        formData.append('action', 'create_stock_adjustment');
        formData.append('department_id', departmentId);
        formData.append('adjustment_type', adjustmentType);
        formData.append('special_instructions', specialInstructions);

        // Append item data
        $('#show_table tr').not('#noItemRow').each(function () {
            const itemId = $(this).find('input[name="item_codes[]"]').val();
            const code = $(this).find('td:eq(1)').text().trim();
            const name = $(this).find('td:eq(2)').text().trim();
            const qty = $(this).find('td:eq(3)').text().trim();

            formData.append('item_ids[]', itemId);
            formData.append('item_codes[]', code);
            formData.append('item_names[]', name);
            formData.append('item_qtys[]', qty);
        });

        $(".someBlock").preloader(); // start loader

        $.ajax({
            url: 'ajax/php/stock-adjustment.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $(".someBlock").preloader("remove"); // stop loader

                let res = {};
                try {
                    res = typeof response === 'object' ? response : JSON.parse(response);
                } catch (e) {
                    console.error('Invalid JSON:', response);
                    swal({
                        title: "Error!",
                        text: "Server returned an invalid response.",
                        type: 'error',
                        timer: 2500,
                        showConfirmButton: false
                    });
                    return;
                }

                if (res.status === 'success') {
                    swal({
                        title: "Success!",
                        text: "Stock adjustment saved successfully.",
                        type: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal({
                        title: "Error!",
                        text: res.message || "Failed to save stock adjustment.",
                        type: 'error',
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            },
            error: function () {
                $(".someBlock").preloader("remove");
                swal({
                    title: "Error!",
                    text: "An unexpected error occurred while saving.",
                    type: 'error',
                    timer: 2500,
                    showConfirmButton: false
                });
            }
        });
    });
});