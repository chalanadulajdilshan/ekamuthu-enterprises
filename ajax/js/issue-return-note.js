$(document).ready(function () {
    var returnItems = [];
    var current_return_id = null;

    // Get New Code Function
    function getNewCode() {
        $.ajax({
            url: "ajax/php/issue-return-note.php",
            type: "POST",
            data: {
                action: "get_new_code"
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#return_note_code").val(result.code);
                }
            }
        });
    }

    // Initial Load
    getNewCode();

    // Load Issue Note Table in Modal
    $("#IssueNoteModal").on("shown.bs.modal", function () {
        if ($.fn.DataTable.isDataTable("#issueNoteTable")) {
            $("#issueNoteTable").DataTable().destroy();
        }

        $("#issueNoteTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/issue-note.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                    d.exclude_returned = true;
                }
            },
            columns: [
                { data: "id", title: "#ID" },
                { data: "issue_note_code", title: "Issue Note No" },
                { data: "rent_invoice_ref", title: "Ref Invoice" },
                { data: "customer_name", title: "Customer" },
                { data: "issue_date", title: "Date" },
                { data: "status", title: "Status" }
            ],
            order: [[0, "desc"]]
        });

        // Row click handler
        $("#issueNoteTable tbody").on("click", "tr", function () {
            var data = $("#issueNoteTable").DataTable().row(this).data();
            if (data) {
                var noteId = data.id;
                $("#IssueNoteModal").modal("hide");
                loadIssueDetails(noteId);
            }
        });
    });

    // Load Return History Table in Modal
    $("#ReturnHistoryModal").on("shown.bs.modal", function () {
        if ($.fn.DataTable.isDataTable("#returnHistoryTable")) {
            $("#returnHistoryTable").DataTable().destroy();
        }

        $("#returnHistoryTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/issue-return-note.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                }
            },
            columns: [
                { data: "id", title: "#ID" },
                { data: "return_code", title: "Return Note No" },
                { data: "issue_note_ref", title: "Issue Note Ref" },
                { data: "customer_name", title: "Customer" },
                { data: "return_date", title: "Date" },
                { data: "remarks", title: "Remarks" },
                {
                    data: "id",
                    title: "Action",
                    render: function (data, type, row) {
                        return `<button type="button" class="btn btn-sm btn-info print-return-note" data-id="${data}">
                                    <i class="uil uil-print"></i> Print
                                </button>`;
                    }
                }
            ],
            order: [[0, "desc"]]
        });

        // Row click handler for History (Load Details) - exclude action buttons
        $("#returnHistoryTable tbody").on("click", "td:not(:last-child)", function () {
            var data = $("#returnHistoryTable").DataTable().row($(this).parents('tr')).data();
            if (data) {
                var returnId = data.id;
                $("#ReturnHistoryModal").modal("hide");
                loadReturnDetails(returnId);
            }
        });

        // Print button handler
        $(document).on("click", ".print-return-note", function (e) {
            e.stopPropagation();
            var id = $(this).data("id");
            window.open("issue-return-note-print.php?id=" + id, "_blank");
        });
    });

    // Load Return Details
    function loadReturnDetails(returnId) {
        $.ajax({
            url: "ajax/php/issue-return-note.php",
            type: "POST",
            data: {
                action: "get_return_note_details",
                return_id: returnId
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    var ret = result.return;
                    $("#issue_note_id").val(ret.issue_note_id);
                    $("#customer_name").val(ret.customer_name);
                    $("#customer_phone").val(ret.customer_phone);
                    $("#selected_issue_display").val(ret.issue_note_code);
                    $("#return_note_code").val(ret.return_code);
                    $("#return_date").val(ret.return_date);
                    $("#department_id").val(ret.department_id);
                    $("#remarks").val(ret.remarks);

                    // Store items (View Mode)
                    returnItems = result.items.map(function (item) {
                        return {
                            equipment_id: item.equipment_id,
                            sub_equipment_id: item.sub_equipment_id,
                            equipment_name: item.equipment_name,
                            sub_equipment_code: item.sub_equipment_code,
                            rent_type: item.rent_type,
                            issued_quantity: parseFloat(item.issued_quantity) || 0,
                            already_returned: 0, // Not relevant in view mode
                            remaining_quantity: 0, // Not relevant
                            return_quantity: parseFloat(item.return_quantity) || 0,
                            remarks: item.remarks || "",
                            is_view_mode: true
                        };
                    });

                    renderItemsTable();
                    $("#save_return").hide(); // Hide save button in view mode
                    $("#print_return").show().attr("data-id", returnId); // Show print button

                } else {
                    swal("Error!", result.message, "error");
                }
            },
            error: function () {
                swal("Error!", "Failed to load return details", "error");
            }
        });
    }

    // Load Issue Details and Items
    function loadIssueDetails(noteId) {
        $.ajax({
            url: "ajax/php/issue-return-note.php",
            type: "POST",
            data: {
                action: "get_issue_details",
                issue_note_id: noteId
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    var note = result.note;
                    $("#issue_note_id").val(note.id);
                    $("#customer_name").val(note.customer_name);
                    $("#customer_phone").val(note.customer_phone);
                    $("#selected_issue_display").val(note.issue_note_code + " - " + note.issue_date);

                    // Handle Appending to Existing Return Note
                    current_return_id = null; // Reset
                    if (result.history && result.history.length > 0) {
                        var latestReturn = result.history[0];
                        current_return_id = latestReturn.id;
                        $("#return_note_code").val(latestReturn.return_code);
                        $("#remarks").val(latestReturn.remarks);
                        swal("Info", "Appending to existing Return Note: " + latestReturn.return_code, "info");
                    } else {
                        getNewCode();
                    }

                    // Store items
                    returnItems = result.items.map(function (item) {
                        return {
                            equipment_id: item.equipment_id,
                            sub_equipment_id: item.sub_equipment_id,
                            equipment_name: item.equipment_name,
                            sub_equipment_code: item.sub_equipment_code,
                            rent_type: item.rent_type,
                            issued_quantity: parseFloat(item.issued_quantity) || 0,
                            already_returned: parseFloat(item.already_returned) || 0,
                            remaining_quantity: parseFloat(item.remaining_quantity) || 0,
                            return_quantity: parseFloat(item.remaining_quantity) || 0, // Default to returning all
                            remarks: ""
                        };
                    });

                    renderItemsTable();
                    $("#save_return").show(); // Ensure save button is visible for new entries
                    $("#print_return").hide(); // Hide print button for new entries

                } else {
                    swal("Error!", result.message, "error");
                }
            },
            error: function () {
                swal("Error!", "Failed to load issue details", "error");
            }
        });
    }

    // Render Items Table
    function renderItemsTable() {
        var tbody = $("#returnItemsTable tbody");
        tbody.empty();

        if (returnItems.length === 0) {
            tbody.append('<tr id="empty_row"><td colspan="8" class="text-center py-4 text-muted"><i class="uil uil-box font-size-24 d-block mb-2"></i>Select an Issue Note to load items</td></tr>');
            return;
        }

        returnItems.forEach(function (item, index) {
            var itemName = item.equipment_name;
            if (item.sub_equipment_code) {
                itemName += " (" + item.sub_equipment_code + ")";
            }

            var isReadOnly = item.remaining_quantity <= 0;
            var inputDisabled = isReadOnly ? 'disabled' : '';
            var bgClass = item.remaining_quantity <= 0 ? 'table-success' : '';

            var row = `
                <tr class="${bgClass}">
                    <td>${index + 1}</td>
                    <td>${itemName}</td>
                    <td class="text-center bg-light">${item.issued_quantity}</td>
                    <td class="text-center bg-light">${item.already_returned}</td>
                    <td class="text-center bg-light text-primary fw-bold">${item.remaining_quantity}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-center return-qty" 
                               data-index="${index}" value="${item.return_quantity}" min="0" max="${item.remaining_quantity}" ${inputDisabled}>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-remark" 
                               data-index="${index}" value="${item.remarks || ''}" placeholder="Remark" ${inputDisabled}>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
                            <i class="uil uil-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Update Return Quantity
    $(document).on("change keyup", ".return-qty", function () {
        var index = $(this).data("index");
        var val = parseFloat($(this).val()) || 0;
        var max = returnItems[index].remaining_quantity;

        if (val < 0) val = 0;
        if (val > max) {
            swal("Warning", "Return quantity cannot exceed remaining balance (" + max + ")", "warning");
            val = max;
            $(this).val(val);
        }

        returnItems[index].return_quantity = val;
    });

    // Update Item Remark
    $(document).on("change keyup", ".item-remark", function () {
        var index = $(this).data("index");
        returnItems[index].remarks = $(this).val();
    });

    // Remove Item
    $(document).on("click", ".remove-item", function () {
        var index = $(this).data("index");
        returnItems.splice(index, 1);
        renderItemsTable();
    });

    // Save Return Note
    $("#save_return").click(function (e) {
        e.preventDefault();

        if (!$("#issue_note_id").val()) {
            swal("Error!", "Please select an Issue Note first", "error");
            return;
        }

        var totalReturning = returnItems.reduce((acc, item) => acc + item.return_quantity, 0);
        if (totalReturning <= 0) {
            swal("Error!", "Please enter at least one return quantity", "error");
            return;
        }

        var formData = {
            create: true,
            return_id: current_return_id,
            return_code: $("#return_note_code").val(),
            issue_note_id: $("#issue_note_id").val(),
            return_date: $("#return_date").val(),
            department_id: $("#department_id").val(),
            remarks: $("#remarks").val(),
            items: JSON.stringify(returnItems)
        };

        $.ajax({
            url: "ajax/php/issue-return-note.php",
            type: "POST",
            data: formData,
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Issue Return Note created successfully!",
                        type: "success",
                        showCancelButton: true,
                        confirmButtonText: "Print Note",
                        cancelButtonText: "Close",
                        closeOnConfirm: true
                    }, function (isConfirm) {
                        if (isConfirm) {
                            window.open("issue-return-note-print.php?id=" + result.id, "_blank");
                        }
                        location.reload();
                    });
                } else if (result.status === "duplicate") {
                    swal("Error!", result.message, "error");
                } else {
                    swal("Error!", result.message, "error");
                }
            },
            error: function () {
                swal("Error!", "Server error occurred", "error");
            }
        });
    });

    // New Return Action
    $("#new_return").click(function (e) {
        e.preventDefault();
        location.reload();
    });

    // Header Print Action
    $("#print_return").click(function () {
        var id = $(this).attr("data-id");
        if (id) {
            window.open("issue-return-note-print.php?id=" + id, "_blank");
        }
    });
});
