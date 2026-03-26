jQuery(document).ready(function () {

    // ========================
    // Conditional Section Logic
    // ========================

    // Trip Category change
    $('input[name="trip_category"]').on('change', function () {
        var category = $(this).val();
        if (category === 'customer') {
            $('#section-invoice-type').addClass('show');
            // Show pay amount in completion section
            $('#section-pay-amount').addClass('show');
        } else {
            $('#section-invoice-type').removeClass('show');
            $('#section-bill').removeClass('show');
            $('#section-customer-transport').removeClass('show');
            $('#section-pay-amount').removeClass('show');
            // Reset invoice type
            $('input[name="invoice_type"]').prop('checked', false);
            $('#bill_id').val('').trigger('change');
            $('#customer_id').val('').trigger('change');
        }
    });

    // Invoice Type change
    $('input[name="invoice_type"]').on('change', function () {
        var invoiceType = $(this).val();
        if (invoiceType === 'invoice') {
            $('#section-bill').addClass('show');
            $('#section-customer-transport').removeClass('show');
            // Load bills
            loadBills();
        } else if (invoiceType === 'non_invoice') {
            $('#section-customer-transport').addClass('show');
            $('#section-bill').removeClass('show');
            $('#bill_id').val('').trigger('change');
        }
    });

    // Vehicle change - auto-fill start meter
    $('#vehicle_id').on('change', function () {
        var vehicleId = $(this).val();
        if (vehicleId) {
            $.ajax({
                url: 'ajax/php/trip-management.php',
                type: 'POST',
                data: { get_vehicle_meter: true, vehicle_id: vehicleId },
                dataType: 'json',
                success: function (result) {
                    if (result.status === 'success') {
                        $('#start_meter').val(result.start_meter);
                    }
                }
            });
        } else {
            $('#start_meter').val('');
        }
    });

    // Bill selection - auto-load customer and transport
    $('#bill_id').on('change', function () {
        var billId = $(this).val();
        if (billId) {
            $.ajax({
                url: 'ajax/php/trip-management.php',
                type: 'POST',
                data: { get_bill_transport: true, bill_id: billId },
                dataType: 'json',
                success: function (result) {
                    if (result.status === 'success') {
                        // Set customer name
                        if (result.customer) {
                            $('#bill_customer_name').val(result.customer.code + ' - ' + result.customer.name);
                        }
                        // Show transport info
                        if (result.transports && result.transports.length > 0) {
                            var info = '<table class="table table-sm table-bordered mb-0">';
                            info += '<tr><th>From</th><th>To</th><th>Amount</th></tr>';
                            result.transports.forEach(function(t) {
                                info += '<tr><td>' + (t.start_location || '-') + '</td>';
                                info += '<td>' + (t.end_location || '-') + '</td>';
                                info += '<td>' + parseFloat(t.total_amount || 0).toFixed(2) + '</td></tr>';
                            });
                            info += '</table>';
                            $('#bill_transport_info').html(info);

                            // Auto-fill locations from first transport
                            if (result.transports[0]) {
                                $('#start_location').val(result.transports[0].start_location || '');
                                $('#end_location').val(result.transports[0].end_location || '');
                                var totalTransport = 0;
                                result.transports.forEach(function(t) {
                                    totalTransport += parseFloat(t.total_amount || 0);
                                });
                                $('#transport_amount').val(totalTransport.toFixed(2));
                            }
                        } else {
                            $('#bill_transport_info').html('<span class="text-muted">No transport details found for this bill</span>');
                        }

                        // Auto-fill pay_amount from bill's transport_cost
                        if (result.bill && result.bill.transport_cost) {
                            $('#pay_amount').val(parseFloat(result.bill.transport_cost).toFixed(2));
                            $('#pay_amount_completion').val(parseFloat(result.bill.transport_cost).toFixed(2));
                        }
                    }
                }
            });
        } else {
            $('#bill_customer_name').val('');
            $('#bill_transport_info').html('Select a bill to view transport details');
        }
    });

    // ========================
    // Auto-Calculate Total Cost
    // ========================
    function calculateTotalCost() {
        var toll = parseFloat($('#toll').val()) || 0;
        var helper = parseFloat($('#helper_payment').val()) || 0;
        var transport = parseFloat($('#transport_amount').val()) || 0;
        var transportCost = parseFloat($('#pay_amount_completion').val()) || 0;
        var total = toll + helper + transport + transportCost;
        $('#total_cost').val(total.toFixed(2));
    }

    // Bind auto-calculation to cost fields and sync pay amounts
    $('#toll, #helper_payment, #transport_amount, #pay_amount, #pay_amount_completion').on('input change', function () {
        if ($(this).attr('id') === 'pay_amount') {
            $('#pay_amount_completion').val($(this).val());
        } else if ($(this).attr('id') === 'pay_amount_completion') {
            $('#pay_amount').val($(this).val());
        }
        calculateTotalCost();
    });

    // ========================
    // Payment Method Change
    // ========================
    $('#payment_method').on('change', function () {
        if ($(this).val() === 'credit') {
            $('#section-settlement-btn').addClass('show');
        } else {
            $('#section-settlement-btn').removeClass('show');
        }
    });

    // ========================
    // Load Bills into dropdown
    // ========================
    function loadBills() {
        $.ajax({
            url: 'ajax/php/trip-management.php',
            type: 'POST',
            data: { get_bills: true },
            dataType: 'json',
            success: function (result) {
                if (result.status === 'success') {
                    var $select = $('#bill_id');
                    $select.empty();
                    $select.append('<option value="">-- Select Bill --</option>');
                    result.data.forEach(function (bill) {
                        $select.append('<option value="' + bill.id + '">' +
                            bill.bill_number + ' - ' + (bill.customer_code || '') + ' ' + (bill.customer_name || '') +
                            '</option>');
                    });
                    // Re-initialize select2 if available
                    if ($.fn.select2 && $select.hasClass('select2')) {
                        $select.select2();
                    }
                }
            }
        });
    }

    // ========================
    // Section Confirm Buttons
    // ========================

    // Confirm Bill Selection
    $('#submit-bill-section').click(function (e) {
        e.preventDefault();
        if (!$('#bill_id').val()) {
            swal({ title: 'Error!', text: 'Please select a bill number', type: 'error', timer: 2000, showConfirmButton: false });
            return false;
        }
        // Mark section as confirmed
        $(this).closest('.section-card').css('border-left-color', '#34c38f');
        $(this).removeClass('btn-primary').addClass('btn-success').html('<i class="uil uil-check-circle me-1"></i> Bill Confirmed');
        swal({ title: 'Confirmed!', text: 'Bill selection confirmed.', type: 'success', timer: 1500, showConfirmButton: false });
    });

    // Confirm Customer & Transport
    $('#submit-customer-section').click(function (e) {
        e.preventDefault();
        if (!$('#customer_id').val()) {
            swal({ title: 'Error!', text: 'Please select a customer', type: 'error', timer: 2000, showConfirmButton: false });
            return false;
        }
        // Mark section as confirmed
        $(this).closest('.section-card').css('border-left-color', '#34c38f');
        $(this).removeClass('btn-primary').addClass('btn-success').html('<i class="uil uil-check-circle me-1"></i> Customer Confirmed');
        swal({ title: 'Confirmed!', text: 'Customer & transport confirmed.', type: 'success', timer: 1500, showConfirmButton: false });
    });

    // ========================
    // Create Trip
    // ========================
    $(document).on('click', '#create', function (event) {
        event.preventDefault();

        var tripCategory = $('input[name="trip_category"]:checked').val();
        var vehicleId = $('#vehicle_id').val();
        var startMeter = $('#start_meter').val();

        // Validation
        if (!vehicleId) {
            swal({ title: 'Error!', text: 'Please select a vehicle', type: 'error', timer: 2000, showConfirmButton: false });
            return false;
        }
        if (!startMeter || startMeter === '') {
            swal({ title: 'Error!', text: 'Please enter start meter reading', type: 'error', timer: 2000, showConfirmButton: false });
            return false;
        }
        if (!$('#transport_date').val()) {
            swal({ title: 'Error!', text: 'Please select transport date', type: 'error', timer: 2000, showConfirmButton: false });
            return false;
        }

        if (tripCategory === 'customer') {
            var invoiceType = $('input[name="invoice_type"]:checked').val();
            if (!invoiceType) {
                swal({ title: 'Error!', text: 'Please select invoice type', type: 'error', timer: 2000, showConfirmButton: false });
                return false;
            }
            if (invoiceType === 'invoice' && !$('#bill_id').val()) {
                swal({ title: 'Error!', text: 'Please select a bill number', type: 'error', timer: 2000, showConfirmButton: false });
                return false;
            }
            if (invoiceType === 'non_invoice' && !$('#customer_id').val()) {
                swal({ title: 'Error!', text: 'Please select a customer', type: 'error', timer: 2000, showConfirmButton: false });
                return false;
            }
        }

        $('.someBlock').preloader();

        var formData = new FormData($('#form-data')[0]);
        formData.append('create', true);

        $.ajax({
            url: 'ajax/php/trip-management.php',
            type: 'POST',
            data: formData,
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                $('.someBlock').preloader('remove');

                if (result.status === 'success') {
                    swal({
                        title: 'Success!',
                        text: 'Trip saved successfully! Trip No: ' + result.trip_number,
                        type: 'success',
                        timer: 2500,
                        showConfirmButton: false
                    });

                    // Set the trip id to the form
                    $('#id').val(result.trip_id);
                    $('#trip_number').val(result.trip_number);

                    // Show trip details section & switch to update mode
                    $('#section-trip-details').addClass('show');
                    $('#create').hide();

                    // Show pay amount if customer trip
                    if ($('input[name="trip_category"]:checked').val() === 'customer') {
                        $('#section-pay-amount').addClass('show');
                    }
                } else {
                    swal({ title: 'Error!', text: result.message || 'Something went wrong.', type: 'error', showConfirmButton: true });
                }
            },
            error: function (xhr, status, error) {
                $('.someBlock').preloader('remove');
                swal({ title: 'Error!', text: 'Server error: ' + error, type: 'error', showConfirmButton: true });
            }
        });
        return false;
    });

    // ========================
    // Update Trip
    // ========================
    $(document).on('click', '#update', function (event) {
        event.preventDefault();

        var id = $('#id').val();
        if (!id || id === '0') {
            swal({ title: 'Error!', text: 'Please save or select a trip first.', type: 'error', timer: 2000, showConfirmButton: false });
            return false;
        }

        $('.someBlock').preloader();

        var formData = new FormData($('#form-data')[0]);
        formData.append('update', true);

        $.ajax({
            url: 'ajax/php/trip-management.php',
            type: 'POST',
            data: formData,
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                $('.someBlock').preloader('remove');

                if (result.status === 'success') {
                    swal({
                        title: 'Success!',
                        text: 'Trip updated successfully!',
                        type: 'success',
                        timer: 2500,
                        showConfirmButton: false
                    });

                    window.setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal({ title: 'Error!', text: result.message || 'Something went wrong.', type: 'error', showConfirmButton: true });
                }
            },
            error: function (xhr, status, error) {
                $('.someBlock').preloader('remove');
                swal({ title: 'Error!', text: 'Server error: ' + error, type: 'error', showConfirmButton: true });
            }
        });
        return false;
    });

    // ========================
    // Delete Trip
    // ========================
    $(document).on('click', '#delete-trip', function (e) {
        e.preventDefault();

        var id = $('#id').val();
        var tripNumber = $('#trip_number').val();

        if (!id || id === '0') {
            swal({ title: 'Error!', text: 'Please select a trip first.', type: 'error', timer: 2000, showConfirmButton: false });
            return;
        }

        swal({
            title: 'Are you sure?',
            text: "Do you want to delete trip '" + tripNumber + "'?",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $('.someBlock').preloader();

                $.ajax({
                    url: 'ajax/php/trip-management.php',
                    type: 'POST',
                    data: { id: id, delete: true },
                    dataType: 'json',
                    success: function (response) {
                        $('.someBlock').preloader('remove');

                        if (response.status === 'success') {
                            swal({
                                title: 'Deleted!',
                                text: 'Trip has been deleted.',
                                type: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            setTimeout(function () {
                                window.location.reload();
                            }, 2000);
                        } else {
                            swal({ title: 'Error!', text: 'Something went wrong.', type: 'error', timer: 2000, showConfirmButton: false });
                        }
                    }
                });
            }
        });
    });

    // ========================
    // New - Reset Form
    // ========================
    $('#new').click(function (e) {
        e.preventDefault();
        window.location.reload();
    });

    // ========================
    // Load Trips into Modal
    // ========================
    function loadTrips() {
        $.ajax({
            url: 'ajax/php/trip-management.php',
            type: 'POST',
            data: { fetch_trips: true },
            dataType: 'json',
            success: function (result) {
                if (result.status === 'success') {
                    var tbody = '';
                    result.data.forEach(function (trip) {
                        tbody += '<tr class="select-trip" ' +
                            'data-id="' + trip.id + '" ' +
                            'data-trip_number="' + trip.trip_number + '" ' +
                            'data-trip_category="' + trip.trip_category + '" ' +
                            'data-invoice_type="' + (trip.invoice_type || '') + '" ' +
                            'data-customer_name="' + trip.customer_name + '" ' +
                            'data-vehicle_no="' + trip.vehicle_no + '" ' +
                            'data-employee_name="' + trip.employee_name + '" ' +
                            'data-start_meter="' + trip.start_meter + '" ' +
                            'data-end_meter="' + (trip.end_meter || '') + '" ' +
                            'data-transport_date="' + (trip.transport_date || '') + '" ' +
                            'data-status="' + trip.status + '"' +
                            '>';
                        tbody += '<td>' + trip.key + '</td>';
                        tbody += '<td>' + (trip.transport_date || '-') + '</td>';
                        tbody += '<td>' + trip.trip_number + '</td>';
                        tbody += '<td>' + trip.category_label + '</td>';
                        tbody += '<td>' + trip.customer_name + '</td>';
                        tbody += '<td>' + trip.vehicle_no + '</td>';
                        tbody += '<td>' + trip.employee_name + '</td>';
                        tbody += '<td>' + parseFloat(trip.start_meter).toFixed(2) + '</td>';
                        tbody += '<td>' + (trip.end_meter !== '-' ? parseFloat(trip.end_meter).toFixed(2) : '-') + '</td>';
                        tbody += '<td>' + trip.status_label + '</td>';
                        tbody += '</tr>';
                    });
                    $('#tripTableBody').html(tbody);

                    // Initialize DataTable if not already
                    if (!$.fn.DataTable.isDataTable('#tripDataTable')) {
                        $('#tripDataTable').DataTable({
                            destroy: true,
                            order: [[0, 'desc']]
                        });
                    }
                }
            }
        });
    }

    // Load trips in modal when opened
    $('#tripListModal').on('show.bs.modal', function () {
        loadTrips();
    });

    // ========================
    // Select Trip from Modal
    // ========================
    $(document).on('click', '.select-trip', function () {
        var tripId = $(this).data('id');
        loadTripIntoForm(tripId);
        $('#tripListModal').modal('hide');
    });

    function loadTripIntoForm(tripId) {
        $('.someBlock').preloader();
        $.ajax({
            url: 'ajax/php/trip-management.php',
            type: 'POST',
            data: { fetch_trips: true },
            dataType: 'json',
            success: function (result) {
                $('.someBlock').preloader('remove');

                if (result.status === 'success') {
                    var trip = result.data.find(function(t) { return t.id == tripId; });
                    if (!trip) return;

                    // Basic info
                    $('#id').val(trip.id);
                    $('#trip_number').val(trip.trip_number);
                    $('#transport_date').val(trip.transport_date || '');

                    // Set trip category radio
                    $('input[name="trip_category"][value="' + trip.trip_category + '"]').prop('checked', true).trigger('change');

                    // Set invoice type radio (with delay for conditional sections to render)
                    setTimeout(function() {
                        if (trip.invoice_type) {
                            $('input[name="invoice_type"][value="' + trip.invoice_type + '"]').prop('checked', true).trigger('change');

                            // Set bill or customer after invoice section shows
                            if (trip.invoice_type === 'invoice' && trip.bill_id) {
                                // Wait for options to load if necessary
                                var waitForBills = setInterval(function() {
                                    if ($('#bill_id option').length > 1) {
                                        clearInterval(waitForBills);
                                        $('#bill_id').val(trip.bill_id);
                                        if ($.fn.select2) $('#bill_id').trigger('change.select2');
                                        $('#bill_id').trigger('change');
                                        if (trip.customer_name) $('#bill_customer_name').val(trip.customer_name);
                                    }
                                }, 100);
                                setTimeout(function() { clearInterval(waitForBills); }, 5000);
                            } else if (trip.invoice_type === 'non_invoice' && trip.customer_id) {
                                $('#customer_id').val(trip.customer_id);
                                if ($.fn.select2) $('#customer_id').trigger('change.select2');
                                $('#customer_id').trigger('change');
                            }
                        }
                    }, 200);

                    // Set vehicle by ID
                    if (trip.vehicle_id) {
                        $('#vehicle_id').val(trip.vehicle_id);
                        if ($.fn.select2) $('#vehicle_id').trigger('change.select2');
                    }

                    // Set employee/driver by ID
                    if (trip.employee_id) {
                        $('#employee_id').val(trip.employee_id);
                        if ($.fn.select2) $('#employee_id').trigger('change.select2');
                    }

                    // Meter readings
                    $('#start_meter').val(trip.start_meter);

                    // Locations
                    $('#start_location').val(trip.start_location || '');
                    $('#end_location').val(trip.end_location || '');

                    // Remark
                    $('#remark').val(trip.remark || '');

                    // Transport amount
                    $('#transport_amount').val(parseFloat(trip.transport_amount || 0).toFixed(2));

                    // Show update controls, hide create
                    $('#create').hide();

                    // Show trip details section
                    $('#section-trip-details').addClass('show');

                    // Show pay amount for customer trips
                    if (trip.trip_category === 'customer') {
                        $('#section-pay-amount').addClass('show');
                        $('#pay_amount').val(parseFloat(trip.pay_amount || 0).toFixed(2));
                    }

                    // Trip completion fields
                    if (trip.end_meter && trip.end_meter !== '-') {
                        $('#end_meter').val(parseFloat(trip.end_meter).toFixed(2));
                    }
                    if (trip.trip_type && trip.trip_type !== '-') {
                        $('#trip_type').val(trip.trip_type);
                    }

                    // Cost fields
                    $('#toll').val(parseFloat(trip.toll || 0).toFixed(2));
                    $('#helper_payment').val(parseFloat(trip.helper_payment || 0).toFixed(2));
                    $('#total_cost').val(parseFloat(trip.total_cost || 0).toFixed(2));
                    $('#pay_amount_completion').val(parseFloat(trip.pay_amount || 0).toFixed(2));

                    // Payment method
                    if (trip.payment_method) {
                        $('#payment_method').val(trip.payment_method).trigger('change');
                    }
                }
            }
        });
    }

    // ========================
    // Add Search Button to Trip Number
    // ========================
    var $tripInput = $('#trip_number');
    var $parent = $tripInput.parent();
    if (!$parent.hasClass('input-group')) {
        $tripInput.wrap('<div class="input-group"></div>');
        $tripInput.after(
            '<button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#tripListModal">' +
            '<i class="uil uil-search me-1"></i>' +
            '</button>'
        );
    }

    // ========================
    // Initialize Select2 if available
    // ========================
    if ($.fn.select2) {
        $('.select2').select2();
    }

    // ========================
    // Auto-fill from URL params (bill_id or trip_id)
    // ========================
    var urlParams = new URLSearchParams(window.location.search);
    var urlBillId = urlParams.get('bill_id');
    var urlTripId = urlParams.get('trip_id');

    if (urlTripId) {
        // If trip_id is present, load the specific trip
        loadTripIntoForm(urlTripId);
    } else if (urlBillId) {
        // Step 1: Select "Customer" trip category
        $('input[name="trip_category"][value="customer"]').prop('checked', true).trigger('change');

        // Step 2: Select "Invoice" type (after a tiny delay for DOM to update)
        setTimeout(function () {
            $('input[name="invoice_type"][value="invoice"]').prop('checked', true).trigger('change');

            // Step 3: Wait for loadBills() to complete, then select the bill
            setTimeout(function () {
                // loadBills is triggered by invoice_type change, wait for AJAX
                var waitForBills = setInterval(function () {
                    if ($('#bill_id option').length > 1) {
                        clearInterval(waitForBills);
                        // Select the bill
                        $('#bill_id').val(urlBillId);
                        if ($.fn.select2) {
                            $('#bill_id').trigger('change.select2');
                        }
                        // Trigger change to load transport info and auto-fill pay_amount
                        $('#bill_id').trigger('change');
                    }
                }, 200);

                // Safety timeout - stop waiting after 5 seconds
                setTimeout(function () { clearInterval(waitForBills); }, 5000);
            }, 100);
        }, 100);
    }

    // ========================
    // Settlement Modal
    // ========================

    // Open settlement modal
    $(document).on('click', '#open-settlement-modal', function () {
        var tripId = $('#id').val();
        if (!tripId || tripId === '0') {
            swal({ title: 'Error!', text: 'Please save the trip first.', type: 'error', timer: 2000, showConfirmButton: false });
            return;
        }
        loadSettlements(tripId);
        $('#settlementModal').modal('show');
    });

    // Load settlements
    function loadSettlements(tripId) {
        $.ajax({
            url: 'ajax/php/trip-management.php',
            type: 'POST',
            data: { get_settlement_status: true, trip_id: tripId },
            dataType: 'json',
            success: function (result) {
                if (result.status === 'success') {
                    $('#settle-total-amount').text(parseFloat(result.total_amount).toFixed(2));
                    $('#settle-total-paid').text(parseFloat(result.total_paid).toFixed(2));
                    $('#settle-remaining').text(parseFloat(result.remaining).toFixed(2));

                    var tbody = '';
                    if (result.settlements && result.settlements.length > 0) {
                        result.settlements.forEach(function (s, i) {
                            tbody += '<tr>';
                            tbody += '<td>' + (i + 1) + '</td>';
                            tbody += '<td>' + s.settlement_date + '</td>';
                            tbody += '<td>' + parseFloat(s.amount).toFixed(2) + '</td>';
                            tbody += '<td>' + (s.remark || '-') + '</td>';
                            tbody += '<td><button class="btn btn-danger btn-sm delete-settlement" data-id="' + s.id + '"><i class="uil uil-trash-alt"></i></button></td>';
                            tbody += '</tr>';
                        });
                    } else {
                        tbody = '<tr><td colspan="5" class="text-center text-muted">No settlements recorded yet</td></tr>';
                    }
                    $('#settlementTableBody').html(tbody);
                }
            }
        });
    }

    // Add settlement
    $(document).on('click', '#add-settlement', function () {
        var tripId = $('#id').val();
        var amount = parseFloat($('#settle-amount').val());
        var date = $('#settle-date').val();
        var remark = $('#settle-remark').val();

        if (!amount || amount <= 0) {
            swal({ title: 'Error!', text: 'Please enter a valid amount', type: 'error', timer: 2000, showConfirmButton: false });
            return;
        }
        if (!date) {
            swal({ title: 'Error!', text: 'Please select a date', type: 'error', timer: 2000, showConfirmButton: false });
            return;
        }

        $.ajax({
            url: 'ajax/php/trip-management.php',
            type: 'POST',
            data: {
                add_settlement: true,
                trip_id: tripId,
                amount: amount,
                settlement_date: date,
                remark: remark
            },
            dataType: 'json',
            success: function (result) {
                if (result.status === 'success') {
                    swal({ title: 'Success!', text: 'Settlement added.', type: 'success', timer: 1500, showConfirmButton: false });
                    $('#settle-amount').val('');
                    $('#settle-remark').val('');
                    loadSettlements(tripId);
                } else {
                    swal({ title: 'Error!', text: result.message || 'Failed to add settlement.', type: 'error', showConfirmButton: true });
                }
            }
        });
    });

    // Delete settlement
    $(document).on('click', '.delete-settlement', function () {
        var settlementId = $(this).data('id');
        var tripId = $('#id').val();

        swal({
            title: 'Are you sure?',
            text: 'Do you want to delete this settlement?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel',
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: 'ajax/php/trip-management.php',
                    type: 'POST',
                    data: { delete_settlement: true, settlement_id: settlementId },
                    dataType: 'json',
                    success: function (result) {
                        if (result.status === 'success') {
                            swal({ title: 'Deleted!', text: 'Settlement removed.', type: 'success', timer: 1500, showConfirmButton: false });
                            loadSettlements(tripId);
                        } else {
                            swal({ title: 'Error!', text: 'Failed to delete.', type: 'error', timer: 2000, showConfirmButton: false });
                        }
                    }
                });
            }
        });
    });

});
