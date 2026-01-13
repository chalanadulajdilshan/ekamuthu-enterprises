jQuery(document).ready(function ($) {
    
    // Load branches when bank is selected (for deposit)
    $('#deposit-bank').change(function () {
        var bankId = $(this).val();
        var branchSelect = $('#deposit-branch');
        
        branchSelect.html('<option value="">Loading...</option>');
        
        if (bankId) {
            $.ajax({
                url: 'ajax/php/cashbook.php',
                type: 'POST',
                data: {
                    get_branches: true,
                    bank_id: bankId
                },
                dataType: 'JSON',
                success: function (response) {
                    if (response.status === 'success') {
                        branchSelect.html('<option value="">Select Branch</option>');
                        response.branches.forEach(function (branch) {
                            branchSelect.append('<option value="' + branch.id + '">' + branch.name + '</option>');
                        });
                    } else {
                        branchSelect.html('<option value="">No branches found</option>');
                    }
                },
                error: function () {
                    branchSelect.html('<option value="">Error loading branches</option>');
                }
            });
        } else {
            branchSelect.html('<option value="">Select Branch</option>');
        }
    });
    
    // Save Bank Deposit
    $('#save-deposit').click(function (e) {
        e.preventDefault();
        
        var refNo = $('#deposit-ref-no').val();
        var bankId = $('#deposit-bank').val();
        var branchId = $('#deposit-branch').val();
        var amount = parseFloat($('#deposit-amount').val());
        var remark = $('#deposit-remark').val();
        
        // Validation
        if (!bankId) {
            swal({
                title: "Error!",
                text: "Please select a bank",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        if (!branchId) {
            swal({
                title: "Error!",
                text: "Please select a branch",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        if (!amount || amount <= 0) {
            swal({
                title: "Error!",
                text: "Please enter a valid amount",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        // Get current balance and validate
        var currentBalance = parseFloat($('#current-balance-deposit').text().replace(/,/g, ''));
        if (amount > currentBalance) {
            swal({
                title: "Error!",
                text: "Deposit amount cannot exceed current balance of " + currentBalance.toFixed(2),
                type: 'error',
                timer: 3000,
                showConfirmButton: false
            });
            return;
        }
        
        $('.someBlock').preloader();
        
        $.ajax({
            url: 'ajax/php/cashbook.php',
            type: 'POST',
            data: {
                create_deposit: true,
                ref_no: refNo,
                bank_id: bankId,
                branch_id: branchId,
                amount: amount,
                remark: remark
            },
            dataType: 'JSON',
            success: function (response) {
                $('.someBlock').preloader('remove');
                
                if (response.status === 'success') {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Reset form and close modal
                    $('#deposit-form')[0].reset();
                    $('#depositModal').modal('hide');
                    
                    // Reload page after 2 seconds
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal({
                        title: "Error!",
                        text: response.message,
                        type: 'error',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            },
            error: function () {
                $('.someBlock').preloader('remove');
                swal({
                    title: "Error!",
                    text: "Something went wrong. Please try again.",
                    type: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });
    
    // Save Bank Withdrawal
    $('#save-withdrawal').click(function (e) {
        e.preventDefault();
        
        var refNo = $('#withdrawal-ref-no').val();
        var amount = parseFloat($('#withdrawal-amount').val());
        var remark = $('#withdrawal-remark').val();
        
        // Validation
        if (!amount || amount <= 0) {
            swal({
                title: "Error!",
                text: "Please enter a valid amount",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        // Get current balance and ensure withdrawal does not exceed it
        var currentBalanceWithdrawal = parseFloat($('#current-balance-withdrawal').text().replace(/,/g, '')) || 0;
        if (amount > currentBalanceWithdrawal) {
            swal({
                title: "Error!",
                text: "Withdrawal amount cannot exceed current balance of " + currentBalanceWithdrawal.toFixed(2),
                type: 'error',
                timer: 3000,
                showConfirmButton: false
            });
            return;
        }
        
        $('.someBlock').preloader();
        
        $.ajax({
            url: 'ajax/php/cashbook.php',
            type: 'POST',
            data: {
                create_withdrawal: true,
                ref_no: refNo,
                amount: amount,
                remark: remark
            },
            dataType: 'JSON',
            success: function (response) {
                $('.someBlock').preloader('remove');
                
                if (response.status === 'success') {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Reset form and close modal
                    $('#withdrawal-form')[0].reset();
                    $('#withdrawalModal').modal('hide');
                    
                    // Reload page after 2 seconds
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal({
                        title: "Error!",
                        text: response.message,
                        type: 'error',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            },
            error: function () {
                $('.someBlock').preloader('remove');
                swal({
                    title: "Error!",
                    text: "Something went wrong. Please try again.",
                    type: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });
    
    // Refresh page
    $('#btn-refresh').on('click', function(e) {
        e.preventDefault();
        location.reload();
    });

    // Filter by specific date
    $('#btn-filter').on('click', function(e) {
        e.preventDefault();
        const date = $('#date').val();

        if (!date) {
            swal({
                title: "Error!",
                text: "Please select a date",
                icon: "error",
                button: "OK",
            });
            return;
        }

        // Reload page with single date parameter
        window.location.href = `cashbook.php?date=${date}`;
    });


    // Reset filter
    $('#btn-reset-filter').on('click', function(e) {
        e.preventDefault();
        window.location.href = 'cashbook.php';
    });
    
    // Update ref number and current balance when bank modals are opened
    $('#depositModal, #withdrawalModal').on('show.bs.modal', function () {
        // Get next reference number
        $.ajax({
            url: 'ajax/php/cashbook.php',
            type: 'POST',
            data: { get_ref_no: true },
            dataType: 'JSON',
            success: function (response) {
                if (response.status === 'success') {
                    $('#deposit-ref-no, #withdrawal-ref-no').val(response.ref_no);
                }
            }
        });

        // Update current balance display (for both deposit and withdrawal)
        $.ajax({
            url: 'ajax/php/cashbook.php',
            type: 'POST',
            data: { get_balance: true },
            dataType: 'JSON',
            success: function (response) {
                if (response.status === 'success') {
                    $('#current-balance-deposit, #current-balance-withdrawal').text(response.balance);
                }
            }
        });
    });
    
    // Reset form when modal is closed
    $('#depositModal').on('hidden.bs.modal', function () {
        $('#deposit-form')[0].reset();
        $('#deposit-branch').html('<option value="">Select Branch</option>');
    });
    
    $('#withdrawalModal').on('hidden.bs.modal', function () {
        $('#withdrawal-form')[0].reset();
    });
    
});
