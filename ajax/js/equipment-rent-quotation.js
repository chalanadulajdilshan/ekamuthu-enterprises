jQuery(document).ready(function () {
    // Store quotation items in memory
    var quotationItems = [];
    var currentRentOneDay = 0;
    var currentRentOneMonth = 0;
    var transportCost = 0;
    var depositTotal = 0;

    function formatAmount(num) {
        var val = parseFloat(num || 0);
        if (isNaN(val)) val = 0;
        return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function parseAmount(val) {
        if (typeof val === "number") return val;
        if (!val) return 0;
        return parseFloat(String(val).replace(/,/g, "")) || 0;
    }

    // Calculate amount and return date
    function calculateRentDetails() {
        var rentType = $("#item_rent_type").val();
        var duration = parseFloat($("#item_duration").val()) || 0;
        var qty = parseFloat($("#item_qty").val()) || 1;
        var rentalDate = $("#item_rental_date").val();

        if (!rentalDate || duration < 0) {
            $("#item_amount").val("0.00");
            return;
        }

        var amount = 0;
        var returnDate = new Date(rentalDate);

        if (rentType === "day") {
            amount = currentRentOneDay * duration * qty;
            returnDate.setDate(returnDate.getDate() + duration);
            $("#duration_label").text("Days");
        } else {
            amount = currentRentOneMonth * duration * qty;
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
        var qty = parseFloat($("#item_qty").val()) || 1;
        if (rentType === "day") {
            amount = currentRentOneDay * duration * qty;
        } else {
            amount = currentRentOneMonth * duration * qty;
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
                    parseFloat(item.quantity || 1).toFixed(0) +
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

        // Update summary section if available
        updateSummary();
    }

    function updateSummary() {
        var itemsTotal = quotationItems.reduce(function (sum, item) {
            return sum + (parseFloat(item.amount) || 0);
        }, 0);
        transportCost = parseAmount($("#transport_cost").val());
        depositTotal = parseAmount($("#deposit_total").val());

        var netTotal = itemsTotal + transportCost + depositTotal;

        if ($("#summary_items_total").length) {
            $("#summary_items_total").text(formatAmount(itemsTotal));
        }
        if ($("#summary_transport").length) {
            $("#summary_transport").text(formatAmount(transportCost));
        }
        if ($("#summary_deposit").length) {
            $("#summary_deposit").text(formatAmount(depositTotal));
        }
        if ($("#summary_net_total").length) {
            $("#summary_net_total").text(formatAmount(netTotal));
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
        var noSubItems = $("#item_equipment_id").data("no_sub_items") == 1;

        if (!equipmentId || (!noSubItems && !$("#item_sub_equipment_id").val())) {
            swal({
                title: "Error!",
                text: noSubItems ? "Please select an equipment" : "Please select a sub equipment (unit code)",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }
        if (noSubItems) {
            $("#item_sub_equipment_id").val(0);
            $("#item_sub_equipment_display").val("N/A");
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
            quantity: $("#item_qty").val(),
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
        $("#item_qty").val(1);
        $("#item_amount").val("");
    });

    // Calculate on input changes
    $("#item_rent_type, #item_duration, #item_qty, #item_rental_date").on("change keyup", function () {
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

    // Transport / Deposit inputs live format and summary update
    $("#transport_cost, #deposit_total").on("input", function () {
        var formatted = $(this)
            .val()
            .replace(/[^0-9.,-]/g, "")
            .replace(/(,)(?=.*,)/g, "");
        $(this).val(formatted);
        updateSummary();
    });

    $("#transport_cost, #deposit_total").on("blur", function () {
        $(this).val(formatAmount(parseAmount($(this).val())));
        updateSummary();
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
                    $("#customer_name").val(quotation.customer_name);
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
                    $("#customer_name").val(data.code + " - " + data.name);
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

        var equipmentTable = $("#equipmentSelectTable").DataTable({
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
                {
                    data: null,
                    title: "",
                    className: "details-control",
                    orderable: false,
                    defaultContent:
                        '<span class="mdi mdi-plus-circle-outline text-primary" style="font-size:18px; cursor:pointer;"></span>',
                    width: "30px",
                },
                { data: "key", title: "#" },
                {
                    data: "image_name",
                    title: "Image",
                    orderable: false,
                    render: function (data, type, row) {
                        var imgSrc = data ? "uploads/equipment/" + data : "assets/images/no-image.png";
                        return `<img src="${imgSrc}" alt="Img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">`;
                    },
                },
                { data: "code", title: "Code" },
                {
                    data: "item_name",
                    title: "Item Name",
                    render: function (data, type, row) {
                        return `
                            <div class="fw-bold">${data}</div>
                            <div class="text-muted small">
                                <span class="fw-bold text-primary">SN -</span> <span class="text-danger">${row.serial_number || "-"}</span> |
                                <span class="fw-bold text-primary">Deposit -</span> <span class="text-danger">Rs. ${row.deposit_one_day || "0.00"}</span> |
                                <span class="fw-bold text-primary">Size -</span> <span class="text-danger">${row.size || "-"}</span> |
                                <span class="fw-bold text-primary">Category -</span> <span class="text-danger">${row.category_label || "-"}</span>
                            </div>
                        `;
                    },
                },
            ],
            order: [[3, "asc"]],
            pageLength: 50,
        });

        // Function to format expandable row details
        function formatEquipmentDetails(row) {
            var d = row.data();
            var availabilityBadge = '';
            if (d.no_sub_items == 1) {
                availabilityBadge = '<span class="badge bg-secondary">No Sub Items</span>';
            } else if (d.available_sub > 0) {
                availabilityBadge = '<span class="badge bg-success">' + d.available_sub + '/' + d.total_sub + ' Available</span>';
            } else {
                availabilityBadge = '<span class="badge bg-danger">All Rented</span>';
            }

            return `
                <div class="p-3 bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="fw-bold" style="width: 140px;">Serial Number:</td><td>${d.serial_number || '-'}</td></tr>
                                <tr><td class="fw-bold">Size:</td><td>${d.size || '-'}</td></tr>
                                <tr><td class="fw-bold">Category:</td><td>${d.category_label || '-'}</td></tr>
                                <tr><td class="fw-bold">Availability:</td><td>${availabilityBadge}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="fw-bold" style="width: 140px;">Rent (1 Day):</td><td>Rs. ${parseFloat(d.rent_one_day || 0).toFixed(2)}</td></tr>
                                <tr><td class="fw-bold">Rent (1 Month):</td><td>Rs. ${parseFloat(d.rent_one_month || 0).toFixed(2)}</td></tr>
                                <tr><td class="fw-bold">Deposit:</td><td>Rs. ${parseFloat(d.deposit_one_day || 0).toFixed(2)}</td></tr>
                                <tr><td class="fw-bold">Value:</td><td>Rs. ${parseFloat(d.value || 0).toFixed(2)}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // Handle expand/collapse on + icon click
        $("#equipmentSelectTable tbody").off("click", "td.details-control").on("click", "td.details-control", function (e) {
            e.stopPropagation(); // Prevent row click from firing
            var tr = $(this).closest("tr");
            var row = equipmentTable.row(tr);
            var icon = tr.find("td.details-control span.mdi");

            if (row.child.isShown()) {
                // Close
                row.child.hide();
                tr.removeClass("shown");
                icon.removeClass("mdi-minus-circle-outline").addClass("mdi-plus-circle-outline");
            } else {
                // Open
                row.child(formatEquipmentDetails(row)).show();
                tr.addClass("shown");
                icon.removeClass("mdi-plus-circle-outline").addClass("mdi-minus-circle-outline");
            }
        });

        // Handle row click to select equipment (exclude details-control column)
        $("#equipmentSelectTable tbody")
            .off("click", "tr")
            .on("click", "tr", function (e) {
                // Skip if clicked on expand/collapse icon
                if ($(e.target).closest("td.details-control").length) {
                    return;
                }

                var data = equipmentTable.row(this).data();
                if (data) {
                    // Warning for quotes if not available, but allow it
                    if (data.available_sub <= 0 && data.no_sub_items != 1) {
                        swal({
                            title: "Availability Warning",
                            text: "All units of this equipment are currently rented out. You can still create a quotation.",
                            type: "warning",
                            showConfirmButton: true,
                        });
                    }

                    var noSubItems = data.no_sub_items == 1;

                    $("#item_equipment_id").val(data.id).data("no_sub_items", noSubItems ? 1 : 0);
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

                    // Toggle sub-equipment controls based on availability
                    if (noSubItems) {
                        $("#item_sub_equipment_display")
                            .prop("disabled", true)
                            .attr("placeholder", "Not Required");
                        $("#btn-select-sub-equipment").prop("disabled", true);
                        $("#item_qty").prop("readonly", false);
                    } else {
                        $("#item_sub_equipment_display")
                            .prop("disabled", false)
                            .attr("placeholder", "Select sub equipment");
                        $("#btn-select-sub-equipment").prop("disabled", false);
                        $("#item_qty").prop("readonly", true).val(1);
                    }

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
        var noSubItems = $("#item_equipment_id").data("no_sub_items") == 1;

        if (!equipmentId || noSubItems) {
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
        if (!$("#customer_name").val()) {
            $("#create").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter customer name",
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
        formData.append("transport_cost", parseAmount($("#transport_cost").val()) || 0);
        formData.append("deposit_total", parseAmount($("#deposit_total").val()) || 0);

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

        if (!$("#customer_name").val()) {
            $("#update").prop("disabled", false);
            swal({
                title: "Error!",
                text: "Please enter customer name",
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
        formData.append("transport_cost", parseAmount($("#transport_cost").val()) || 0);
        formData.append("deposit_total", parseAmount($("#deposit_total").val()) || 0);

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
