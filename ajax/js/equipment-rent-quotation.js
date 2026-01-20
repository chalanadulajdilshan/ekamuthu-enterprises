jQuery(document).ready(function () {
    // Store quotation items in memory
    var quotationItems = [];
    var currentRentOneDay = 0;
    var currentRentOneMonth = 0;

    // Calculate amount and return date
    function calculateRentDetails() {
        var rentType = $("#item_rent_type").val();
        var duration = parseFloat($("#item_duration").val()) || 0;
        var rentalDate = $("#item_rental_date").val();

        if (!rentalDate || duration <= 0) {
            $("#item_amount").val("0.00");
            return;
        }

        var amount = 0;
        var returnDate = new Date(rentalDate);

        if (rentType === "day") {
            amount = currentRentOneDay * duration;
            returnDate.setDate(returnDate.getDate() + duration);
            $("#duration_label").text("Days");
        } else {
            amount = currentRentOneMonth * duration;
            returnDate.setMonth(returnDate.getMonth() + duration);
            $("#duration_label").text("Months");
        }

        // Format amount
        $("#item_amount").val(amount.toFixed(2));

        // Format return date YYYY-MM-DD
        var yyyy = returnDate.getFullYear();
        var mm = String(returnDate.getMonth() + 1).padStart(2, '0');
        var dd = String(returnDate.getDate()).padStart(2, '0');
        $("#item_return_date").val(yyyy + "-" + mm + "-" + dd);
    }

    // Calculate duration from return date
    function calculateDurationFromDates() {
        var rentType = $("#item_rent_type").val();
        var rentalDateStr = $("#item_rental_date").val();
        var returnDateStr = $("#item_return_date").val();

        if (!rentalDateStr || !returnDateStr) return;

        var rentalDate = new Date(rentalDateStr);
        var returnDate = new Date(returnDateStr);
        var duration = 0;
        var timeDiff = returnDate - rentalDate;

        if (timeDiff <= 0) {
            $("#item_duration").val(0);
            $("#item_amount").val("0.00");
            return;
        }

        if (rentType === "day") {
            duration = Math.ceil(timeDiff / (1000 * 3600 * 24));
            $("#duration_label").text("Days");
        } else {
            var months = (returnDate.getFullYear() - rentalDate.getFullYear()) * 12;
            months -= rentalDate.getMonth();
            months += returnDate.getMonth();
            if (returnDate.getDate() < rentalDate.getDate()) {
                months--;
            }
            duration = months <= 0 ? 0 : months;
        }

        $("#item_duration").val(duration);

        var amount = 0;
        if (rentType === "day") {
            amount = currentRentOneDay * duration;
        } else {
            amount = currentRentOneMonth * duration;
        }
        $("#item_amount").val(amount.toFixed(2));
    }

    // Update items table display
    function updateItemsTable() {
        var tbody = $("#rentItemsTable tbody");
        tbody.empty();

        if (quotationItems.length === 0) {
            $("#noItemsMessage").show();
            $("#rentItemsTable").hide();
        } else {
            $("#noItemsMessage").hide();
            $("#rentItemsTable").show();

            quotationItems.forEach(function (item, index) {
                var actionBtns =
                    '<button class="btn btn-sm btn-danger remove-item-btn" data-index="' +
                    index +
                    '" title="Remove"><i class="uil uil-trash"></i></button>';

                var row =
                    "<tr>" +
                    "<td>" +
                    (index + 1) +
                    "</td>" +
                    "<td>" +
                    item.equipment_display +
                    "</td>" +

                    "<td>" +
                    item.sub_equipment_display +
                    "</td>" +
                    "<td>" +
                    (item.rent_type === 'month' ? 'Month' : 'Day') +
                    "</td>" +
                    "<td>" +
                    '<span class="badge ' + (item.rent_type === 'month' ? 'bg-primary' : 'bg-info') + '">' + parseFloat(item.duration).toFixed(0) + (item.rent_type === 'month' ? ' Months' : ' Days') + '</span>' +
                    "</td>" +
                    "<td>" +
                    parseFloat(item.amount).toFixed(2) +
                    "</td>" +
                    "<td>" +
                    item.rental_date +
                    "</td>" +
                    "<td>" +
                    (item.return_date || "-") +
                    "</td>" +
                    "<td>" +
                    actionBtns +
                    "</td>" +
                    "</tr>";
                tbody.append(row);
            });
        }
    }

    // Check if sub-equipment already added
    function isSubEquipmentAlreadyAdded(subEquipmentId) {
        return quotationItems.some(function (item) {
            return (
                item.sub_equipment_id == subEquipmentId
            );
        });
    }

    // Add item to list
    $("#addItemBtn").click(function () {
        var equipmentId = $("#item_equipment_id").val();
        var equipmentDisplay = $("#item_equipment_display").val();
        var subEquipmentId = $("#item_sub_equipment_id").val();
        var subEquipmentDisplay = $("#item_sub_equipment_display").val();
        var rentalDate = $("#item_rental_date").val() || $("#rental_date").val();
        var returnDate = $("#item_return_date").val();

        if (!equipmentId) {
            swal({
                title: "Error!",
                text: "Please select an equipment",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }
        if (!subEquipmentId) {
            swal({
                title: "Error!",
                text: "Please select a sub equipment (unit code)",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }
        if (isSubEquipmentAlreadyAdded(subEquipmentId)) {
            swal({
                title: "Error!",
                text: "This sub equipment is already added to the list",
                type: "warning",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        quotationItems.push({
            id: null,
            equipment_id: equipmentId,
            equipment_display: equipmentDisplay,
            sub_equipment_id: subEquipmentId,
            sub_equipment_display: subEquipmentDisplay,
            rental_date: rentalDate,
            return_date: returnDate,
            rent_type: $("#item_rent_type").val(),
            duration: $("#item_duration").val(),
            amount: $("#item_amount").val(),
            status: "pending",
            remark: "",
        });

        updateItemsTable();

        // Clear item inputs
        $("#item_sub_equipment_id").val("");
        $("#item_sub_equipment_display").val("");
        $("#item_return_date").val("");
        $("#item_duration").val("");
        $("#item_amount").val("");
    });

    // Calculate on input changes
    $("#item_rent_type, #item_duration, #item_rental_date").on("change keyup", function () {
        calculateRentDetails();
    });

    // Calculate when return date changes
    $("#item_return_date").on("change keyup", function () {
        calculateDurationFromDates();
    });

    // Remove item from list
    $(document).on("click", ".remove-item-btn", function () {
        var index = $(this).data("index");
        quotationItems.splice(index, 1);
        updateItemsTable();
    });

    // Load Quotation Table when modal opens
    $("#QuotationModal").on("shown.bs.modal", function () {
        loadQuotationTable();
    });

    function loadQuotationTable() {
        if ($.fn.DataTable.isDataTable("#quotationTable")) {
            $("#quotationTable").DataTable().destroy();
        }

        $("#quotationTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-quotation.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                },
                dataSrc: function (json) {
                    return json.data;
                },
                error: function (xhr) {
                    console.error("Server Error:", xhr.responseText);
                },
            },
            columns: [
                { data: "id", title: "#ID" },
                { data: "quotation_number", title: "Quotation Number" },
                { data: "customer_name", title: "Customer" },
                { data: "rental_date", title: "Rental Date" },
                { data: "total_items", title: "Items" },
                { data: "status_label", title: "Status" },
            ],
            order: [[0, "desc"]],
            pageLength: 100,
        });

        // Row click to load quotation details
        $("#quotationTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#quotationTable").DataTable().row(this).data();
                if (data) {
                    loadQuotationDetails(data.id);
                    $("#QuotationModal").modal("hide");
                }
            });
    }

    // Load quotation details including items
    function loadQuotationDetails(quotationId) {
        $.ajax({
            url: "ajax/php/equipment-rent-quotation.php",
            type: "POST",
            data: { action: "get_quotation_details", quotation_id: quotationId },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    var quotation = result.quotation;
                    $("#quotation_id").val(quotation.id);
                    $("#quotation_number").val(quotation.quotation_number);
                    $("#customer_id").val(quotation.customer_id);
                    $("#customer_display").val(quotation.customer_name);
                    $("#rental_date").val(quotation.rental_date);
                    $("#remark").val(quotation.remark || "");

                    // Load items
                    quotationItems = result.items.map(function (item) {
                        return {
                            id: item.id,
                            equipment_id: item.equipment_id,
                            equipment_display:
                                (item.equipment_code || "") +
                                " - " +
                                (item.equipment_name || ""),
                            sub_equipment_id: item.sub_equipment_id,
                            sub_equipment_display: item.sub_equipment_code || "",
                            rental_date: item.rental_date,
                            return_date: item.return_date,
                            rent_type: item.rent_type,
                            duration: item.duration,
                            amount: item.amount,
                            quantity: item.quantity,
                            status: item.status,
                            remark: item.remark,
                        };
                    });
                    updateItemsTable();

                    $("#create").hide();
                    $("#update").show();
                    $("#print").show();
                }
            },
        });
    }

    // Load Customer Table
    $("#CustomerSelectModal").on("shown.bs.modal", function () {
        loadCustomerTable();
    });

    function loadCustomerTable() {
        if ($.fn.DataTable.isDataTable("#customerSelectTable")) {
            $("#customerSelectTable").DataTable().destroy();
        }

        $("#customerSelectTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-quotation.php",
                type: "POST",
                data: function (d) {
                    d.filter_customers = true;
                },
                dataSrc: function (json) {
                    return json.data;
                },
            },
            columns: [
                { data: "id", title: "#ID" },
                { data: "code", title: "Code" },
                { data: "name", title: "Name" },
                { data: "mobile_number", title: "Mobile" },
                { data: "nic", title: "NIC" },
                { data: "address", title: "Address" },
                { data: "outstanding", title: "Outstanding" },
            ],
            order: [[2, "asc"]],
            pageLength: 50,
        });

        $("#customerSelectTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#customerSelectTable").DataTable().row(this).data();
                if (data) {
                    $("#customer_id").val(data.id);
                    $("#customer_display").val(data.code + " - " + data.name);
                    $("#CustomerSelectModal").modal("hide");
                }
            });
    }

    // Load Equipment Table
    $("#EquipmentSelectModal").on("shown.bs.modal", function () {
        loadEquipmentTable();
    });

    function loadEquipmentTable() {
        if ($.fn.DataTable.isDataTable("#equipmentSelectTable")) {
            $("#equipmentSelectTable").DataTable().destroy();
        }

        $("#equipmentSelectTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-quotation.php",
                type: "POST",
                data: function (d) {
                    d.filter_equipment = true;
                },
                dataSrc: function (json) {
                    return json.data;
                },
            },
            columns: [
                { data: "key", title: "#" },
                { data: "code", title: "Code" },
                { data: "item_name", title: "Item Name" },
                { data: "category_label", title: "Category" },
                { data: "availability_label", title: "Availability" },
            ],
            order: [[2, "asc"]],
            pageLength: 50,
        });

        $("#equipmentSelectTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#equipmentSelectTable").DataTable().row(this).data();
                if (data) {
                    // Warning for quotes if not available, but allow it? 
                    // Probably better to warn but allow since it's a quote.
                    // For now, mirroring rent behavior, but without strict block.
                    if (data.available_sub <= 0) {
                        swal({
                            title: "Availability Warning",
                            text: "All units of this equipment are currently rented out. You can still create a quotation.",
                            type: "warning",
                            showConfirmButton: true,
                        });
                    }

                    $("#item_equipment_id").val(data.id);
                    $("#item_equipment_display").val(data.code + " - " + data.item_name);

                    // Set rates
                    currentRentOneDay = parseFloat(data.rent_one_day) || 0;
                    currentRentOneMonth = parseFloat(data.rent_one_month) || 0;

                    // Reset calculations
                    $("#item_duration").val("");
                    $("#item_amount").val("");

                    // Clear sub equipment when equipment changes
                    $("#item_sub_equipment_id").val("");
                    $("#item_sub_equipment_display").val("");
                    $("#EquipmentSelectModal").modal("hide");
                }
            });
    }

    // Load Sub Equipment Table
    $("#SubEquipmentSelectModal").on("shown.bs.modal", function () {
        loadSubEquipmentTable();
    });

    function loadSubEquipmentTable() {
        var equipmentId = $("#item_equipment_id").val();

        if (!equipmentId) {
            $("#noSubEquipmentMsg").show();
            if ($.fn.DataTable.isDataTable("#subEquipmentSelectTable")) {
                $("#subEquipmentSelectTable").DataTable().destroy();
            }
            $("#subEquipmentSelectTable").hide();
            return;
        }

        $("#noSubEquipmentMsg").hide();
        $("#subEquipmentSelectTable").show();

        if ($.fn.DataTable.isDataTable("#subEquipmentSelectTable")) {
            $("#subEquipmentSelectTable").DataTable().destroy();
        }

        $("#subEquipmentSelectTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-quotation.php",
                type: "POST",
                data: function (d) {
                    d.filter_sub_equipment = true;
                    d.equipment_id = equipmentId;
                },
                dataSrc: function (json) {
                    if (json.data.length === 0) {
                        $("#noSubEquipmentMsg").show();
                    }
                    return json.data;
                },
            },
            columns: [
                { data: "key", title: "#" },
                { data: "code", title: "Code" },
                { data: "status_label", title: "Status" },
            ],
            order: [[1, "asc"]],
            pageLength: 50,
        });

        $("#subEquipmentSelectTable tbody")
            .off("click")
            .on("click", "tr", function () {
                var data = $("#subEquipmentSelectTable").DataTable().row(this).data();
                if (data) {
                    if (isSubEquipmentAlreadyAdded(data.id)) {
                        swal({
                            title: "Already Added!",
                            text: "This unit is already in your quotation list.",
                            type: "warning",
                            showConfirmButton: true,
                        });
                        return;
                    }
                    $("#item_sub_equipment_id").val(data.id);
                    $("#item_sub_equipment_display").val(data.code);
                    $("#SubEquipmentSelectModal").modal("hide");
                }
            });
    }

    // Create Quotation
    $("#create").click(function (event) {
        event.preventDefault();
        $("#create").prop("disabled", true);

        if (!$("#quotation_number").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter quotation number",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        }
        if (!$("#customer_id").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a customer",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        }
        if (!$("#rental_date").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter rental date",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        }
        if (quotationItems.length === 0) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please add at least one equipment item",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        }

        $("#page-preloader").show();

        var formData = new FormData($("#form-data")[0]);
        formData.append("create", true);
        formData.append("items", JSON.stringify(quotationItems));

        $.ajax({
            url: "ajax/php/equipment-rent-quotation.php",
            type: "POST",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function (result) {
                $("#page-preloader").hide();
                $("#create").prop("disabled", false);

                if (result.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Quotation created successfully!",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Something went wrong.",
                        type: "error",
                        showConfirmButton: true,
                    });
                }
            },
            error: function (xhr) {
                $("#page-preloader").hide();
                $("#create").prop("disabled", false);
                console.error("Error:", xhr.responseText);
                swal({
                    title: "Error!",
                    text: "Failed to create quotation.",
                    type: "error",
                    showConfirmButton: true,
                });
            },
        });
        return false;
    });

    // Update Quotation
    $("#update").click(function (event) {
        event.preventDefault();
        $("#update").prop("disabled", true);

        if (!$("#customer_id").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a customer",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        }
        if (quotationItems.length === 0) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please add at least one equipment item",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        }

        $("#page-preloader").show();

        var formData = new FormData($("#form-data")[0]);
        formData.append("update", true);
        formData.append("items", JSON.stringify(quotationItems));

        $.ajax({
            url: "ajax/php/equipment-rent-quotation.php",
            type: "POST",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function (result) {
                $("#page-preloader").hide();
                $("#update").prop("disabled", false);

                if (result.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Quotation updated successfully!",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Something went wrong.",
                        type: "error",
                        showConfirmButton: true,
                    });
                }
            },
            error: function (xhr) {
                $("#page-preloader").hide();
                $("#update").prop("disabled", false);
                console.error("Error:", xhr.responseText);
                swal({
                    title: "Error!",
                    text: "Failed to update quotation.",
                    type: "error",
                    showConfirmButton: true,
                });
            },
        });
        return false;
    });

    // Reset form - New button
    $("#new").click(function (e) {
        e.preventDefault();
        $("#form-data")[0].reset();
        $("#quotation_id").val("");
        $("#customer_id").val("");
        $("#customer_display").val("");
        $("#item_equipment_id").val("");
        $("#item_equipment_display").val("");
        $("#item_sub_equipment_id").val("");
        $("#item_sub_equipment_display").val("");
        quotationItems = [];
        updateItemsTable();
        $("#create").show();
        $("#update").hide();
        $("#print").hide();

        $.ajax({
            url: "ajax/php/equipment-rent-quotation.php",
            type: "POST",
            data: { action: "get_new_code" },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#quotation_number").val(result.code);
                }
            },
        });
    });

    // Delete Quotation
    $(document).on("click", ".delete-quotation", function (e) {
        e.preventDefault();
        $(".delete-quotation").prop("disabled", true);

        var quotationId = $("#quotation_id").val();
        var quotationCode = $("#quotation_number").val();

        if (!quotationId) {
            $(".delete-quotation").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please select a quotation record first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        swal(
            {
                title: "Are you sure?",
                text:
                    "Do you want to delete quotation '" +
                    quotationCode +
                    "'?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
            },
            function (isConfirm) {
                if (isConfirm) {
                    $("#page-preloader").show();
                    $.ajax({
                        url: "ajax/php/equipment-rent-quotation.php",
                        type: "POST",
                        data: { id: quotationId, delete: true },
                        dataType: "JSON",
                        success: function (response) {
                            $("#page-preloader").hide();
                            $(".delete-quotation").prop("disabled", false);

                            if (response.status === "success") {
                                swal({
                                    title: "Deleted!",
                                    text: "Quotation has been deleted.",
                                    type: "success",
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                                setTimeout(function () {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                swal({
                                    title: "Error!",
                                    text: "Something went wrong.",
                                    type: "error",
                                    showConfirmButton: true,
                                });
                            }
                        },
                    });
                } else {
                    $(".delete-quotation").prop("disabled", false);
                }
            },
        );
    });

    // Print Quotation
    $("#print").click(function (e) {
        e.preventDefault();
        var id = $("#quotation_id").val();
        if (id) {
            window.open("equipment-rent-quotation-print.php?id=" + id, "_blank");
        }
    });

    // Sync item rental date with master rental date
    $("#rental_date").on("change", function () {
        $("#item_rental_date").val($(this).val());
    });
});
