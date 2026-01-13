jQuery(document).ready(function () {
    // Load Equipment Rent Table when modal opens
    $("#EquipmentRentModal").on("shown.bs.modal", function () {
        loadEquipmentRentTable();
    });

    function loadEquipmentRentTable() {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable("#equipmentRentTable")) {
            $("#equipmentRentTable").DataTable().destroy();
        }

        $("#equipmentRentTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-master.php",
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
                { data: "code", title: "Code" },
                { data: "customer_name", title: "Customer" },
                { data: "equipment_name", title: "Equipment" },
                { data: "rental_date", title: "Rental Date" },
                { data: "received_date", title: "Received Date" },
                { data: "quantity", title: "Qty" },
                { data: "status_label", title: "Status" },
            ],
            order: [[0, "desc"]],
            pageLength: 100,
        });

        // Row click event to populate form and close modal
        $("#equipmentRentTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#equipmentRentTable").DataTable().row(this).data();

                if (data) {
                    $("#rent_id").val(data.id || "");
                    $("#code").val(data.code || "");
                    $("#customer_id").val(data.customer_id || "");
                    $("#customer_display").val(data.customer_name || "");
                    $("#equipment_id").val(data.equipment_id || "");
                    $("#equipment_display").val(data.equipment_name || "");
                    $("#rental_date").val(data.rental_date || "");
                    $("#received_date").val(data.received_date || "");
                    $("#rent_status").val(data.status || "rented");
                    $("#quantity").val(data.quantity || "1");
                    $("#available_quantity").val(data.available_quantity || "0");
                    $("#remark").val(data.remark || "");

                    // Show update button, hide create button
                    $("#create").hide();
                    $("#update").show();

                    // Close the modal
                    $("#EquipmentRentModal").modal("hide");
                }
            });
    }

    // Load Customer Table when modal opens
    $("#CustomerSelectModal").on("shown.bs.modal", function () {
        loadCustomerTable();
    });

    function loadCustomerTable() {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable("#customerSelectTable")) {
            $("#customerSelectTable").DataTable().destroy();
        }

        $("#customerSelectTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-master.php",
                type: "POST",
                data: function (d) {
                    d.filter_customers = true;
                },
                dataSrc: function (json) {
                    return json.data;
                },
                error: function (xhr) {
                    console.error("Server Error Response:", xhr.responseText);
                },
            },
            columns: [
                { data: "key", title: "#" },
                { data: "code", title: "Code" },
                { data: "name", title: "Name" },
                { data: "mobile_number", title: "Mobile" },
            ],
            order: [[2, "asc"]],
            pageLength: 50,
        });

        // Row click event to select customer
        $("#customerSelectTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#customerSelectTable").DataTable().row(this).data();

                if (data) {
                    $("#customer_id").val(data.id || "");
                    $("#customer_display").val(data.code + " - " + data.name || "");

                    // Close the modal
                    $("#CustomerSelectModal").modal("hide");
                }
            });
    }

    // Load Equipment Table when modal opens
    $("#EquipmentSelectModal").on("shown.bs.modal", function () {
        loadEquipmentTable();
    });

    function loadEquipmentTable() {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable("#equipmentSelectTable")) {
            $("#equipmentSelectTable").DataTable().destroy();
        }

        $("#equipmentSelectTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-master.php",
                type: "POST",
                data: function (d) {
                    d.filter_equipment = true;
                },
                dataSrc: function (json) {
                    return json.data;
                },
                error: function (xhr) {
                    console.error("Server Error Response:", xhr.responseText);
                },
            },
            columns: [
                { data: "key", title: "#" },
                { data: "code", title: "Code" },
                { data: "item_name", title: "Item Name" },
                { data: "category_label", title: "Category" },
                { data: "quantity", title: "Available Qty" },
                { data: "condition_label", title: "Condition" },
            ],
            order: [[2, "asc"]],
            pageLength: 50,
        });

        // Row click event to select equipment
        $("#equipmentSelectTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#equipmentSelectTable").DataTable().row(this).data();

                if (data) {
                    $("#equipment_id").val(data.id || "");
                    $("#equipment_display").val(data.code + " - " + data.item_name || "");
                    $("#available_quantity").val(data.quantity || "0");

                    // Close the modal
                    $("#EquipmentSelectModal").modal("hide");
                }
            });
    }

    // Create Equipment Rent
    $("#create").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#create").prop("disabled", true);

        // Validation
        if (!$("#code").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter equipment rent code",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#customer_id").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a customer",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#equipment_id").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select equipment",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#rental_date").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter rental date",
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
                url: "ajax/php/equipment-rent-master.php",
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
                            text: "Equipment rent added successfully!",
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
                            text: result.message || "Something went wrong.",
                            type: "error",
                            showConfirmButton: true,
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
                        text: "Failed to create equipment rent. Please check the console for details.",
                        type: "error",
                        showConfirmButton: true,
                    });
                },
            });
        }

        return false;
    });

    // Update Equipment Rent
    $("#update").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#update").prop("disabled", true);

        if (!$("#code").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter equipment rent code",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#customer_id").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a customer",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#equipment_id").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select equipment",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#rental_date").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter rental date",
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
                url: "ajax/php/equipment-rent-master.php",
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
                            text: "Equipment rent updated successfully!",
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
                            text: result.message || "Something went wrong.",
                            type: "error",
                            showConfirmButton: true,
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
                        text: "Failed to update equipment rent. Please check the console for details.",
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
        $("#rent_id").val("");
        $("#customer_id").val("");
        $("#equipment_id").val("");
        $("#customer_display").val("");
        $("#equipment_display").val("");
        $("#available_quantity").val("0");
        $("#rent_status").prop("selectedIndex", 0);
        $("#create").show();
        $("#update").hide();

        // Generate new code
        $.ajax({
            url: "ajax/php/equipment-rent-master.php",
            type: "POST",
            data: { action: "get_new_code" },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#code").val(result.code);
                }
            },
        });
    });

    // Delete Equipment Rent
    $(document).on("click", ".delete-equipment-rent", function (e) {
        e.preventDefault();

        // Disable the button to prevent multiple submissions
        $(".delete-equipment-rent").prop("disabled", true);

        var rentId = $("#rent_id").val();
        var rentCode = $("#code").val();

        if (!rentId || rentId === "") {
            // Re-enable the button on validation error
            $(".delete-equipment-rent").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select an equipment rent record first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        swal(
            {
                title: "Are you sure?",
                text: "Do you want to delete equipment rent '" + rentCode + "'?",
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
                        url: "ajax/php/equipment-rent-master.php",
                        type: "POST",
                        data: {
                            id: rentId,
                            delete: true,
                        },
                        dataType: "JSON",
                        success: function (response) {
                            // Hide page preloader
                            $("#page-preloader").hide();

                            // Re-enable the button
                            $(".delete-equipment-rent").prop("disabled", false);

                            if (response.status === "success") {
                                swal({
                                    title: "Deleted!",
                                    text: "Equipment rent has been deleted.",
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
                    $(".delete-equipment-rent").prop("disabled", false);
                }
            }
        );
    });
});
