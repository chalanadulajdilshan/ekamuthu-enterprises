$(document).ready(function() {
    
    // Open return modal for an item
    window.openReturnModal = function(rentItemId) {
        $("#returnModal").modal("show");
        $("#return_rent_item_id").val(rentItemId);
        $("#return_qty").val(1);
        $("#damage_amount").val(0);
        $("#return_date").val(new Date().toISOString().split('T')[0]);
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        $("#return_time").val(`${hh}:${mm}`);
        $("#after_9am_extra_day").prop('checked', false);
        $("#extra_day_amount").val(0).prop('disabled', true);
        $("#penalty_percentage").val(0).prop('disabled', true);
        $("#return_remark").val("");
        
        // Clear settlement display
        $("#settlementPreview").hide();
        
        // Load item details
        loadItemDetails(rentItemId);
        // Show an initial calculation so the user sees settlement/deposit straight away
        calculateSettlement();
    };
    
    // Load item details for return form
    function loadItemDetails(rentItemId) {
        const returnDate = $("#return_date").val();
        $.ajax({
            url: 'ajax/php/equipment-rent-return.php',
            type: 'POST',
            data: {
                action: 'get_item_details',
                rent_item_id: rentItemId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    
                    // Display item information
                    $("#returnItemInfo").html(`
                        <div class="alert alert-info mb-3">
                            <div class="text-center mb-2">
                                <h6 class="mb-0 fw-bold">Equipment Summary</h6>
                                <small class="text-muted">${data.equipment_name} (${data.equipment_code})</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div><strong>Unit Code:</strong> ${data.sub_equipment_code || '-'}</div>
                                    <div><strong>Total Qty:</strong> ${data.quantity}</div>
                                    <div><strong>Already Returned:</strong> ${data.total_returned}</div>
                                    <div><strong>Pending Qty:</strong> ${data.pending_qty}</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div><strong>Customer Deposit:</strong> Rs. ${parseFloat(data.customer_deposit || 0).toFixed(2)}</div>
                                    <div><strong>Deposit (Total):</strong> Rs. ${parseFloat(data.deposit_amount).toFixed(2)}</div>
                                    <div><strong>Deposit Per Unit:</strong> Rs. ${(parseFloat(data.deposit_amount) / parseFloat(data.quantity)).toFixed(2)}</div>
                                    <div><strong class="text-danger">Equipment Damage (catalog):</strong> <span class="text-danger">Rs. ${parseFloat(data.equipment_damage || 0).toFixed(2)}</span></div>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    // Set max return quantity
                    $("#return_qty").attr("max", data.pending_qty);
                    $("#return_qty").val(Math.min(1, data.pending_qty));
                    
                    // Display existing returns if any
                    if (data.returns && data.returns.length > 0) {
                        let returnsHtml = '<h6 class="mt-3">Previous Returns:</h6><div class="table-responsive"><table class="table table-sm table-bordered">';
                        returnsHtml += '<thead><tr><th>Date</th><th>Time</th><th>Qty</th><th>Rental</th><th>Extra Day</th><th>Penalty</th><th>Damage</th><th>Settlement</th><th>Remark</th></tr></thead><tbody>';
                        
                        data.returns.forEach(function(ret) {
                            let settlementText = '';
                            if (parseFloat(ret.refund_amount) > 0) {
                                settlementText = `<span class="text-success">Refund: Rs. ${parseFloat(ret.refund_amount).toFixed(2)}</span>`;
                            } else if (parseFloat(ret.additional_payment) > 0) {
                                settlementText = `<span class="text-danger">Pay: Rs. ${parseFloat(ret.additional_payment).toFixed(2)}</span>`;
                            } else {
                                settlementText = '<span class="text-muted">No charge</span>';
                            }
                            
                            const penaltyText = parseFloat(ret.penalty_amount || 0) > 0 
                                ? `<span class="text-danger">Rs. ${parseFloat(ret.penalty_amount).toFixed(2)} (${parseFloat(ret.penalty_percentage || 0).toFixed(0)}%)</span>`
                                : '-';
                            
                            returnsHtml += `<tr>
                                <td>${ret.return_date}</td>
                                <td>${ret.return_time || '-'}</td>
                                <td>${ret.return_qty}</td>
                                <td>Rs. ${parseFloat(ret.rental_amount || 0).toFixed(2)}</td>
                                <td>Rs. ${parseFloat(ret.extra_day_amount || 0).toFixed(2)}</td>
                                <td>${penaltyText}</td>
                                <td>Rs. ${parseFloat(ret.damage_amount).toFixed(2)}</td>
                                <td>${settlementText}</td>
                                <td>${ret.remark || '-'}</td>
                            </tr>`;
                        });
                        
                        returnsHtml += '</tbody></table></div>';
                        $("#previousReturns").html(returnsHtml).show();
                    } else {
                        $("#previousReturns").hide();
                    }
                    // After details are loaded, show the initial settlement block
                    calculateSettlement();
                }
            },
            error: function() {
                swal({
                    title: "Error!",
                    text: "Failed to load item details",
                    type: "error",
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }
    
    // Calculate settlement on input change
    $("#return_qty, #damage_amount, #return_date, #return_time, #after_9am_extra_day, #extra_day_amount, #penalty_percentage").on("input change", function() {
        calculateSettlement();
    });
    
    // Calculate and display settlement
    function calculateSettlement() {
        const rentItemId = $("#return_rent_item_id").val();
        const returnQty = parseFloat($("#return_qty").val()) || 0;
        const damageAmount = parseFloat($("#damage_amount").val()) || 0;
        const returnDate = $("#return_date").val();
        const returnTime = $("#return_time").val();
        const after9amExtraDay = $("#after_9am_extra_day").is(':checked') ? 1 : 0;
        const extraDayAmount = parseFloat($("#extra_day_amount").val()) || 0;
        const penaltyPercentage = parseFloat($("#penalty_percentage").val()) || 0;
        
        if (returnQty <= 0) {
            $("#settlementPreview").hide();
            return;
        }
        
        $.ajax({
            url: 'ajax/php/equipment-rent-return.php',
            type: 'POST',
            data: {
                action: 'calculate_settlement',
                rent_item_id: rentItemId,
                return_qty: returnQty,
                damage_amount: damageAmount,
                return_date: returnDate,
                return_time: returnTime,
                after_9am_extra_day: after9amExtraDay,
                extra_day_amount: extraDayAmount,
                penalty_percentage: penaltyPercentage
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const calc = response.data;

                    if (after9amExtraDay) {
                        $("#extra_day_amount").prop('disabled', false);
                        const currentExtra = parseFloat($("#extra_day_amount").val());
                        if (!currentExtra || currentExtra <= 0) {
                            $("#extra_day_amount").val(parseFloat(calc.extra_day_amount || 0).toFixed(2));
                        }
                    } else {
                        $("#extra_day_amount").val(0).prop('disabled', true);
                    }
                    
                    // Enable penalty field only when return is late
                    if (calc.is_late) {
                        $("#penalty_percentage").prop('disabled', false);
                        $("#penaltySection").show();
                    } else {
                        $("#penalty_percentage").val(0).prop('disabled', true);
                        $("#penaltySection").hide();
                    }
                    
                    let settlementHtml = `
                        <div class="alert alert-warning">
                            <h6><strong>Settlement Calculation:</strong></h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Return Qty:</td>
                                    <td class="text-right"><strong>${returnQty}</strong></td>
                                </tr>
                                <tr>
                                    <td>Deposit for this qty:</td>
                                    <td class="text-right">Rs. ${calc.deposit_for_return.toFixed(2)}</td>
                                </tr>
                                <tr>
                                    <td>Used Days:</td>
                                    <td class="text-right">${calc.used_days} day(s)</td>
                                </tr>
                                <tr>
                                    <td>Charged Days:</td>
                                    <td class="text-right">${calc.charged_days} day(s)</td>
                                </tr>
                                <tr>
                                    <td>Daily Rate (per unit):</td>
                                    <td class="text-right">Rs. ${calc.per_unit_daily.toFixed(2)}</td>
                                </tr>
                                <tr>
                                    <td>Extra Day Amount:</td>
                                    <td class="text-right">Rs. ${(parseFloat(calc.extra_day_amount || 0)).toFixed(2)}</td>
                                </tr>
                                ${calc.is_late ? `<tr class="text-danger">
                                    <td>Overdue Days:</td>
                                    <td class="text-right">${calc.overdue_days} day(s)</td>
                                </tr>
                                <tr class="text-danger">
                                    <td>Penalty (${calc.penalty_percentage}%):</td>
                                    <td class="text-right">Rs. ${(parseFloat(calc.penalty_amount || 0)).toFixed(2)}</td>
                                </tr>` : ''}
                                <tr>
                                    <td>Rental for this qty:</td>
                                    <td class="text-right">Rs. ${calc.rental_amount.toFixed(2)}</td>
                                </tr>
                                <tr>
                                    <td>Damage Amount:</td>
                                    <td class="text-right">Rs. ${calc.damage_amount.toFixed(2)}</td>
                                </tr>
                                <tr>
                                    <td>Net Settlement:</td>
                                    <td class="text-right">Rs. ${calc.settle_amount.toFixed(2)}</td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>Settlement:</strong></td>
                                    <td class="text-right">`;
                    
                    if (calc.refund_amount > 0) {
                        settlementHtml += `<strong class="text-success">Refund: Rs. ${calc.refund_amount.toFixed(2)}</strong>`;
                    } else if (calc.additional_payment > 0) {
                        settlementHtml += `<strong class="text-danger">Customer Pays: Rs. ${calc.additional_payment.toFixed(2)}</strong>`;
                    } else {
                        settlementHtml += `<strong class="text-muted">No charge (Break-even)</strong>`;
                    }
                    
                    settlementHtml += `</td></tr></table></div>`;
                    
                    $("#settlementPreview").html(settlementHtml).show();
                } else {
                    $("#settlementPreview").html(`<div class="alert alert-danger">${response.message}</div>`).show();
                }
            }
        });
    }
    
    // Save return
    $("#saveReturnBtn").click(function() {
        const rentItemId = $("#return_rent_item_id").val();
        const returnDate = $("#return_date").val();
        const returnTime = $("#return_time").val();
        const returnQty = parseFloat($("#return_qty").val()) || 0;
        const damageAmount = parseFloat($("#damage_amount").val()) || 0;
        const after9amExtraDay = $("#after_9am_extra_day").is(':checked') ? 1 : 0;
        const extraDayAmount = parseFloat($("#extra_day_amount").val()) || 0;
        const penaltyPercentage = parseFloat($("#penalty_percentage").val()) || 0;
        const remark = $("#return_remark").val();
        
        if (!rentItemId) {
            swal({
                title: "Error!",
                text: "Invalid item",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        if (returnQty <= 0) {
            swal({
                title: "Error!",
                text: "Return quantity must be greater than 0",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        if (damageAmount < 0) {
            swal({
                title: "Error!",
                text: "Damage amount cannot be negative",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        $.ajax({
            url: 'ajax/php/equipment-rent-return.php',
            type: 'POST',
            data: {
                action: 'create_return',
                rent_item_id: rentItemId,
                return_date: returnDate,
                return_time: returnTime,
                return_qty: returnQty,
                damage_amount: damageAmount,
                after_9am_extra_day: after9amExtraDay,
                extra_day_amount: extraDayAmount,
                penalty_percentage: penaltyPercentage,
                remark: remark
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    $("#returnModal").modal("hide");
                    
                    // Reload the rent details if function exists
                    if (typeof loadRentDetails === 'function') {
                        loadRentDetails();
                    } else if (typeof loadRentItemsTable === 'function') {
                        loadRentItemsTable();
                    } else {
                        location.reload();
                    }
                } else {
                    swal({
                        title: "Error!",
                        text: response.message,
                        type: "error",
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            },
            error: function() {
                swal({
                    title: "Error!",
                    text: "Failed to process return",
                    type: "error",
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });
    
    // View returns history for an item
    window.viewReturnsHistory = function(rentItemId) {
        $.ajax({
            url: 'ajax/php/equipment-rent-return.php',
            type: 'POST',
            data: {
                action: 'get_returns',
                rent_item_id: rentItemId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    let modalHtml = `
                        <div class="modal fade" id="returnsHistoryModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Returns History</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">`;
                    
                    if (response.returns && response.returns.length > 0) {
                        modalHtml += `
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Qty</th>
                                            <th>Damage</th>
                                            <th>Extra Day</th>
                                            <th>Penalty</th>
                                            <th>Refund</th>
                                            <th>Additional Payment</th>
                                            <th>Created By</th>
                                            <th>Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                        
                        response.returns.forEach(function(ret) {
                            const penaltyText = parseFloat(ret.penalty_amount || 0) > 0 
                                ? `Rs. ${parseFloat(ret.penalty_amount).toFixed(2)} (${parseFloat(ret.penalty_percentage || 0).toFixed(0)}%)`
                                : '-';
                            modalHtml += `
                                <tr>
                                    <td>${ret.return_date}</td>
                                    <td>${ret.return_time || '-'}</td>
                                    <td>${ret.return_qty}</td>
                                    <td>Rs. ${parseFloat(ret.damage_amount).toFixed(2)}</td>
                                    <td>Rs. ${parseFloat(ret.extra_day_amount || 0).toFixed(2)}</td>
                                    <td class="text-danger">${penaltyText}</td>
                                    <td class="text-success">Rs. ${parseFloat(ret.refund_amount).toFixed(2)}</td>
                                    <td class="text-danger">Rs. ${parseFloat(ret.additional_payment).toFixed(2)}</td>
                                    <td>${ret.created_by_name || '-'}</td>
                                    <td>${ret.remark || '-'}</td>
                                </tr>`;
                        });
                        
                        modalHtml += `
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="3">Total Settlement:</td>
                                            <td colspan="2"></td>
                                            <td></td>
                                            <td class="text-success">Rs. ${response.settlement.total_refund.toFixed(2)}</td>
                                            <td class="text-danger">Rs. ${response.settlement.total_additional.toFixed(2)}</td>
                                            <td colspan="2">Net: Rs. ${response.settlement.net_settlement.toFixed(2)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>`;
                    } else {
                        modalHtml += '<p class="text-muted">No returns recorded yet.</p>';
                    }
                    
                    modalHtml += `
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    
                    // Remove existing modal if present
                    $("#returnsHistoryModal").remove();
                    
                    // Append and show modal
                    $("body").append(modalHtml);
                    $("#returnsHistoryModal").modal("show");

                    // Ensure proper cleanup when hidden
                    $("#returnsHistoryModal").on('hidden.bs.modal', function () {
                        $(this).remove();
                    });
                }
            }
        });
    };
});
