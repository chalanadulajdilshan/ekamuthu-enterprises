jQuery(document).ready(function () {
    // Get equipment_id from URL
    var urlParams = new URLSearchParams(window.location.search);
    var parentEquipmentId = urlParams.get("equipment_id");

    // Load parent equipment info
    if (parentEquipmentId) {
        $.ajax({
            url: "ajax/php/sub-equipment-master.php",
            type: "POST",
            data: {
                action: "get_equipment_info",
                equipment_id: parentEquipmentId,
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#parent_equipment_id").val(result.id);
                    $("#parent_equipment_display").val(
                        result.code + " - " + result.item_name
                    );
                    $("#equipment_id").val(result.id);
                }
            },
        });
    }

    // Load Sub Equipment Table when modal opens
    $("#SubEquipmentModal").on("shown.bs.modal", function () {
        loadSubEquipmentTable();
    });

    function loadSubEquipmentTable() {
        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable("#subEquipmentTable")) {
            $("#subEquipmentTable").DataTable().destroy();
        }

        $("#subEquipmentTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/sub-equipment-master.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                    d.equipment_id = parentEquipmentId;
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
                { data: "name", title: "Name" },
            ],
            order: [[0, "desc"]],
            pageLength: 100,
        });

        // Row click event to populate form and close modal
        $("#subEquipmentTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#subEquipmentTable").DataTable().row(this).data();

                if (data) {
                    $("#sub_equipment_id").val(data.id || "");
                    $("#code").val(data.code || "");
                    $("#name").val(data.name || "");

                    // Show update button, hide create button
                    $("#create").hide();
                    $("#update").show();

                    // Close the modal
                    $("#SubEquipmentModal").modal("hide");
                }
            });
    }

    // Create Sub Equipment
    $("#create").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#create").prop("disabled", true);

        // Validation
        if (!$("#code").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter sub equipment code",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#name").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter sub equipment name",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#equipment_id").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Parent equipment is required",
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
                url: "ajax/php/sub-equipment-master.php",
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
                            text: "Sub equipment added successfully!",
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
                        text: "Failed to create sub equipment. Please check the console for details.",
                        type: "error",
                        showConfirmButton: true,
                    });
                },
            });
        }

        return false;
    });

    // Update Sub Equipment
    $("#update").click(function (event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        $("#update").prop("disabled", true);

        if (!$("#code").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter sub equipment code",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#name").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter sub equipment name",
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
                url: "ajax/php/sub-equipment-master.php",
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
                            text: "Sub equipment updated successfully!",
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
                        text: "Failed to update sub equipment. Please check the console for details.",
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
        $("#sub_equipment_id").val("");
        // Keep the parent equipment ID
        $("#equipment_id").val(parentEquipmentId);
        $("#create").show();
        $("#update").hide();

        // Generate new code
        $.ajax({
            url: "ajax/php/sub-equipment-master.php",
            type: "POST",
            data: {
                action: "get_new_code",
                equipment_id: parentEquipmentId,
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#code").val(result.code);
                }
            },
        });
    });

    // Delete Sub Equipment
    $(document).on("click", ".delete-sub-equipment", function (e) {
        e.preventDefault();

        // Disable the button to prevent multiple submissions
        $(".delete-sub-equipment").prop("disabled", true);

        var subEquipmentId = $("#sub_equipment_id").val();
        var subEquipmentCode = $("#code").val();

        if (!subEquipmentId || subEquipmentId === "") {
            // Re-enable the button on validation error
            $(".delete-sub-equipment").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a sub equipment first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        swal(
            {
                title: "Are you sure?",
                text: "Do you want to delete sub equipment '" + subEquipmentCode + "'?",
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
                        url: "ajax/php/sub-equipment-master.php",
                        type: "POST",
                        data: {
                            id: subEquipmentId,
                            delete: true,
                        },
                        dataType: "JSON",
                        success: function (response) {
                            // Hide page preloader
                            $("#page-preloader").hide();

                            // Re-enable the button
                            $(".delete-sub-equipment").prop("disabled", false);

                            if (response.status === "success") {
                                swal({
                                    title: "Deleted!",
                                    text: "Sub equipment has been deleted.",
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
                    $(".delete-sub-equipment").prop("disabled", false);
                }
            }
        );
    });

    // Back to Equipment Master button
    $("#back-to-equipment").click(function (e) {
        e.preventDefault();
        window.location.href = "equipment-master.php";
    });
});
