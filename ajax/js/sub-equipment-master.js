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
                    // Refresh table with new ID
                    parentEquipmentId = result.id;
                    loadAllSubEquipmentTable();
                }
            },
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
        } else if (!$("#department").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a department",
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
        } else if (!$("#department").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a department",
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

    // Handle search button click to open equipment modal from here
    $("#equipment_search").click(function () {
        // This page doesn't have an equipment search modal trigger usually,
        // but if it did, we'd handle it.
        // Actually, equipment search is usually on equipment-master.php
    });

    // If equipment_id input changes, refresh table
    $("#equipment_id").change(function () {
        parentEquipmentId = $(this).val();
        loadAllSubEquipmentTable();
    });

    // Back to Equipment Master button
    $("#back-to-equipment").click(function (e) {
        e.preventDefault();
        window.location.href = "equipment-master.php";
    });

    function loadAllSubEquipmentTable() {
        // if (!parentEquipmentId) return; // Allow loading all if no equipment selected? 
        // The user says "all of the sub equipment show to main related equipment"
        // Let's show all if no ID, or filter if ID exists.

        var currentEquipmentId = parentEquipmentId || $("#equipment_id").val();

        // Destroy if already initialized
        if ($.fn.DataTable.isDataTable("#allSubEquipmentTable")) {
            $("#allSubEquipmentTable").DataTable().destroy();
        }

        $("#allSubEquipmentTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/sub-equipment-master.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                    d.equipment_id = currentEquipmentId;
                },
                dataSrc: function (json) {
                    return json.data;
                },
                error: function (xhr) {
                    console.error("Table Load Error:", xhr.responseText);
                }
            },
            columns: [
                { data: "key", title: "#ID" },
                { data: "code", title: "Code" },
                { data: "department_name", title: "Department" },
                { data: "qty", title: "Qty" },
                {
                    data: "rental_status",
                    render: function (data) {
                        if (!data) return '<span class="badge bg-secondary">UNKNOWN</span>';
                        var badgeClass = 'bg-secondary';
                        var label = data.toUpperCase();

                        if (data === 'available' || data === 'returned') {
                            badgeClass = 'bg-success';
                            label = 'AVAILABLE';
                        } else if (data === 'rent' || data === 'rented') {
                            badgeClass = 'bg-primary';
                            label = 'RENTED';
                        } else if (data === 'damage') {
                            badgeClass = 'bg-danger';
                            label = 'DAMAGED';
                        } else if (data === 'repair') {
                            badgeClass = 'bg-warning';
                            label = 'REPAIR';
                        }

                        return '<span class="badge ' + badgeClass + '">' + label + '</span>';
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return '<button class="btn btn-sm btn-info edit-sub" data-id="' + data.id + '"><i class="uil uil-edit"></i></button>';
                    }
                }
            ],
            order: [[0, "desc"]],
            pageLength: 50,
        });

        // Row click event for edit button
        $("#allSubEquipmentTable").off("click", ".edit-sub").on("click", ".edit-sub", function (e) {
            e.preventDefault();
            var data = $("#allSubEquipmentTable").DataTable().row($(this).closest('tr')).data();
            if (data) {
                $("#sub_equipment_id").val(data.id || "");
                $("#code").val(data.code || "");
                $("#department").val(data.department_id || "");
                $("#qty").val(data.qty || "0");
                $("#rental_status").val(data.rental_status || "available");
                $("#create").hide();
                $("#update").show();
                // Scroll to top
                $("html, body").animate({ scrollTop: 0 }, "slow");
            }
        });
    }
});
