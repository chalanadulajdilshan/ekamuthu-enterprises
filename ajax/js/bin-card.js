jQuery(document).ready(function ($) {

    // Set a default department ID if not set
    if (!$('#filter_department_id').val()) {
        var firstDeptId = $('#filter_department_id option:first').val();
        if (firstDeptId) {
            $('#filter_department_id').val(firstDeptId);
        }
    }

    // Destroy existing DataTable instance if already initialized (avoid double init from global scripts)
    if ($.fn.DataTable.isDataTable('#datatable')) {
        $('#datatable').DataTable().clear().destroy();
    }

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
                return d;
            },
            dataSrc: function (json) {
                if (json.error) {
                    console.error("Error from server:", json.message);
                    return [];
                }
                return json.data || [];
            },
            error: function (xhr, error, thrown) {
                console.error("AJAX Error:", error);
                console.error("Status:", xhr.status);
                console.error("Response:", xhr.responseText);
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
        order: [[1, 'asc']],
        pageLength: 100,
        error: function(settings, techNote, message) {
            console.error('DataTables Error:', message);
            $('.dataTables_empty').html(
                '<div class="alert alert-danger">' +
                '   <i class="fa fa-exclamation-triangle"></i> ' +
                '   Error loading data. Please try again or contact support.' +
                '</div>'
            );
        }
    });

    // Department filter change handler
    $('#filter_department_id').on('change', function() {
        table.ajax.reload();
    });

    // Toggle zero quantity items
    $(document).on('change', '#showZeroQty', function() {
        table.ajax.reload();
    });

    // On row click, load selected item into input fields
    $('#datatable tbody').on('click', 'tr', function () {
        var data = table.row(this).data();
        if (!data) return;

        const salesType = $('#sales_type').val();
        const paymentType = $('#payment_type').val();

        if (salesType == 1) {  // Whole Sales
            $('#itemPrice').val(data.cash_price.replace(/,/g, ''));
        } else if (salesType == 2) {  // Retail Sales
            $('#itemPrice').val(data.credit_price.replace(/,/g, ''));
        }

        if (paymentType == 1) {
            $('#itemDiscount').val(data.cash_discount);
        } else if (paymentType == 2) {
            $('#itemDiscount').val(data.credit_discount);
        } else {
            $('#itemDiscount').val(0);
        }

        $('#item_id').val(data.id);
        $('#itemCode').val(data.code);
        $('#itemName').val(data.name);
        $('#itemQty').val(1);

        loadDepartmentStockTable(data.id);
        $('#showTransactions').prop('checked', false);
        $('#transactionTable').hide(); // Hide the table
        $('#transactionTableBody').html('<tr><td colspan="6" class="text-muted text-center">No items added</td></tr>'); // Clear old data
        initializeStockInfoListener();
        setTimeout(() => $('#itemQty').focus(), 200);

        $('#department_stock').modal('hide');
    });

    // Show/hide transactions table on checkbox toggle
    $('#showTransactions').on('change', function () {
        if ($(this).is(':checked')) {
            $('#transactionTable').slideDown();
        } else {
            $('#transactionTable').slideUp();
        }
    });

    // Department change handler
    $('#department_id').on('change', function () {
        const itemId = $('#item_id').val();
        if (itemId) {
            loadTransactionData(itemId, $(this).val());
            updateStockInfo(itemId, $(this).val(), parseInt($('#selectDays').val()));
        }
    });

    // Date select handling
    const $selectDays = $('#selectDays');
    const $dateFrom = $('#dateFrom');
    const $dateTo = $('#dateTo');

    function formatDate(date) {
        const d = date.getDate().toString().padStart(2, '0');
        const m = (date.getMonth() + 1).toString().padStart(2, '0');
        const y = date.getFullYear();
        return `${y}-${m}-${d}`;
    }

    function parseDate(str) {
        if (!str) return null;
        const [y, m, d] = str.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function addDays(date, days) {
        const copy = new Date(date);
        copy.setDate(copy.getDate() + days);
        return copy;
    }

    // Set From and To dates based on selected days (To = today, From = today - days)
    function updateFromToByDays() {
        const days = parseInt($selectDays.val(), 10);
        const today = new Date();
        const fromDate = addDays(today, -days);

        $dateFrom.val(formatDate(fromDate));
        $dateTo.val(formatDate(today));
    }

    // When From date changes manually, update To date by adding days
    function updateToByFromAndDays() {
        const days = parseInt($selectDays.val(), 10);
        const fromVal = $dateFrom.val();
        if (!fromVal) return;

        const fromDate = parseDate(fromVal);
        if (!fromDate) return;

        const toDate = addDays(fromDate, days);
        $dateTo.val(formatDate(toDate));
    }

    // On page load initialize dates
    updateFromToByDays();

    // When days dropdown changes, update both dates
    $selectDays.on('change', updateFromToByDays);

    // When from date changes manually, update to date
    $dateFrom.on('change', updateToByFromAndDays);

    initializeStockInfoListener();

    function initializeStockInfoListener() {
        const $itemId = $('#item_id');
        const $departmentId = $('#department_id');
        const $selectDays = $('#selectDays');
        const $dateFrom = $('#dateFrom');
        const $dateTo = $('#dateTo');
        const $showTransactions = $('#showTransactions');

        function updateStockByInputs() {
            const itemId = $itemId.val();
            const departmentId = $departmentId.val();
            const daysCount = parseInt($selectDays.val(), 10);

            updateStockInfo(itemId, departmentId, daysCount);

            loadDepartmentStockTable(itemId);

            // If transactions are visible, reload them on input changes
            if ($showTransactions.is(':checked')) {
                loadTransactionData(itemId, departmentId);
            }
        }

        // Bind change listeners
        $itemId.on('change', updateStockByInputs);
        $departmentId.on('change', updateStockByInputs);
        $selectDays.on('change', updateStockByInputs);
        $dateFrom.on('change', updateStockByInputs);
        $dateTo.on('change', updateStockByInputs);

        // Show/hide transaction table on checkbox toggle
        $showTransactions.on('change', function () {
            if ($(this).is(':checked')) {
                $('#transactionTable').show();
                loadTransactionData($itemId.val(), $departmentId.val());
            } else {
                $('#transactionTable').hide();
                $('#transactionTableBody').html('<tr><td colspan="6" class="text-center">No items added</td></tr>');
            }
        });

        // Initial call on load
        updateStockByInputs();
    }

    function updateStockInfo(itemId, departmentId, daysCount) {
        const $stockInfo = $('#stock-info');
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();

        if (!itemId || !departmentId || !dateFrom || !dateTo) {
            $stockInfo.html(`
            <div class="bg-warning text-dark text-center p-2 rounded">
                No Item Data
            </div>
        `);
            return;
        }

        // Show loading UI
        $stockInfo.html(`
        <div class="bg-secondary text-white text-center p-2 rounded mb-2">
            Days Selected<br><strong>${daysCount}</strong>
        </div>
        <div class="bg-info text-white text-center p-2 rounded">
            Available Qty<br><strong>Loading...</strong>
        </div>
    `);

        $.ajax({
            url: 'ajax/php/stock-transfer.php',
            method: 'POST',
            data: {
                action: 'get_available_qty_by_dates',
                item_id: itemId,
                department_id: departmentId,
                days: daysCount,
                date_from: dateFrom,
                date_to: dateTo
            },
            dataType: 'json',
            success: function (response) {
                $stockInfo.html(`
                <div class="bg-secondary text-white text-center p-2 rounded mb-2">
                    Days Selected<br><strong>${daysCount}</strong>
                </div>
                <div class="bg-success text-white text-center p-2 rounded">
                    Available Qty<br><strong>${response.available_qty}</strong>
                </div>
            `);
            },
            error: function () {
                $stockInfo.html(`
                <div class="bg-danger text-white text-center p-2 rounded">
                    Failed to load stock
                </div>
            `);
            }
        });
    }

    function loadTransactionData(itemId, departmentId) {
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        
        if (!itemId || !departmentId || !dateFrom || !dateTo) {
            $('#transactionTableBody').html('<tr><td colspan="8" class="text-center text-warning">Please select Item, Department, and Date range first.</td></tr>');
            return;
        }

        $('#transactionTableBody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');
        
        $.ajax({
            url: 'ajax/php/stock-transfer.php',
            type: 'POST',
            data: {
                action: 'get_transaction_records',
                item_id: itemId,
                department_id: departmentId,
                date_from: dateFrom,
                date_to: dateTo
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.transactions && response.transactions.length > 0) {
                    let html = '';
                    let runningBalance = 0;
                    
                    response.transactions.forEach((tx, index) => {
                        runningBalance += parseFloat(tx.qty_in || 0) - parseFloat(tx.qty_out || 0);
                        
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${tx.created_at || ''}</td>
                                <td>${tx.type_name || ''}</td>
                                <td>${tx.remark || ''}</td>
                                <td>${tx.type_direction || ''}</td>
                                <td class="text-end">${tx.qty_in || '0.00'}</td>
                                <td class="text-end">${tx.qty_out || '0.00'}</td>
                                <td class="text-end">${runningBalance.toFixed(2)}</td>
                            </tr>`;
                    });
                    
                    $('#transactionTableBody').html(html);
                } else {
                    $('#transactionTableBody').html('<tr><td colspan="8" class="text-center text-muted">No transactions found</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading transactions:', error);
                $('#transactionTableBody').html('<tr><td colspan="8" class="text-center text-danger">Error loading transactions</td></tr>');
            }
        });
    }

    function loadDepartmentStockTable(itemId) {
        if (!itemId) {
            $('#departmentStockTable tbody').html(`
            <tr><td colspan="3" class="text-center text-muted">Please select an item</td></tr>
        `);
            return;
        }

        $('#departmentStockTable tbody').html(`
        <tr><td colspan="3" class="text-center">Loading...</td></tr>
    `);

        $.ajax({
            url: 'ajax/php/stock-transfer.php',
            method: 'POST',
            data: {
                action: 'get_department_stock_status',
                item_id: itemId
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    const rows = response.data.map(dept => `
                    <tr>
                        <td>${dept.department_name}</td>
                        <td>${dept.available_qty}</td>
                        <td>${dept.pending_orders}</td>
                    </tr>
                `).join('');

                    $('#departmentStockTable tbody').html(rows);
                } else {
                    $('#departmentStockTable tbody').html(`
                    <tr><td colspan="3" class="text-center text-danger">${response.message}</td></tr>
                `);
                }
            },
            error: function () {
                $('#departmentStockTable tbody').html(`
                <tr><td colspan="3" class="text-center text-danger">Failed to load data</td></tr>
            `);
            }
        });
    }

    // Update the department stock button click handler
    $(document).on('click', '.btn-department-stock', function() {
        var itemCode = $('#itemCode').val();
        if (!itemCode) {
            alert('Please select an item first');
            return;
        }
        // Redirect to department-stock-model.php with item code as parameter
        window.location.href = 'department-stock-model.php?item_code=' + encodeURIComponent(itemCode);
    });

});
