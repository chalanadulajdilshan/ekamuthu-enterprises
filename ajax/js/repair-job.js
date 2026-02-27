jQuery(document).ready(function () {
    // Store repair items in memory
    var repairItems = [];
    var isJobCodeDuplicate = false;

    // Calculate item total
    function calculateItemTotal() {
        var qty = parseFloat($("#repair_item_qty").val()) || 0;
        var price = parseFloat($("#repair_item_price").val()) || 0;
        var total = qty * price;
        $("#repair_item_total").val(total.toFixed(2));
    }

    // Calculate grand total
    function calculateGrandTotal() {
        var total = 0;
        repairItems.forEach(function (item) {
            total += parseFloat(item.total_price) || 0;
        });

        // Add repair charge
        var repairCharge = parseFloat($("#repair_charge").val()) || 0;
        var grandTotal = total + repairCharge;

        // Calculate commission
        var commissionPercentage = parseFloat($("#commission_percentage").val()) || 0;
        var commissionAmount = repairCharge * (commissionPercentage / 100);

        $("#commission_amount").val(commissionAmount.toFixed(2));
        $("#grand_total").text(total.toFixed(2));
        $("#total_cost_display").val(grandTotal.toFixed(2));
        return grandTotal;
    }

    // Update repair items table
    function updateRepairItemsTable() {
        var tbody = $("#repairItemsTable tbody");
        tbody.empty();

        if (repairItems.length === 0) {
            $("#noRepairItemsMessage").show();
            $("#repairItemsTable").hide();
        } else {
            $("#noRepairItemsMessage").hide();
            $("#repairItemsTable").show();

            repairItems.forEach(function (item, index) {
                var row = "<tr>" +
                    "<td>" + (index + 1) + "</td>" +
                    "<td>" + item.item_name + "</td>" +
                    "<td>" + item.quantity + "</td>" +
                    "<td>" + parseFloat(item.unit_price).toFixed(2) + "</td>" +
                    "<td>" + parseFloat(item.total_price).toFixed(2) + "</td>" +
                    "<td><button class='btn btn-sm btn-danger remove-repair-item' data-index='" + index + "'><i class='uil uil-trash'></i></button></td>" +
                    "</tr>";
                tbody.append(row);
            });
        }

        calculateGrandTotal();
    }

    // Show/hide repair items section based on status
    function toggleRepairItemsSection() {
        var status = $("#job_status").val();
        if (status === "in_progress" || status === "completed" || status === "cannot_repair") {
            $("#repair-items-card").slideDown();
        } else {
            $("#repair-items-card").slideUp();
        }
    }

    // Check job code uniqueness
    function checkJobCodeDuplicate(jobCode) {
        isJobCodeDuplicate = false;
        $("#job_code").removeClass("is-invalid");

        if (!jobCode) {
            return;
        }

        $.ajax({
            url: "ajax/php/repair-job.php",
            type: "POST",
            data: { action: "check_duplicate_code", job_code: jobCode },
            dataType: "JSON",
            success: function (res) {
                if (res.status === "success" && res.duplicate) {
                    isJobCodeDuplicate = true;
                    $("#job_code").addClass("is-invalid");
                    swal({
                        title: "Duplicate Entry!",
                        text: "This job code already exists. Please use a different code.",
                        type: "warning",
                        showConfirmButton: true
                    });
                }
            },
            error: function () {
                // Fail silently; validation on submit will still run
            }
        });
    }

    $("#job_code").on("change blur", function () {
        var code = $(this).val().trim();
        checkJobCodeDuplicate(code);
    });

    // Show/hide machine CODE section and customer info based on item type
    function toggleMachineSection() {
        var itemType = $("input[name='item_type']:checked").val();
        if (itemType === "company") {
            $("#machine_code_section").slideDown();
            $("#customer_contact_section").slideUp();
            // Clear customer fields when company item is selected
            $("#customer_name, #customer_phone, #customer_address").val("");
            $("#machine_code").prop("readonly", true);
        } else {
            $("#machine_code_section").slideUp();
            $("#machine_code").val("");
            $("#customer_contact_section").slideDown();
            $("#machine_code").prop("readonly", false);
        }
    }

    // Show/hide outsource contact section and toggle commission fields
    function toggleOutsourceSection() {
        if ($("#is_outsource").is(":checked")) {
            $("#outsource_contact_section").slideDown();

            // Disable commission fields and set to 0
            $("#commission_percentage").val(0).prop("readonly", true);
            $("#commission_amount").val("0.00").prop("readonly", true);
        } else {
            $("#outsource_contact_section").slideUp();
            // Clear outsource fields when unchecked
            $("#outsource_name, #outsource_phone, #outsource_address").val("");
            $("#outsource_cost").val("0.00");

            // Enable commission fields and restore default
            $("#commission_percentage").val(15).prop("readonly", false);
            $("#commission_amount").prop("readonly", true); // Amount is calc-only but percentage is editable
        }
        calculateGrandTotal();
    }

    // Event: Calculate item total on input change
    $("#repair_item_qty, #repair_item_price").on("change keyup", function () {
        calculateItemTotal();
    });

    // Event: Calculate grand total on repair charge or commission change
    $("#repair_charge, #commission_percentage").on("change keyup", function () {
        calculateGrandTotal();
    });

    // Event: Toggle repair items section on status change
    $("#job_status").on("change", function () {
        toggleRepairItemsSection();
    });

    // Event: Toggle machine section on item type change
    $("input[name='item_type']").on("change", function () {
        toggleMachineSection();
    });

    // Event: Toggle outsource section on checkbox change
    $("#is_outsource").on("change", function () {
        toggleOutsourceSection();
    });

    // Event: Add repair item
    $("#addRepairItemBtn").click(function () {
        var itemName = $("#repair_item_name").val().trim();
        var qty = parseFloat($("#repair_item_qty").val()) || 0;
        var price = parseFloat($("#repair_item_price").val()) || 0;
        var total = qty * price;

        if (!itemName) {
            swal({
                title: "Error!",
                text: "Please enter item name",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        if (qty <= 0) {
            swal({
                title: "Error!",
                text: "Quantity must be greater than 0",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        repairItems.push({
            item_name: itemName,
            quantity: qty,
            unit_price: price,
            total_price: total
        });

        updateRepairItemsTable();

        // Clear inputs
        $("#repair_item_name").val("");
        $("#repair_item_qty").val(1);
        $("#repair_item_price").val("0.00");
        $("#repair_item_total").val("0.00");
    });

    // Event: Remove repair item
    $(document).on("click", ".remove-repair-item", function () {
        var index = $(this).data("index");
        repairItems.splice(index, 1);
        updateRepairItemsTable();
    });

    // Load Repair Job Table in Modal
    $("#RepairJobModal").on("shown.bs.modal", function () {
        loadRepairJobTable();
    });

    function loadRepairJobTable() {
        if ($.fn.DataTable.isDataTable("#repairJobTable")) {
            $("#repairJobTable").DataTable().destroy();
        }

        $("#repairJobTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/repair-job.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                },
                dataSrc: function (json) {
                    return json.data;
                }
            },
            columns: [
                { data: "id", title: "#ID" },
                { data: "job_code", title: "Job Code" },
                { data: "item_type", title: "Item Type" },
                { data: "customer_name", title: "Customer" },
                { data: "customer_phone", title: "Phone" },
                { data: "status_label", title: "Status" },
                { data: "total_cost", title: "Total Cost" }
            ],
            order: [[0, "desc"]],
            pageLength: 50
        });

        // Row click to load job
        $("#repairJobTable tbody").off("click").on("click", "tr", function () {
            var data = $("#repairJobTable").DataTable().row(this).data();
            if (data) {
                loadJobDetails(data.id);
                $("#RepairJobModal").modal("hide");
            }
        });
    }

    // Load job details
    function loadJobDetails(jobId) {
        $.ajax({
            url: "ajax/php/repair-job.php",
            type: "POST",
            data: { action: "get_job_details", job_id: jobId },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    var job = result.job;
                    $("#job_id").val(job.id);
                    $("#job_code").val(job.job_code);
                    $("input[name='item_type'][value='" + job.item_type + "']").prop("checked", true).trigger('change');
                    $("#machine_code").val(job.machine_code || "");
                    $("#machine_name").val(job.machine_name || "");
                    $("#customer_name").val(job.customer_name || "");
                    $("#customer_address").val(job.customer_address || "");
                    $("#customer_phone").val(job.customer_phone || "");
                    $("#item_breakdown_date").val(job.item_breakdown_date || "");
                    $("#technical_issue").val(job.technical_issue || "");
                    $("#job_status").val(job.job_status || "pending").trigger('change');
                    $("#repair_charge").val(parseFloat(job.repair_charge || 0).toFixed(2)).trigger('change');
                    $("#commission_percentage").val(parseFloat(job.commission_percentage || 15).toFixed(2)).trigger('change');
                    $("#commission_amount").val(parseFloat(job.commission_amount || 0).toFixed(2));
                    $("#employee_id").val(job.employee_id || "");
                    $("#is_outsource").prop("checked", job.is_outsource == 1);
                    $("#outsource_name").val(job.outsource_name || "");
                    $("#outsource_address").val(job.outsource_address || "");
                    $("#outsource_address").val(job.outsource_address || "");
                    $("#outsource_phone").val(job.outsource_phone || "");
                    $("#outsource_cost").val(parseFloat(job.outsource_cost || 0).toFixed(2));
                    $("#remark").val(job.remark || "");
                    $("#total_cost_display").val(parseFloat(job.total_cost || 0).toFixed(2));

                    $("#total_cost_display").val(parseFloat(job.total_cost || 0).toFixed(2));

                    // Toggle sections based on loaded data
                    toggleMachineSection();
                    toggleOutsourceSection();

                    // Load items
                    repairItems = result.items.map(function (item) {
                        return {
                            id: item.id,
                            item_name: item.item_name,
                            quantity: item.quantity,
                            unit_price: item.unit_price,
                            total_price: item.total_price
                        };
                    });
                    updateRepairItemsTable();
                    toggleRepairItemsSection();

                    $("#create").hide();
                    $("#update").show();
                    $(".delete-job").show();
                    $("#print-job").show();

                    // Handle delivery button visibility
                    // Logic: Only show if status is completed or cannot_repair or delivered
                    // If status is 'delivered', show Undo. Else show Mark Delivered.
                    if (job.job_status === 'completed' || job.job_status === 'cannot_repair' || job.job_status === 'delivered') {
                        if (job.job_status === 'delivered') {
                            $("#mark-delivered").hide();
                            $("#mark-undelivered").show();
                        } else {
                            $("#mark-delivered").show();
                            $("#mark-undelivered").hide();
                        }
                    } else {
                        $("#mark-delivered").hide();
                        $("#mark-undelivered").hide();
                    }
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Failed to load job details",
                        type: "error"
                    });
                }
            },
            error: function () {
                swal({
                    title: "Error!",
                    text: "Failed to load job details",
                    type: "error"
                });
            }
        });
    }

    // Expose loadJobDetails globally
    window.loadJobDetails = loadJobDetails;

    // Validate Form
    function validateForm() {
        var isValid = true;
        var errorMessage = "";

        // Check required fields
        if ($("#job_code").val().trim() === "") {
            errorMessage = "Job Code is required";
            isValid = false;
        } else if (isJobCodeDuplicate) {
            errorMessage = "Job Code already exists. Please use a different code";
            isValid = false;
            $("#job_code").addClass("is-invalid");
        } else if ($("input[name='item_type']:checked").val() === "company" && $("#machine_name").val().trim() === "") {
            errorMessage = "Machine Name is required";
            isValid = false;
        } else if ($("input[name='item_type']:checked").val() === "company" && $("#machine_code").val().trim() === "") {
            errorMessage = "Machine Code is required";
            isValid = false;
        } else if ($("input[name='item_type']:checked").val() === "customer" && $("#customer_name").val().trim() === "") {
        } else if ($("input[name='item_type']:checked").val() === "customer" && $("#customer_name").val().trim() === "") {
            errorMessage = "Customer Name is required";
            isValid = false;
        } else if ($("input[name='item_type']:checked").val() === "customer" && $("#customer_phone").val().trim() === "") {
            errorMessage = "Customer Phone is required";
            isValid = false;
        } else if ($("#item_breakdown_date").val().trim() === "") {
            errorMessage = "Item Breakdown Date is required";
            isValid = false;
        } else if ($("#is_outsource").is(":checked") && $("#outsource_name").val().trim() === "") {
            errorMessage = "Outsource Company Name is required";
            isValid = false;
        } else if ($("#technical_issue").val().trim() === "") {
            errorMessage = "Technical Issue Details are required";
            isValid = false;
        } else if ($("#employee_id").val() === "") {
            errorMessage = "Please select an employee";
            isValid = false;
        } else if (parseFloat($("#repair_charge").val()) < 0) {
            errorMessage = "Repair Charge cannot be negative";
            isValid = false;
        } else if (parseFloat($("#commission_percentage").val()) < 0) {
            errorMessage = "Commission Percentage cannot be negative";
            isValid = false;
        }

        if (!isValid) {
            swal({
                title: "Error!",
                text: errorMessage,
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
        }

        return isValid;
    }

    // Create new job
    $("#create").click(function (e) {
        e.preventDefault();

        if (!validateForm()) return;

        var formData = {
            create: true,
            job_code: $("#job_code").val(),
            item_type: $("input[name='item_type']:checked").val(),
            machine_code: $("#machine_code").val(),
            machine_name: $("#machine_name").val(),
            customer_name: $("#customer_name").val(),
            customer_address: $("#customer_address").val(),
            customer_phone: $("#customer_phone").val(),
            item_breakdown_date: $("#item_breakdown_date").val(),
            technical_issue: $("#technical_issue").val(),
            job_status: $("#job_status").val(),
            is_outsource: $("#is_outsource").is(":checked") ? 1 : 0,
            outsource_name: $("#outsource_name").val(),
            outsource_address: $("#outsource_address").val(),
            outsource_phone: $("#outsource_phone").val(),
            outsource_cost: $("#outsource_cost").val(),
            repair_charge: $("#repair_charge").val(),
            commission_percentage: $("#commission_percentage").val(),
            commission_amount: $("#commission_amount").val(),
            employee_id: $("#employee_id").val(),
            remark: $("#remark").val(),
            items: JSON.stringify(repairItems)
        };

        $.ajax({
            url: "ajax/php/repair-job.php",
            type: "POST",
            data: formData,
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Repair job created successfully",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Reset page
                    $("#new").click();
                } else if (result.status === "duplicate") {
                    swal({
                        title: "Error!",
                        text: result.message,
                        type: "error"
                    });
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Failed to create job",
                        type: "error"
                    });
                }
            },
            error: function () {
                swal({
                    title: "Error!",
                    text: "Server error occurred",
                    type: "error"
                });
            }
        });
    });

    // Update job
    $("#update").click(function (e) {
        e.preventDefault();

        if (!validateForm()) return;

        var formData = {
            update: true,
            job_id: $("#job_id").val(),
            job_code: $("#job_code").val(),
            item_type: $("input[name='item_type']:checked").val(),
            machine_code: $("#machine_code").val(),
            machine_name: $("#machine_name").val(),
            customer_name: $("#customer_name").val(),
            customer_address: $("#customer_address").val(),
            customer_phone: $("#customer_phone").val(),
            item_breakdown_date: $("#item_breakdown_date").val(),
            technical_issue: $("#technical_issue").val(),
            job_status: $("#job_status").val(),
            is_outsource: $("#is_outsource").is(":checked") ? 1 : 0,
            outsource_name: $("#outsource_name").val(),
            outsource_address: $("#outsource_address").val(),
            outsource_phone: $("#outsource_phone").val(),
            outsource_cost: $("#outsource_cost").val(),
            repair_charge: $("#repair_charge").val(),
            commission_percentage: $("#commission_percentage").val(),
            commission_amount: $("#commission_amount").val(),
            employee_id: $("#employee_id").val(),
            remark: $("#remark").val(),
            items: JSON.stringify(repairItems)
        };

        $.ajax({
            url: "ajax/php/repair-job.php",
            type: "POST",
            data: formData,
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Repair job updated successfully",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Reset page
                    $("#new").click();
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Failed to update job",
                        type: "error"
                    });
                }
            },
            error: function () {

                swal({
                    title: "Error!",
                    text: "Server error occurred",
                    type: "error"
                });
            }
        });
    });

    // Delete job
    $(".delete-job").click(function (e) {
        e.preventDefault();
        var jobId = $("#job_id").val();
        if (!jobId) return;

        swal({
            title: "Are you sure?",
            text: "This will permanently delete EOS repair job!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: "ajax/php/repair-job.php",
                type: "POST",
                data: { delete: true, id: jobId },
                dataType: "JSON",
                success: function (result) {
                    if (result.status === "success") {
                        swal({
                            title: "Deleted!",
                            text: "Repair job has been deleted.",
                            type: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $("#new").click();
                    } else {
                        swal("Error!", "Failed to delete job", "error");
                    }
                }
            });
        });
    });

    // New button - reset form
    $("#new").click(function (e) {
        e.preventDefault();
        $("#form-data")[0].reset();
        $("#job_id").val("");
        $("input[name='item_type'][value='customer']").prop("checked", true);
        $("#job_status").val("pending");
        $("#machine_code").val("");
        $("#machine_name").val("");
        $("#repair_charge").val("0.00");
        $("#outsource_cost").val("0.00");
        $("#commission_percentage").val("15");
        $("#commission_amount").val("0.00");
        $("#employee_id").val("");
        $("#total_cost_display").val("0.00");
        repairItems = [];
        updateRepairItemsTable();
        toggleRepairItemsSection();
        toggleMachineSection();
        toggleOutsourceSection();
        $("#create").show();
        $("#update").hide();
        $(".delete-job").hide();
        $("#print-job").hide();

        // Get new code
        $.ajax({
            url: "ajax/php/repair-job.php",
            type: "POST",
            data: { action: "get_new_code" },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#job_code").val(result.code);
                }
            }
        });
    });

    // Print job
    $("#print-job").click(function (e) {
        e.preventDefault();
        var jobId = $("#job_id").val();
        if (jobId) {
            window.open("print-repair-job.php?id=" + jobId, "_blank");
        }
    });

    // Mark as Delivered
    $("#mark-delivered").click(function (e) {
        e.preventDefault();
        updateDeliveryStatus('delivered');
    });

    // Undo Delivery
    $("#mark-undelivered").click(function (e) {
        e.preventDefault();
        updateDeliveryStatus('completed');
    });

    function updateDeliveryStatus(status) {
        var jobId = $("#job_id").val();
        if (!jobId) return;

        var actionText = status === 'delivered' ? "mark as delivered" : "undo delivery";
        var successText = status === 'delivered' ? "Job marked as delivered!" : "Delivery status undone!";

        swal({
            title: "Are you sure?",
            text: "Do you want to " + actionText + "?",
            type: "info",
            showCancelButton: true,
            confirmButtonText: "Yes, do it!",
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: "ajax/php/repair-job.php",
                type: "POST",
                data: {
                    action: "update_delivery_status",
                    job_id: jobId,
                    status: status
                },
                dataType: "JSON",
                success: function (result) {
                    if (result.status === "success") {
                        swal({
                            title: "Success!",
                            text: successText,
                            type: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });
                        // Reload job details to update UI
                        loadJobDetails(jobId);
                    } else {
                        swal("Error!", result.message || "Failed to update status", "error");
                    }
                },
                error: function () {
                    swal("Error!", "Server error occurred", "error");
                }
            });
        });
    }

    // --- Equipment & Sub-Equipment Selection Logic ---

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
                url: "ajax/php/equipment-rent-master.php",
                type: "POST",
                data: function (d) {
                    d.filter_equipment = true;
                    // Filter to show only equipment that has sub-items (serialized items)
                    d.has_sub_items_only = 'true';
                },
                dataSrc: function (json) {
                    return json.data;
                }
            },
            columns: [
                {
                    data: null,
                    title: "",
                    className: "details-control",
                    orderable: false,
                    defaultContent: '',
                    width: "30px",
                    visible: false // Hiding details for repair job as we just need selection
                },
                { data: "key", title: "#" },
                {
                    data: "image_name",
                    title: "Image",
                    orderable: false,
                    render: function (data, type, row) {
                        var imgSrc = data
                            ? "uploads/equipment/" + data
                            : "assets/images/no-image.png";
                        return `<img src="${imgSrc}" alt="Img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">`;
                    }
                },
                { data: "code", title: "Code" },
                {
                    data: "item_name",
                    title: "Item Name",
                    render: function (data, type, row) {
                        return `<div class="fw-bold">${data}</div>`;
                    }
                }
            ],
            order: [[3, "asc"]],
            pageLength: 50
        });

        // Handle row click to select equipment
        $("#equipmentSelectTable tbody").off("click", "tr").on("click", "tr", function () {
            var data = equipmentTable.row(this).data();
            if (data) {
                // Populate fields
                $("#selected_equipment_id").val(data.id);
                // $("#machine_code").val(data.code); // Don't auto-fill machine code
                $("#machine_name").val(data.item_name);

                // Handle Sub-Equipment Button visibility/state
                $("#selected_sub_equipment_id").val("");

                if (data.no_sub_items == 1) {
                    // If no sub items, maybe disable the sub-equipment search button or keep it as is?
                    // Use case: User selected equipment, auto-filled code. 
                    // If they click search on machine code again, it should probably show "No sub items" or just list available units if any?
                    // But if no_sub_items=1, there are no sub units.
                    // Let's hide the button or disable it to prevent confusion? 
                    // User didn't specify, but good UX.
                    $("#btn-select-sub-equipment").prop('disabled', true);
                } else {
                    $("#btn-select-sub-equipment").prop('disabled', false);
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
        var equipmentId = $("#selected_equipment_id").val();

        if (!equipmentId) {
            $("#noSubEquipmentMsg").show();
            $("#noSubEquipmentMsg").text("Please select an equipment (Machine Name) first.");
            if ($.fn.DataTable.isDataTable("#subEquipmentSelectTable")) {
                $("#subEquipmentSelectTable").DataTable().destroy();
            }
            return;
        }

        if ($.fn.DataTable.isDataTable("#subEquipmentSelectTable")) {
            $("#subEquipmentSelectTable").DataTable().destroy();
        }

        var subEquipmentTable = $("#subEquipmentSelectTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-master.php",
                type: "POST",
                data: function (d) {
                    d.filter_sub_equipment = true;
                    d.equipment_id = equipmentId;
                },
                dataSrc: function (json) {
                    return json.data;
                }
            },
            columns: [
                { data: "key", title: "#" },
                { data: "code", title: "Code" }, // Fixed column name
                {
                    data: "rental_status", // Fixed column name
                    title: "Status",
                    render: function (data) {
                        if (data === 'available') {
                            return '<span class="badge bg-success">Available</span>';
                        } else {
                            return '<span class="badge bg-danger">' + (data || 'Unavailable') + '</span>';
                        }
                    }
                }
            ],
            pageLength: 50
        });

        // Handle row click to select sub-equipment
        $("#subEquipmentSelectTable tbody").off("click", "tr").on("click", "tr", function () {
            var data = subEquipmentTable.row(this).data();
            if (data) {
                $("#selected_sub_equipment_id").val(data.id);
                // Update machine_code to be the specific sub-equipment code
                $("#machine_code").val(data.code);

                $("#SubEquipmentSelectModal").modal("hide");
            }
        });
    }

    // Update toggleMachineSection to handle validaiton cleanup
    var originalToggleMachineSection = toggleMachineSection;
    toggleMachineSection = function () {
        var itemType = $("input[name='item_type']:checked").val();
        if (itemType === "company") {
            $("#machine_code_section").slideDown();
            $("#customer_contact_section").slideUp();

            // Show the search button for Machine Name
            $("#btn-select-equipment").show();
            // DISABLE Sub Equipment Button by default (Enable after selecting machine)
            $("#btn-select-sub-equipment").prop('disabled', true);
            $("#machine_code").prop("readonly", true);

        } else {
            $("#machine_code_section").slideUp();
            // Hide the search button for Machine Name
            $("#btn-select-equipment").hide();

            $("#machine_code").val("");
            $("#selected_equipment_id").val("");
            $("#selected_sub_equipment_id").val("");
            $("#machine_code").prop("readonly", false);

            $("#customer_contact_section").slideDown();
        }
    };

    // Override the original event binding to use the new function
    $("input[name='item_type']").off("change").on("change", function () {
        toggleMachineSection();
    });

    // Initial call to set state
    toggleMachineSection();

    // Check for job_id in URL on load
    const urlParams = new URLSearchParams(window.location.search);
    const jobId = urlParams.get('job_id');
    if (jobId) {
        loadJobDetails(jobId);
    }
});
