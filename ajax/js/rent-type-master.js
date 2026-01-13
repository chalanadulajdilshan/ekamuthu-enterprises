jQuery(document).ready(function () {
    // Load Rent Type Table when modal opens
    $("#RentTypeModal").on("shown.bs.modal", function () {
        loadRentTypeTable();
    });

    function loadRentTypeTable() {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable("#rentTypeTable")) {
            $("#rentTypeTable").DataTable().destroy();
        }

        $("#rentTypeTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/rent-type-master.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                },
                dataSrc: function (json) {
                    return json.data;
                },
                error: function (xhr) {
                    console.error("Server Error Response:", xhr.responseText);
                },
            },
            columns: [
                { data: "key", title: "#ID" },
                { data: "equipment_name", title: "Equipment" },
                { data: "name", title: "Rent Type Name" },
                { data: "price", title: "Price" },
                { data: "deposit_amount", title: "Deposit Amount" },
            ],
            order: [[0, "desc"]],
            pageLength: 100,
        });

        // Row click event to populate form and close modal
        $("#rentTypeTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#rentTypeTable").DataTable().row(this).data();

                if (data) {
                    $("#rent_type_id").val(data.id || "");
                    $("#equipment_id").val(data.equipment_id || "");
                    $("#name").val(data.name || "");
                    $("#price").val(data.price_raw || "0.00");
                    $("#deposit_amount").val(data.deposit_amount_raw || "0.00");

                    // Show update button, hide create button
                    $("#create").hide();
                    $("#update").show();

                    // Close the modal
                    $("#RentTypeModal").modal("hide");
                }
            });
    }

    // Create Rent Type
    $("#create").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#create").prop("disabled", true);

        // Validation
        if (!$("#equipment_id").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select equipment",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#name").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter rent type name",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#price").val() || parseFloat($("#price").val()) <= 0) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter a valid price",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else {
            // Show page preloader
            $("#page-preloader").show();

            var formData = new FormData($("#form-data")[0]);
            formData.append("create", true);

            $.ajax({
                url: "ajax/php/rent-type-master.php",
                type: "POST",
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    // Hide page preloader
                    $("#page-preloader").hide();

                    // Re-enable the button
                    $("#create").prop("disabled", false);

                    if (result.status === "success") {
                        swal({
                            title: "Success!",
                            text: "Rent type added successfully!",
                            type: "success",
                            timer: 2000,
                            showConfirmButton: false,
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else if (result.status === "duplicate") {
                        swal({
                            title: "Duplicate Entry!",
                            text: result.message,
                            type: "warning",
                            showConfirmButton: true,
                        });
                    } else {
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: "error",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                },
                error: function (xhr, status, error) {
                    // Hide page preloader
                    $("#page-preloader").hide();

                    // Re-enable the button
                    $("#create").prop("disabled", false);

                    console.error("AJAX Error:", status, error);
                    console.error("Response:", xhr.responseText);

                    swal({
                        title: "Error!",
                        text: "Failed to create rent type. Please check the console for details.",
                        type: "error",
                        showConfirmButton: true,
                    });
                },
            });
        }

        return false;
    });

    // Update Rent Type
    $("#update").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#update").prop("disabled", true);

        if (!$("#equipment_id").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select equipment",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#name").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter rent type name",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#price").val() || parseFloat($("#price").val()) <= 0) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter a valid price",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else {
            // Show page preloader
            $("#page-preloader").show();

            var formData = new FormData($("#form-data")[0]);
            formData.append("update", true);

            $.ajax({
                url: "ajax/php/rent-type-master.php",
                type: "POST",
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    // Hide page preloader
                    $("#page-preloader").hide();

                    if (result.status == "success") {
                        swal({
                            title: "Success!",
                            text: "Rent type updated successfully!",
                            type: "success",
                            timer: 2500,
                            showConfirmButton: false,
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else if (result.status === "duplicate") {
                        // Re-enable the button
                        $("#update").prop("disabled", false);
                        swal({
                            title: "Duplicate Entry!",
                            text: result.message,
                            type: "warning",
                            showConfirmButton: true,
                        });
                    } else {
                        // Re-enable the button
                        $("#update").prop("disabled", false);
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: "error",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                },
                error: function (xhr, status, error) {
                    // Hide page preloader
                    $("#page-preloader").hide();

                    // Re-enable the button
                    $("#update").prop("disabled", false);

                    console.error("AJAX Error:", status, error);
                    console.error("Response:", xhr.responseText);

                    swal({
                        title: "Error!",
                        text: "Failed to update rent type. Please check the console for details.",
                        type: "error",
                        showConfirmButton: true,
                    });
                },
            });
        }

        return false;
    });

    // Reset input fields
    $("#new").click(function (e) {
        e.preventDefault();
        $("#form-data")[0].reset();
        $("#rent_type_id").val("");
        $("#equipment_id").prop("selectedIndex", 0);
        $("#price").val("0.00");
        $("#deposit_amount").val("0.00");
        $("#create").show();
        $("#update").hide();
    });

    // Delete Rent Type
    $(document).on("click", ".delete-rent-type", function (e) {
        e.preventDefault();

        // Disable the button to prevent multiple submissions
        $(".delete-rent-type").prop("disabled", true);

        var rentTypeId = $("#rent_type_id").val();
        var rentTypeName = $("#name").val();

        if (!rentTypeId || rentTypeId === "") {
            // Re-enable the button on validation error
            $(".delete-rent-type").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a rent type first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        swal(
            {
                title: "Are you sure?",
                text: "Do you want to delete rent type '" + rentTypeName + "'?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
            },
            function (isConfirm) {
                if (isConfirm) {
                    // Show page preloader
                    $("#page-preloader").show();

                    $.ajax({
                        url: "ajax/php/rent-type-master.php",
                        type: "POST",
                        data: {
                            id: rentTypeId,
                            delete: true,
                        },
                        dataType: "JSON",
                        success: function (response) {
                            // Hide page preloader
                            $("#page-preloader").hide();

                            // Re-enable the button
                            $(".delete-rent-type").prop("disabled", false);

                            if (response.status === "success") {
                                swal({
                                    title: "Deleted!",
                                    text: "Rent type has been deleted.",
                                    type: "success",
                                    timer: 2000,
                                    showConfirmButton: false,
                                });

                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                swal({
                                    title: "Error!",
                                    text: "Something went wrong.",
                                    type: "error",
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                            }
                        },
                    });
                } else {
                    // Re-enable the button if user cancels
                    $(".delete-rent-type").prop("disabled", false);
                }
            }
        );
    });
});
