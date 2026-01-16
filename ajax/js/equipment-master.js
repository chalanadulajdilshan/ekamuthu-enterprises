jQuery(document).ready(function () {
    // Load Equipment Table when modal opens
    $("#EquipmentModal").on("shown.bs.modal", function () {
        loadEquipmentTable();
    });

    // Shared required fields list
    var requiredFields = [
        { selector: "#code", message: "Please enter equipment code" },
        { selector: "#item_name", message: "Please enter item name" },
        { selector: "#category", message: "Please select category" },
        { selector: "#serial_number", message: "Please enter serial number" },
        { selector: "#damage", message: "Please enter damage status/notes" },
        { selector: "#size", message: "Please enter size" },
        { selector: "#rent_one_day", message: "Please enter one day's rent" },
        { selector: "#deposit_one_day", message: "Please enter one day's deposit" },
        { selector: "#rent_one_month", message: "Please enter one month's rent" },
        { selector: "#value", message: "Please enter value" },
        { selector: "#quantity", message: "Please enter quantity" }
    ];

    function loadEquipmentTable() {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable("#equipmentTable")) {
            $("#equipmentTable").DataTable().destroy();
        }

        $("#equipmentTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-master.php",
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
                { data: "item_name", title: "Item Name" },
                { data: "category_label", title: "Category" },
                { data: "serial_number", title: "Serial Number" },
                { data: "value", title: "Value" },
                { data: "quantity", title: "Quantity" },
                {
                    data: null,
                    title: "Action",
                    orderable: false,
                    render: function (data, type, row) {
                        return '<button class="btn btn-sm btn-info add-sub-equipment" data-id="' + row.id + '" data-code="' + row.code + '" data-name="' + row.item_name + '"><i class="uil uil-plus me-1"></i>Add Sub</button>';
                    }
                }
            ],
            order: [[0, "desc"]],
            pageLength: 100,
        });

        // Row click event to populate form and close modal (exclude action/expand clicks)
        $("#equipmentTable tbody")
            .off("click", "tr")
            .on("click", "tr", function (e) {
                // Skip if clicked on add-sub button or responsive expand control
                if (
                    $(e.target).closest(".add-sub-equipment").length ||
                    $(e.target).closest("td.dtr-control").length
                ) {
                    return;
                }

                var data = $("#equipmentTable").DataTable().row(this).data();

                if (data) {
                    $("#equipment_id").val(data.id || "");
                    $("#code").val(data.code || "");
                    $("#item_name").val(data.item_name || "");
                    $("#category").val(data.category || "");
                    $("#serial_number").val(data.serial_number || "");
                    $("#damage").val(data.damage || "");
                    $("#size").val(data.size || "");
                    $("#rent_one_day").val(data.rent_one_day || "0");
                    $("#deposit_one_day").val(data.deposit_one_day || "0");
                    $("#rent_one_month").val(data.rent_one_month || "0");
                    $("#value").val(data.value || "0");
                    $("#quantity").val(data.quantity || "0");

                    // Show update button, hide create button
                    $("#create").hide();
                    $("#update").show();

                    // Close the modal
                    $("#EquipmentModal").modal("hide");
                }
            });

        // Add Sub Equipment button click handler
        $("#equipmentTable tbody")
            .off("click", ".add-sub-equipment")
            .on("click", ".add-sub-equipment", function (e) {
                e.preventDefault();
                e.stopPropagation();

                var parentId = $(this).data("id");

                // Redirect to Sub Equipment Master page
                window.location.href = "sub-equipment-master.php?equipment_id=" + parentId;
            });
    }

    // Create Equipment
    $("#create").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#create").prop("disabled", true);

        // Validation
        var requiredFields = [
            { selector: "#code", message: "Please enter equipment code" },
            { selector: "#item_name", message: "Please enter item name" },
            { selector: "#category", message: "Please select category" },
            { selector: "#serial_number", message: "Please enter serial number" },
            { selector: "#damage", message: "Please enter damage status/notes" },
            { selector: "#size", message: "Please enter size" },
            { selector: "#rent_one_day", message: "Please enter one day's rent" },
            { selector: "#deposit_one_day", message: "Please enter one day's deposit" },
            { selector: "#rent_one_month", message: "Please enter one month's rent" },
            { selector: "#value", message: "Please enter value" },
            { selector: "#quantity", message: "Please enter quantity" }
        ];

        var invalidField = requiredFields.find(function (field) {
            return !$(field.selector).val();
        });

        if (invalidField) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: invalidField.message,
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
                url: "ajax/php/equipment-master.php",
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
                            text: "Equipment added successfully!",
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
                        text: "Failed to create equipment. Please check the console for details.",
                        type: "error",
                        showConfirmButton: true,
                    });
                },
            });
        }

        return false;
    });

    // Update Equipment
    $("#update").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#update").prop("disabled", true);

        var invalidUpdateField = requiredFields.find(function (field) {
            return !$(field.selector).val();
        });

        if (invalidUpdateField) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: invalidUpdateField.message,
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
                url: "ajax/php/equipment-master.php",
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
                            text: "Equipment updated successfully!",
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
                        text: "Failed to update equipment. Please check the console for details.",
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
        $("#equipment_id").val("");
        $("#create").show();
        $("#update").hide();

        // Generate new code
        $.ajax({
            url: "ajax/php/equipment-master.php",
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

    // Delete Equipment
    $(document).on("click", ".delete-equipment", function (e) {
        e.preventDefault();

        // Disable the button to prevent multiple submissions
        $(".delete-equipment").prop("disabled", true);

        var equipmentId = $("#equipment_id").val();
        var itemName = $("#item_name").val();

        if (!equipmentId || equipmentId === "") {
            // Re-enable the button on validation error
            $(".delete-equipment").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select equipment first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        swal(
            {
                title: "Are you sure?",
                text: "Do you want to delete equipment '" + itemName + "'?",
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
                        url: "ajax/php/equipment-master.php",
                        type: "POST",
                        data: {
                            id: equipmentId,
                            delete: true,
                        },
                        dataType: "JSON",
                        success: function (response) {
                            // Hide page preloader
                            $("#page-preloader").hide();

                            // Re-enable the button
                            $(".delete-equipment").prop("disabled", false);

                            if (response.status === "success") {
                                swal({
                                    title: "Deleted!",
                                    text: "Equipment has been deleted.",
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
                    $(".delete-equipment").prop("disabled", false);
                }
            }
        );
    });
});
