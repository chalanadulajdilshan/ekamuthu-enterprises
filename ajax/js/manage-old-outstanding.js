$(document).ready(function () {

    // Hide preloader
    $("#page-preloader").fadeOut();

    var customerId = $("#customer_id").val();

    if (customerId) {
        loadRecords(customerId);
    }

    // Load Records Function
    function loadRecords(id) {
        $.ajax({
            url: "ajax/php/customer-master.php",
            type: "POST",
            data: { action: "get_old_outstanding_details", customer_id: id },
            dataType: "JSON",
            success: function (data) {
                var rows = "";
                if (data.length > 0) {
                    $.each(data, function (i, item) {
                        rows += `
                            <tr>
                                <td>${item.invoice_no}</td>
                                <td>${item.date}</td>
                                <td class="text-end">${parseFloat(item.amount).toFixed(2)}</td>
                                <td>${item.status == 'Not Paid' ? '<span class="badge bg-danger">Not Paid</span>' : '<span class="badge bg-success">Paid</span>'}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-danger delete-record" data-id="${item.id}" data-amount="${item.amount}" data-status="${item.status}">
                                        <i class="uil uil-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    rows = `<tr><td colspan="5" class="text-center">No records found</td></tr>`;
                }
                $("#recordsTable tbody").html(rows);
            }
        });
    }

    // Save Record
    $("#saveRecord").click(function () {
        var formData = new FormData($("#oldOutstandingForm")[0]);
        formData.append("action", "add_old_outstanding_detail");

        if (!$("#detail_invoice_no").val() || !$("#detail_date").val() || !$("#detail_amount").val()) {
            swal("Error", "Please fill all required fields", "error");
            return;
        }

        $("#saveRecord").prop("disabled", true).text("Saving...");

        $.ajax({
            url: "ajax/php/customer-master.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "JSON",
            success: function (response) {
                $("#saveRecord").prop("disabled", false).html('<i class="uil uil-save me-1"></i> Add Record');

                if (response.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Record added successfully!",
                        type: "success",
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Clear form but keep Customer ID
                    var custId = $("#customer_id").val();
                    $("#oldOutstandingForm")[0].reset();
                    $("#customer_id").val(custId);

                    loadRecords(custId);
                } else {
                    swal("Error", response.message, "error");
                }
            },
            error: function () {
                $("#saveRecord").prop("disabled", false).html('<i class="uil uil-save me-1"></i> Add Record');
                swal("Error", "System error occurred", "error");
            }
        });
    });

    // Delete Record
    $(document).on("click", ".delete-record", function () {
        var id = $(this).data("id");
        var amount = $(this).data("amount");
        var status = $(this).data("status");
        var custId = $("#customer_id").val();

        swal({
            title: "Are you sure?",
            text: "This will delete the record and adjust the customer balance.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: "ajax/php/customer-master.php",
                type: "POST",
                data: { action: "delete_old_outstanding_detail", id: id },
                dataType: "JSON",
                success: function (response) {
                    if (response.status === "success") {
                        swal("Deleted!", "Record deleted.", "success");
                        loadRecords(custId);
                    } else {
                        swal("Error", response.message, "error");
                    }
                }
            });
        });
    });

});
