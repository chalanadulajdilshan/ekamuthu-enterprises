// Store selected ARN ID globally
var selectedArnId = null;

// New button behavior: simply reload the page to reset the form and layout
function newPurchaseReturn() {
    window.location.reload();
}

function selectArnOrder(arnId, arnNumber) {
    selectedArnId = arnId;
    $('#itemTableHeader').text('ARN Return Items - ARN Number: ' + arnNumber);
    $('#arn_no').val(arnNumber);

    var $row = $('#arnTable').find('tr.select-arn[data-id="' + arnId + '"]');
    if ($row.length) {
        var supplierCode = $row.data('supplier_code') || '';
        var supplierName = $row.data('supplier_name') || '';
        var departmentId = $row.data('department') || '';
        $('#customer_id').val(supplierCode);
        $('#customer_name').val(supplierName);
        if (departmentId) {
            $('#department_id').val(departmentId);
        }
    }

    $.ajax({
        url: 'purchase-return-arn-items.php',
        method: 'GET',
        data: { arn_id: arnId },
        success: function (response) {
            $('#itemTableContainer').html(response);
            $('#po_number_modal').modal('hide');
        },
        error: function () {
            swal({
                title: "Error!",
                text: "Something went wrong while fetching ARN items.",
                type: 'error',
                timer: 2500,
                showConfirmButton: false
            });
        }
    });
}

// Handle Save button click - use main form fields
$(document).on('click', '#makeReturnBtn', function(e) {
    e.preventDefault();
    
    var refNo = $('#reference_no').val();
    var reason = $('#reason').val();
    var returnDate = $('#date').val();
    var departmentId = $('#department_id').val();
    var arnIdHidden = $('#arn_id_hidden').val();
    var arnId = arnIdHidden || selectedArnId;

    if (!arnId) {
        swal({
            title: "Error!",
            text: "Please select an ARN first.",
            type: 'error',
            timer: 2500,
            showConfirmButton: false
        });
        return;
    }

    if (!refNo) {
        swal({
            title: "Error!",
            text: "Reference No is required.",
            type: 'error',
            timer: 2500,
            showConfirmButton: false
        });
        return;
    }

    if (!reason) {
        swal({
            title: "Error!",
            text: "Please enter a return reason.",
            type: 'error',
            timer: 2500,
            showConfirmButton: false
        });
        return;
    }

    var hasValidQty = false;
    var hasError = false;
    var returnItems = [];

    $('.return-qty').each(function() {
        var qty = parseFloat($(this).val()) || 0;
        var available = parseFloat($(this).data('available')) || 0;
        var itemId = $(this).data('item-id');

        if (qty > 0) {
            hasValidQty = true;
            if (qty > available) {
                swal({
                    title: "Error!",
                    text: "Return quantity cannot exceed available stock (" + available + ").",
                    type: 'error',
                    timer: 3000,
                    showConfirmButton: false
                });
                hasError = true;
                return false;
            } else {
                returnItems.push({
                    item_id: itemId,
                    quantity: qty
                });
            }
        }
    });

    if (hasError) {
        return;
    }

    if (!hasValidQty) {
        swal({
            title: "Warning!",
            text: "Please enter return quantity for at least one item.",
            type: 'warning',
            timer: 2500,
            showConfirmButton: false
        });
        return;
    }

    // Send AJAX directly without confirmation for now
    $.ajax({
        url: 'save-purchase-return.php',
        method: 'POST',
        data: {
            arn_id: arnId,
            ref_no: refNo,
            return_reason: reason,
            return_date: returnDate,
            department_id: departmentId,
            return_items: JSON.stringify(returnItems)
        },
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response);
            if (response.status === 'success') {
                swal({
                    title: "Success!",
                    text: response.message || "Purchase return saved successfully!",
                    type: 'success',
                    timer: 2500,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    location.reload();
                }, 2500);
            } else {
                swal({
                    title: "Error!",
                    text: response.message || "Failed to save purchase return.",
                    type: 'error',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
            swal({
                title: "Error!",
                text: "An error occurred while saving the return.",
                type: 'error',
                timer: 2500,
                showConfirmButton: false
            });
        }
    });
});

// Override generic #new handlers on this page and bind to newPurchaseReturn only
$(document).ready(function () {
    $('#new').off('click');
    $('#new').on('click', function (e) {
        e.preventDefault();
        newPurchaseReturn();
    });
});

// View return items in modal
$(document).on('click', '.view-return-items', function () {
    var returnId = $(this).data('id');
    $.ajax({
        url: 'purchase-returned-item-list.php',
        method: 'GET',
        data: { return_id: returnId },
        success: function (response) {
            $('#returnItemsContainer').html(response);
            $('#returnItemsModal').modal('show');
            $('#makeReturnBtn').hide();
            $('#update').show();
        },
        error: function () {
            swal({
                title: "Error!",
                text: "Failed to load return items.",
                type: 'error',
                timer: 2500,
                showConfirmButton: false
            });
        }
    });
});
