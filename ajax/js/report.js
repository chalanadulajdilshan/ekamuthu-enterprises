jQuery(document).ready(function () {
    // Initialize datatable for item selection
    if ($.fn.DataTable.isDataTable('#datatable')) {
        $('#datatable').DataTable().destroy();
    }

    var itemTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/php/item-master.php",
            type: "POST",
            data: function (d) {
                d.filter = true;
                d.status = 0;
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
            { data: "invoice_price", title: "Invoice Price" },
            { data: "qty", title: "Quantity" }
        ],
        order: [[0, 'desc']],
        pageLength: 100
    });

    // On row click in the item selection modal
    $('#datatable tbody').on('click', 'tr', function () {
        var data = itemTable.row(this).data();
        if (!data) return;

        // Set the selected item's code, name, and ID in the input fields
        $('#code').val(data.code);
        $('#name').val(data.name);
        $('#item_id').val(data.id);
        
        // Close the modal
        $('#main_item_master').modal('hide');
        
        // Show success message
        toastr.success('Item selected successfully!', 'Success');
        
        // Load profit report for the selected item
        loadProfitReport();
    });

    // Profit report - Load when view report button is clicked
    $('#view_profit_report').on('click', function (e) {
        e.preventDefault();
        loadProfitReport();
    });

    // Function to load profit report with filters
    function loadProfitReport() {
        // Get filter values
        const itemCode = $('#code').val();
        const itemName = $('#name').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const companyId = $('#company').val();
        const brandId = $('#brand').val();
        const departmentId = $('#department_id').val();
        const customerId = $('#customer_id').val();
        const allCustomers = $('#all_customers').is(':checked');

        // Show loading state
        const table = $('#profitReport').DataTable({
            processing: true,
            serverSide: true,
            destroy: true, // Destroy any existing table
            ajax: {
                url: 'ajax/php/report.php',
                type: 'POST',
                data: {
                    action: 'load_profit_report',
                    item_code: itemCode,
                    item_name: itemName,
                    from_date: fromDate,
                    to_date: toDate,
                    company_id: companyId,
                    brand_id: brandId,
                    department_id: departmentId,
                    customer_id: customerId,
                    all_customers: allCustomers
                },
                dataSrc: function(json) {
                    return json.sales_data || [];
                }
            },
            rowCallback: function (row, data, index) {
                if (
                    data.sales_type === "CREDIT" &&
                    parseFloat(data.outstanding_settle_amount) > 0
                ) {
                    $(row).addClass('bg-warning text-white');
                }
            },
            columns: [
                { data: 'id' },
                { data: 'invoice_no' },
                { data: 'invoice_date' },
                { data: 'company_name' },
                { data: 'customer_name' },
                { data: 'department_name' }, // fix mismatch (was department)
                { data: 'sales_type' },
                { 
                    data: 'cost',
                    render: function(data) {
                        return parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                },
                { 
                    data: 'selling_price',
                    render: function(data) {
                        return parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                },
                { 
                    data: 'profit',
                    render: function(data) {
                        return parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                },
                { data: 'outstanding_settle_amount', visible: false } // hidden column
            ],

            order: [[1, 'desc']], // Sort by invoice date by default
            pageLength: 25,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            initComplete: function() {
                // Update the date range display
                const fromDate = $('#from_date').val() || 'Start Date';
                const toDate = $('#to_date').val() || 'End Date';
                $('#profitReportDateRange').html(`<h5>Showing profit report for item: ${$('#name').val()} (${$('#code').val()}) | Date Range: ${fromDate} to ${toDate}</h5>`);
            }
        });
    }
    //loard Price Control
    $('#brand_id, #category_id, #group_id,#department_id').on('change', function () {
        loadPriceControlItems();
    });

    //loard price item vise 
    $('#item_code').on('keyup', function () {
        loadPriceControlItems();
    });

    // Initial load on page ready for Price Control page
    if ($('#priceControl').length) {
        loadPriceControlItems();
    }

    function loadPriceControlItems() {
        let brand_id = $('#brand_id').val();
        let category_id = $('#category_id').val();
        let group_id = $('#group_id').val();
        let department_id = $('#department_id').val();
        let item_code = $('#item_code').val().trim();

        $.ajax({
            url: 'ajax/php/report.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'loard_price_Control',
                brand_id: brand_id,
                category_id: category_id,
                group_id: group_id,
                department_id: department_id,
                item_code: item_code
            },
            beforeSend: function () {
                try {
                    var $target = $(".someBlock");
                    if ($target.length && typeof $target.preloader === 'function') {
                        $target.preloader();
                    } else {
                        var $wrap = $('#priceControl').closest('.table-responsive');
                        if ($wrap.length && typeof $wrap.preloader === 'function') {
                            $wrap.preloader();
                        }
                    }
                } catch (e) { /* noop */ }
            },
            success: function (data) {
                let tbody = '';
                if (data.length > 0) {
                    $.each(data, function (index, item) {
                        index++;
                        tbody += `<tr class="table-primary">
                        <td>${index}</td>
                        <td>${item.code} - ${item.name}</td> 
                        <td>${item.total_available_qty}</td>
                        <td>${item.discount || '0'}</td>
                        <td class="editable-price" data-item-id="${item.id}" data-original-price="${item.list_price}">
                            <strong class="text-danger price-display">
                                ${Number(item.list_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </strong>
                            <input type="number" step="0.01" class="form-control form-control-sm price-edit d-none" 
                                   value="${Number(item.list_price).toFixed(2)}" 
                                   data-item-id="${item.id}" />
                        </td>
                        <td>${item.brand}</td>
                        <td>${item.category}</td>
                        <td>
                            ${item.is_active == 1
                                ? '<span class="badge bg-soft-success font-size-12">Active</span>'
                                : '<span class="badge bg-soft-danger font-size-12">InActive</span>'}
                        </td>
                    </tr>`;

                        if (Array.isArray(item.stock_tmp) && item.stock_tmp.length > 0) {
                            $.each(item.stock_tmp, function (i, row) {
                                tbody += `
                                <tr class="table-info">
                                    <td colspan="2">
                                        <div><strong>ARN:</strong> ${row.arn_no}</div>
                                         
                                    </td>
                        
                                    <td>
                                        <div><strong>Department:</strong></div>
                                        <div>${row.department}</div>
                                    </td>
                        
                                    <td colspan="2">
                                        <div><strong>Available Qty:</strong> ${row.qty}</div>
                                    </td>
                        
                                    <td>
                                    <div class="d-flex align-items-center gap-2">
    <label for="cost-${row.id}" class="form-label text-danger mb-0" style="white-space: nowrap;">
        <strong>  Item Cost:</strong>
    </label>
    <input type="text" id="cost-${row.id}" step="0.01" 
           class="form-control form-control-sm  cost-input text-end" 
           data-id="${row.id}" value="${parseFloat(row.cost).toFixed(2)}" 
             />
</div>

                                    </td>
                        
                                    <td colspan="2">${row.created_at} </td>
                                </tr>`;
                            });
                        }

                    });
                } else {
                    tbody = `<tr><td colspan="10" class="text-center text-muted">No items found</td></tr>`;
                }
                $('#priceControl tbody').html(tbody);
            },
            error: function (xhr, status, error) {
                console.error('Error loading items:', error);
                $('#priceControl tbody').html(`<tr><td colspan="9" class="text-danger text-center">Error loading data</td></tr>`);
            },
            complete: function () {
                try {
                    var $target = $(".someBlock");
                    if ($target.length && typeof $target.preloader === 'function') {
                        $target.preloader('remove');
                    }
                    var $wrap = $('#priceControl').closest('.table-responsive');
                    if ($wrap.length && typeof $wrap.preloader === 'function') {
                        $wrap.preloader('remove');
                    }
                } catch (e) { /* noop */ }
            }
        });
    }

    // Event delegation for dynamically created inputs
    $('#priceControl tbody').on('change', '.cost-input, .cash-price-input, .credit-price-input, .cash-discount-input, .credit-discount-input', function () {
        let input = $(this);
        let id = input.data('id'); // stock_tmp record ID
        let field = '';

        if (input.hasClass('cost-input')) field = 'cost';
        else if (input.hasClass('cash-price-input')) field = 'cash_price';
        else if (input.hasClass('credit-price-input')) field = 'credit_price';
        else if (input.hasClass('cash-discount-input')) field = 'cash_dis';
        else if (input.hasClass('credit-discount-input')) field = 'credit_dis';

        let value = input.val();

        // Basic validation: non-empty and numeric
        if (value === '' || isNaN(value)) {
            alert('Please enter a valid number');
            return;
        }

        // Preloader start (optional if you use preloader plugin)
        $(".someBlock").preloader();

        $.ajax({
            url: 'ajax/php/report.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_stock_tmp_price',
                id: id,
                field: field,
                value: value
            },
            success: function (response) {

                // Remove preloader
                $(".someBlock").preloader("remove");

                if (response.success) {
                    swal({
                        title: "Success!",
                        text: "Price Updated are successfully.!",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                } else if (response.error) {
                    swal({
                        title: "Error!",
                        text: "Price update error.",
                        type: "error",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            },
            error: function () {
                alert('Error while updating data.');
            }
        });
    });

    // Add this new event handler for double-click on price
    $('#priceControl').on('dblclick', '.editable-price', function () {
        const container = $(this);
        const display = container.find('.price-display');
        const input = container.find('.price-edit');

        display.addClass('d-none');
        input.removeClass('d-none').focus().select();
    });

    // Add event handler for price input blur/save
    $('#priceControl').on('blur', '.price-edit', function () {
        const input = $(this);
        const container = input.closest('.editable-price');
        const display = container.find('.price-display');
        const itemId = input.data('item-id');
        const newPrice = parseFloat(input.val()).toFixed(2);

        // Validate price
        if (isNaN(newPrice) || newPrice < 0) {
            alert('Please enter a valid price');
            input.val(container.data('original-price'));
        } else {
            // Update display
            display.text(Number(newPrice).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            // Make AJAX call to update the price
            $.ajax({
                url: 'ajax/php/report.php',
                type: 'POST',
                data: {
                    action: 'update_item_price',
                    item_id: itemId,
                    new_price: newPrice
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        container.data('original-price', newPrice);
                        // Refresh the item master table if it exists
                        if (typeof table !== 'undefined' && $.fn.DataTable.isDataTable('#datatable')) {
                            table.ajax.reload(null, false);
                        }

                        // Show success message
                        swal({
                            title: "Success!",
                            text: "Price updated successfully!",
                            type: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        // Revert if update failed
                        swal({
                            title: "Error!",
                            text: response.message || "Failed to update price",
                            type: "error",
                            timer: 2000,
                            showConfirmButton: false
                        });
                        input.val(container.data('original-price'));
                    }
                },
                error: function () {
                    swal({
                        title: "Error!",
                        text: "An error occurred while updating the price",
                        type: "error",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    input.val(container.data('original-price'));
                }
            });
        }

        // Reset UI
        input.addClass('d-none');
        display.removeClass('d-none');
    });

    // Handle Enter key to save on press
    $('#priceControl').on('keypress', '.price-edit', function (e) {
        if (e.which === 13) { // Enter key
            $(this).blur();
        }
    });

    // Handle Escape key to cancel edit
    $('#priceControl').on('keydown', '.price-edit', function (e) {
        if (e.key === 'Escape') {
            const container = $(this).closest('.editable-price');
            container.find('.price-edit').addClass('d-none');
            container.find('.price-display').removeClass('d-none');
        }
    });

    //profit report
    $('#view_profit_report').on('click', function (e) {
        e.preventDefault();

        loadProfitReport();
    });

    //Reload on filter change
    $('#brand_id, #department_id, #group_id, #category_id, #filter_type').on('change', function () {
        loadProfitReport();
    });

    //Reload on typing item code
    $('#item_code').on('keyup', function () {
        loadProfitReport();
    });

    //Main function
    function loadProfitReport() {

        let brand_id = $('#brand_id').val();
        let department_id = $('#department_id').val();
        let item_code = $('#item_id').val().trim();
        let from_date = $('#from_date').val();
        let to_date = $('#to_date').val();
        let filter_type = $('#filter_type').val(); // 1: summary, 2: detail, 3: brand wise

        $.ajax({
            url: 'ajax/php/report.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'load_profit_report',
                brand_id,
                department_id,
                item_code,
                from_date,
                to_date,
                filter_type
            },
            success: function (response) {
                let tbody = '';
                let totalFinalCost = 0;
                let totalGrandTotal = 0;
                let totalProfit = 0;

                // Handle the new response structure
                const data = response.sales_data || response; // Fallback for backward compatibility
                const totalExpenses = parseFloat(response.total_expenses) || 0;
                const totalDailyIncome = parseFloat(response.total_daily_income) || 0;
                const totalReturns = parseFloat(response.total_returns) || 0;

                if (data.length > 0) {
                    $.each(data, function (index, row) {
                        index++;
                        const finalCost = parseFloat(row.final_cost);
                        const grandTotal = parseFloat(row.grand_total);
                        const profit = grandTotal - finalCost;
                        totalFinalCost += finalCost;
                        totalGrandTotal += grandTotal;
                        totalProfit += profit;

                        let rowClass = '';
                        if (
                            row.sales_type === "CREDIT" &&
                            parseFloat(row.outstanding_settle_amount) > 0
                        ) {
                            rowClass = 'bg-warning text-dark';
                        }

                        tbody += `<tr class="invoice-row ${rowClass}" data-id="${row.id}">
                            <td>${index}</td>
                            <td style="display: none;">${row.id}</td>
                            <td>${row.invoice_no}</td>
                            <td>${row.invoice_date}</td>
                            <td>${row.company_name}</td>
                            <td>${row.customer_name}</td>
                            <td>${row.department_name}</td>
                            <td style="color: ${row.sales_type === 'CASH' ? 'green' : row.sales_type === 'CREDIT' ? 'blue' : 'black'};">
                                ${row.sales_type}
                            </td>
                            <td>${row.item_name}</td>
                            <td>${finalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td>${grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td><strong style="color: red;">${profit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></td>
                        </tr>`;
                    });

                    // Calculate final profit after expenses (Total Sales Profit - Total Expenses)
                    const finalProfit = totalProfit - totalExpenses - totalReturns + totalDailyIncome;

                    // Add summary rows
                    tbody += `<tr style="font-weight:bold; background-color:#f8f9fa; border-top: 2px solid #dee2e6;">
                        <td colspan="8" class="text-end">Total Sales Profit</td>
                        <td>${totalFinalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>${totalGrandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td style="color: #28a745;">
                            ${totalProfit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                    </tr>`;
                                // Add return row
                                tbody += `<tr class="return-total-row" style="font-weight:bold; background-color:#f8d7da; border: 1px solid #f5c6cb; cursor: pointer;" onmouseover="this.style.backgroundColor='#f5c6cb'" onmouseout="this.style.backgroundColor='#f8d7da'">
                        <td colspan="10" class="text-end">Total Return Value</td>
                        <td style="color: #721c24;">
                            (${totalReturns.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })})
                        </td>
                    </tr>`;

                    // Add expense row showing in both Profit and Selling columns
                    tbody += `<tr style="font-weight:bold; background-color:#fff3cd; border: 1px solid #ffeaa7;">
                        <td colspan="9" class="text-end">Total Expenses</td>
                        <td style="color: #e74c3c;">
                            (${totalExpenses.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })})
                        </td>
                        <td style="color: #e74c3c;">
                            (${totalExpenses.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })})
                        </td>
                    </tr>`;

                    // Add daily income row showing in both Profit and Selling columns
                    tbody += `<tr style="font-weight:bold; background-color:#d4edda; border: 1px solid #c3e6cb;">
                        <td colspan="9" class="text-end">Total Daily Income</td>
                        <td style="color: #155724;">
                            ${totalDailyIncome.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                        <td style="color: #155724;">
                            ${totalDailyIncome.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                    </tr>`;

                    // Add Total Sales Profit after Expenses row - under Selling column
                    const salesProfitAfterExpenses = totalGrandTotal - totalExpenses + totalDailyIncome;
                    tbody += `<tr style="font-weight:bold; background-color:#e9ecef; border: 1px solid #adb5bd;">
                        <td colspan="9" class="text-end">Total Sales Profit after Expenses</td>
                        <td style="color: ${salesProfitAfterExpenses >= 0 ? '#28a745' : '#dc3545'}; font-size: 1.1em;">
                            ${salesProfitAfterExpenses.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                        <td></td>
                    </tr>`;

                    // Add final profit row - show profit-expense calculation under profit column
                    tbody += `<tr style="font-weight:bold; background-color:#d1ecf1; border: 2px solid #bee5eb;">
                        <td colspan="10" class="text-end">Final Profit (After Expenses & Returns)</td>
                        <td style="color: ${finalProfit >= 0 ? '#155724' : '#721c24'}; font-size: 1.1em;">
                            ${finalProfit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                    </tr>`;

                } else {
                    tbody = `<tr><td colspan="11" class="text-center text-muted">No profit data found</td></tr>`;
                }

                $('#profitReport tbody').html(tbody);

                // Show date range above the table
                let fromDate = $('#from_date').val();
                let toDate = $('#to_date').val();
                if (fromDate && toDate) {
                    $('#profitReportDateRange').html(`
                        <h6>Profit Report from <strong>${fromDate}</strong> to <strong>${toDate}</strong></h6>
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading profit report:', error);
                $('#profitReport tbody').html(`<tr><td colspan="11" class="text-danger text-center">Error loading profit report</td></tr>`);
            }
        });
    }

    $('#profitReport tbody').on('click', '.invoice-row', function () {
        let invoiceId = $(this).data('id');
        if (invoiceId) {
            window.location.href = `sales-invoice-view.php?invoice_id=${invoiceId}`;
        }
    });

    $('#profitReport tbody').on('click', '.return-total-row', function () {
        let fromDate = $('#from_date').val();
        let toDate = $('#to_date').val();
        let url = `return-items-report.php`;
        if (fromDate && toDate) {
            url += `?from_date=${encodeURIComponent(fromDate)}&to_date=${encodeURIComponent(toDate)}`;
        }
        window.location.href = url;
    });

});