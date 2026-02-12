$(document).ready(function () {
    
    // Initialize Datepicker
    $(".date-picker").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    
    // Select customer from modal
    $(document).on('click', '#customerTable tbody tr', function () {
        const table = $('#customerTable').DataTable();
        const data = table.row(this).data();
        if (!data) return;
        $('#customer_id').val(data.id);
        $('#customer_code').val(data.name + ' (' + data.code + ')');
        $('#customerModal').modal('hide');
        loadOutstandingItems(data.id);
    });

    // Payment Method change -> Show/Hide fields
    $('#paymentMethod').change(function() {
        const method = $(this).val();
        
        // Hide all specific sections first
        $('#bankDetails').hide();
        $('#chequeDetails').hide();
        $('#transferDetails').hide();
        
        // 1=Cash, 2=Cheque, 3=Transfer
        if (method == '2') { // Cheque
            $('#bankDetails').show();
            $('#chequeDetails').show();
        } else if (method == '3') { // Transfer
            $('#bankDetails').show();
            $('#transferDetails').show();
        }
    });

    // Load Branches when Bank changes
    $('#bank_id').change(function() {
        const bankId = $(this).val();
        const branchSelect = $('#branch_id');
        
        branchSelect.html('<option value="">Loading...</option>').prop('disabled', true);
        
        if (bankId) {
            $.ajax({
                url: 'ajax/php/outstanding-payment.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_branches',
                    bank_id: bankId
                },
                success: function(response) {
                    let html = '<option value="">Select Branch</option>';
                    if (response.status === 'success') {
                        response.branches.forEach(branch => {
                            html += `<option value="${branch.id}">${branch.name} (${branch.code})</option>`;
                        });
                        branchSelect.html(html).prop('disabled', false);
                    } else {
                        branchSelect.html('<option value="">Error loading branches</option>');
                    }
                },
                error: function() {
                    branchSelect.html('<option value="">Error loading branches</option>');
                }
            });
        } else {
            branchSelect.html('<option value="">Select Bank First</option>').prop('disabled', true);
        }
    });

    // Refresh button
    $('#refreshBtn').click(function() {
        const customerId = $('#customer_id').val();
         if (customerId) {
            loadOutstandingItems(customerId);
        } else {
            Swal.fire('Please select a customer first');
        }
    });

    // Load Outstanding Items
    function loadOutstandingItems(customerId) {
        $('#outstandingTableBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');
        $('#payBtn').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/php/outstanding-payment.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_outstanding_rents',
                customer_id: customerId
            },
            success: function (response) {
                if (response.status === 'success') {
                    renderTable(response.items);
                    $('#totalOutstandingDisplay').text(formatCurrency(response.total_outstanding));
                } else {
                    $('#outstandingTableBody').html(`<tr><td colspan="6" class="text-center text-danger">${response.message}</td></tr>`);
                }
            },
            error: function () {
                $('#outstandingTableBody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load data</td></tr>');
            }
        });
    }

    // Render Table
    function renderTable(items) {
        let html = '';
        if (items.length === 0) {
            html = '<tr><td colspan="6" class="text-center">No outstanding items found.</td></tr>';
        } else {
            items.forEach(item => {
                html += `
                    <tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input item-checkbox" type="checkbox" data-id="${item.return_id}" data-amount="${item.amount}">
                            </div>
                        </td>
                        <td>${item.date}</td>
                        <td>${item.bill_number}</td>
                        <td>${item.item_name}</td>
                        <td class="text-end">${formatCurrency(item.amount)}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-end payment-amount-input" 
                                value="${item.amount}" max="${item.amount}" step="0.01" disabled>
                        </td>
                    </tr>
                `;
            });
        }
        $('#outstandingTableBody').html(html);
        updateSelectedTotal();
    }

    // Checkbox Change
    $(document).on('change', '.item-checkbox', function() {
        const row = $(this).closest('tr');
        const input = row.find('.payment-amount-input');
        
        if ($(this).is(':checked')) {
            input.prop('disabled', false);
        } else {
            input.prop('disabled', true);
            // Reset value to max amount when unchecked
            input.val(input.attr('max')); 
        }
        updateSelectedTotal();
    });

    // Select All
    $('#selectAll').change(function() {
        const isChecked = $(this).is(':checked');
        $('.item-checkbox').prop('checked', isChecked).trigger('change');
    });

    // Amount Input Change
    $(document).on('input', '.payment-amount-input', function() {
        const max = parseFloat($(this).attr('max'));
        let val = parseFloat($(this).val());
        
        if (isNaN(val) || val < 0) val = 0;
        if (val > max) val = max; // Enforce max
        
        // Update total
        updateSelectedTotal();
    });

    // Update Total
    function updateSelectedTotal() {
        let total = 0;
        let count = 0;
        
        $('.item-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const input = row.find('.payment-amount-input');
            const amount = parseFloat(input.val()) || 0;
            total += amount;
            count++;
        });
        
        $('#selectedTotal').text(formatCurrency(total));
        $('#payBtn').prop('disabled', count === 0 || total <= 0);
    }

    // Process Payment
    $('#payBtn').click(function() {
        const customerId = $('#customer_id').val();
        const paymentDate = $('#paymentDate').val();
        
        // Collect Payment Details
        const paymentMethod = $('#paymentMethod').val();
        const bankId = $('#bank_id').val();
        const branchId = $('#branch_id').val();
        
        // Cheque Details
        const chequeDate = $('#chequeDate').val();
        const chequeNo = $('#chequeNo').val();
        
        // Transfer Details
        const transferDate = $('#transferDate').val();
        const accountNo = $('#accountNo').val();
        const refNo = $('#refNo').val();

        // Validation
        if (!customerId) {
            Swal.fire('Error', 'Please select a customer', 'error');
            return;
        }

        if (paymentMethod == '2') { // Cheque
            if (!bankId || !branchId || !chequeNo || !chequeDate) {
                Swal.fire('Error', 'Please fill in all Cheque details (Bank, Branch, Cheque No, Date)', 'error');
                return;
            }
        } else if (paymentMethod == '3') { // Transfer
            if (!bankId || !branchId || !accountNo || !transferDate) {
                Swal.fire('Error', 'Please fill in all Transfer details (Bank, Branch, Account No, Date)', 'error');
                return;
            }
        }

        let items = [];
        $('.item-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const input = row.find('.payment-amount-input');
            const id = $(this).data('id');
            const amount = parseFloat(input.val()) || 0;
            
            if (amount > 0) {
                items.push({ id: id, amount: amount });
            }
        });

        if (items.length === 0) return;

        const methodText = $("#paymentMethod option:selected").text();
        
        Swal.fire({
            title: 'Confirm Payment?',
            text: `You are about to pay ${$('#selectedTotal').text()} via ${methodText}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Pay Now',
            confirmButtonColor: '#34c38f',
            cancelButtonColor: '#f46a6a'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/php/outstanding-payment.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'save_rent_payment',
                        customer_id: customerId,
                        payment_date: paymentDate,
                        payment_method_id: paymentMethod, // 1, 2, 3
                        bank_id: bankId,
                        branch_id: branchId,
                        cheque_no: chequeNo,
                        ref_no: refNo,
                        cheque_date: chequeDate,
                        transfer_date: transferDate,
                        account_no: accountNo,
                        items: items
                    },
                    beforeSend: function() {
                        Swal.showLoading();
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Successful',
                                text: response.message
                            }).then(() => {
                                // Reset form fields
                                $('#paymentForm')[0].reset();
                                $('#paymentMethod').val('1').trigger('change');
                                loadOutstandingItems(customerId); // Refresh list
                                $('#selectedTotal').text('0.00');
                                $('#selectAll').prop('checked', false);
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to process payment', 'error');
                    }
                });
            }
        });
    });

    function formatCurrency(amount) {
        return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

});
