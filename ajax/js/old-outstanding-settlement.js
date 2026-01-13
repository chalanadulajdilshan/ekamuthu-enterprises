/**
 * Old Outstanding Settlement JS
 * Handles customer selection and settlement functionality
 */

$(document).ready(function () {
    // Initialize DataTable for customer selection
    if ($.fn.DataTable.isDataTable('#oldOutstandingCustomerTable')) {
        $('#oldOutstandingCustomerTable').DataTable().destroy();
    }

    const customerTable = $('#oldOutstandingCustomerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ajax/php/customer-master.php',
            type: 'POST',
            data: function (d) {
                d.filter = true;
                d.category = '1'; // Filter for category = 1 only
                d.status = 'active'; // Keep the active status filter
                d.old_outstanding_only = true; // Add filter for old outstanding > 0
            },
            dataSrc: function (json) {
                console.log('Server response:', json);
                if (json && json.data) {
                    return json.data;
                }
                return [];
            },
            error: function (xhr, error, thrown) {
                console.error('DataTables error:', error);
                $('#oldOutstandingCustomerTable tbody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data</td></tr>');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'code' },
            { data: 'name' },
            { data: 'mobile_number' },
            { data: 'email' },
            { data: 'category' },
            { data: 'credit_limit' },
            { data: 'old_outstanding' },   // Old Outstanding amount for this page
            {
                data: 'is_vat',
                render: function(data) {
                    return (data === 1 || data === '1') ? 'Yes' : 'No';
                }
            },
            {
                data: 'status_label',
                orderable: false
            }
        ],
        order: [[0, "desc"]],
        pageLength: 100,
        responsive: true,
        createdRow: function(row, data, index) {
            $('td:eq(5)', row).removeClass('text-danger');
        },
        serverParams: function (data) {
            data.start = data.start || 0;
            data.length = data.length || 100;
            if (data.search && data.search.value) {
                data.search = data.search.value;
            }
        },
        error: function (xhr, error, thrown) {
            console.error('DataTables error:', error);
            console.error('Server response:', xhr.responseText);
            alert('An error occurred while loading the data. Please check the console for details.');
        }
    });

    // Handle customer selection from the modal
    $('#oldOutstandingCustomerTable tbody').on('click', 'tr', function () {
        const data = customerTable.row(this).data();
        if (data) {
            $('#customer_id').val(data.id);
            $('#customer_code').val(data.code);
            $('#oldOutstandingCustomerModal').modal('hide');
            // Don't auto-load here, user needs to click View button
        }
    });

    // View button click handler
    $('#viewBtn').on('click', function () {
        loadSettlementData();
    });

    // Reset button click handler
    $('#resetBtn').on('click', function () {
        $('#settlementForm')[0].reset();
        $('#customer_id').val('');
        $('#customer_code').val('');
        $('#settlementContainer').html(`
            <div class="text-muted text-center py-5">
                <i class="uil uil-invoice display-4"></i>
                <p class="mt-2">Select a customer to view old outstanding amount</p>
            </div>
        `);
    });

    // Load settlement data via AJAX
    function loadSettlementData() {
        const customerId = $('#customer_id').val();
        const customerCode = $('#customer_code').val();

        if (!customerId) {
            Swal.fire({
                icon: 'warning',
                title: 'Customer Required',
                text: 'Please select a customer first',
                confirmButtonColor: '#3b5de7',
            });
            return;
        }

        console.log('Loading settlement data for customer:', customerId);

        $.ajax({
            url: 'ajax/php/old-outstanding-settlement.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_old_outstanding',
                customer_id: customerId
            },
            beforeSend: function () {
                $('#settlementContainer').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading old outstanding data...</p>
                    </div>
                `);
            },
            success: function (response) {
                console.log('Settlement data response:', response);
                if (response && response.status === 'success') {
                    renderSettlementForm(response.data, customerCode);
                } else {
                    const errorMsg = response && response.message ? response.message : 'Error loading data';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg,
                        confirmButtonColor: '#3b5de7',
                    });
                    $('#settlementContainer').html(`
                        <div class="text-muted text-center py-5">
                            <i class="uil uil-exclamation-triangle display-4"></i>
                            <p class="mt-2">Error loading data</p>
                        </div>
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error loading data. Please check console for details.',
                    confirmButtonColor: '#3b5de7',
                });
                $('#settlementContainer').html(`
                    <div class="text-muted text-center py-5">
                        <i class="uil uil-exclamation-triangle display-4"></i>
                        <p class="mt-2">Error loading data</p>
                    </div>
                `);
            }
        });
    }

    // Render settlement form
    function renderSettlementForm(data, customerCode) {
        if (!data || data.length === 0) {
            $('#settlementContainer').html(`
                <div class="text-muted text-center py-5">
                    <i class="uil uil-check-circle display-4"></i>
                    <p class="mt-2">No old outstanding amount found for this customer</p>
                </div>
            `);
            return;
        }

        const customer = data[0];
        const oldOutstanding = parseFloat(customer.old_outstanding || 0);

        const formHtml = `
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Settlement Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Customer Code</label>
                                    <input type="text" class="form-control" value="${customerCode}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" value="${customer.name}${customer.name_2 ? ' ' + customer.name_2 : ''}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Old Outstanding Amount</label>
                                    <input type="text" class="form-control old-outstanding-highlight" value="${oldOutstanding.toFixed(2)}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Settlement Amount</label>
                                    <input type="number" id="settlementAmount" class="form-control" step="0.01" min="0" max="${oldOutstanding}" placeholder="Enter settlement amount">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <textarea id="settlementRemarks" class="form-control" rows="3" placeholder="Enter settlement remarks"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="button" id="settleBtn" class="btn btn-success">
                                        <i class="mdi mdi-check-circle me-1"></i> Settle Amount
                                    </button>
                                    <button type="button" id="clearBtn" class="btn btn-warning ms-2">
                                        <i class="mdi mdi-refresh me-1"></i> Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Settlement Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="summary-box mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="summary-label">Total Old Outstanding:</span>
                                    <span class="text-danger fw-bold">${oldOutstanding.toFixed(2)}</span>
                                </div>
                            </div>
                            <div class="summary-box">
                                <div class="d-flex justify-content-between">
                                    <span class="summary-label">Remaining After Settlement:</span>
                                    <span id="remainingAmount" class="text-primary fw-bold">${oldOutstanding.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#settlementContainer').html(formHtml);

        // Update remaining amount when settlement amount changes
        $('#settlementAmount').on('input', function() {
            const settlementAmount = parseFloat($(this).val()) || 0;
            const remaining = Math.max(0, oldOutstanding - settlementAmount);
            $('#remainingAmount').text(remaining.toFixed(2));
        });

        // Handle settle button click
        $('#settleBtn').on('click', function() {
            const settlementAmount = parseFloat($('#settlementAmount').val()) || 0;
            const remarks = $('#settlementRemarks').val().trim();

            if (settlementAmount <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Amount',
                    text: 'Please enter a valid settlement amount',
                    confirmButtonColor: '#3b5de7',
                });
                return;
            }

            if (settlementAmount > oldOutstanding) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Amount',
                    text: 'Settlement amount cannot exceed old outstanding amount',
                    confirmButtonColor: '#3b5de7',
                });
                return;
            }

            Swal.fire({
                title: 'Confirm Settlement',
                text: `Are you sure you want to settle ${settlementAmount.toFixed(2)} from old outstanding?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Settle',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performSettlement(customer.id, settlementAmount, remarks);
                }
            });
        });

        // Handle clear button click
        $('#clearBtn').on('click', function() {
            Swal.fire({
                title: 'Clear All Old Outstanding',
                text: 'Are you sure you want to clear all old outstanding amount for this customer?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Clear All',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performSettlement(customer.id, oldOutstanding, 'Cleared all old outstanding');
                }
            });
        });
    }

    // Perform settlement
    function performSettlement(customerId, amount, remarks) {
        $.ajax({
            url: 'ajax/php/old-outstanding-settlement.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'settle_old_outstanding',
                customer_id: customerId,
                amount: amount,
                remarks: remarks
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process the settlement',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function (response) {
                console.log('Settlement response:', response);
                if (response && response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Settlement Successful',
                        text: response.message || 'Old outstanding amount has been settled successfully',
                        confirmButtonColor: '#28a745',
                    }).then(() => {
                        // Reload the settlement data
                        loadSettlementData();
                    });
                } else {
                    const errorMsg = response && response.message ? response.message : 'Error processing settlement';
                    Swal.fire({
                        icon: 'error',
                        title: 'Settlement Failed',
                        text: errorMsg,
                        confirmButtonColor: '#3b5de7',
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('Settlement AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error processing settlement. Please check console for details.',
                    confirmButtonColor: '#3b5de7',
                });
            }
        });
    }
});
