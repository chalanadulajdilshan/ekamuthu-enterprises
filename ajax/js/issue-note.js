
$(document).ready(function () {
    var issueItems = [];

    // Get New Code Function
    function getNewCode() {
        $.ajax({
            url: "ajax/php/issue-note.php",
            type: "POST",
            data: {
                action: "get_new_code"
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#issue_note_code").val(result.code);
                }
            }
        });
    }

    // Initial Load
    getNewCode();

    // Load Rent Invoice Table in Modal
    $("#RentInvoiceModal").on("shown.bs.modal", function () {
        if ($.fn.DataTable.isDataTable("#rentInvoiceTable")) {
            $("#rentInvoiceTable").DataTable().destroy();
        }

        $("#rentInvoiceTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/equipment-rent-master.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
                    d.exclude_issued = true;
                    d.exclude_returned = true;
                }
            },
            columns: [
                { data: "id", title: "#ID" },
                { data: "bill_number", title: "Ref No" },
                { data: "customer_name", title: "Customer" },
                { data: "rental_date", title: "Date" },
                { data: "status_label", title: "Status" }
            ],
            order: [[0, "desc"]]
        });

        // Row click handler for Rent Invoice
        $("#rentInvoiceTable tbody").on("click", "tr", function () {
            var data = $("#rentInvoiceTable").DataTable().row(this).data();
            if (data) {
                var invoiceId = data.id;
                $("#RentInvoiceModal").modal("hide");
                loadInvoiceDetails(invoiceId);
            }
        });
    });

    // Load Issue Note History Table in Modal
    $("#IssueNoteHistoryModal").on("shown.bs.modal", function () {
        if ($.fn.DataTable.isDataTable("#issueNoteHistoryTable")) {
            $("#issueNoteHistoryTable").DataTable().destroy();
        }

        $("#issueNoteHistoryTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/issue-note.php",
                type: "POST",
                data: function (d) {
                    d.filter = true;
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

        // Row click handler for Issue Note History
        $("#issueNoteHistoryTable tbody").on("click", "tr", function () {
            var data = $("#issueNoteHistoryTable").DataTable().row(this).data();
            if (data) {
                var noteId = data.id;
                $("#IssueNoteHistoryModal").modal("hide");
                loadIssueNoteDetails(noteId);
            }
        });
    });

    // Remove legacy click handlers if present
    $(document).off("click", ".select-invoice");

    // Load Invoice Details and Items
    function loadInvoiceDetails(invoiceId) {
        $.ajax({
            url: "ajax/php/issue-note.php",
            type: "POST",
            data: {
                action: "get_invoice_details",
                invoice_id: invoiceId
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    var invoice = result.invoice;
                    $("#rent_invoice_id").val(invoice.id);
                    $("#customer_id").val(invoice.customer_id);
                    $("#customer_name").val(invoice.customer_name);
                    $("#customer_phone").val(invoice.customer_phone);
                    $("#selected_invoice_display").val(invoice.bill_number + " - " + invoice.rental_date);

                    // Auto-fill existing issue note code if history exists (Append Mode)
                    if (result.history && result.history.length > 0) {
                        var existingCode = result.history[0].issue_note_code;
                        $("#issue_note_code").val(existingCode);
                        swal("Info", "Appending to existing Issue Note: " + existingCode, "info");
                    } else {
                        // Reset to new code if no history
                        getNewCode();
                    }

                    // Store items
                    issueItems = result.items.map(function (item) {
                        var ordered = parseFloat(item.quantity) || 0;
                        var alreadyIssued = parseFloat(item.already_issued) || 0;
                        var remaining = parseFloat(item.remaining_quantity) || 0;

                        return {
                            equipment_id: item.equipment_id,
                            sub_equipment_id: item.sub_equipment_id,
                            equipment_name: item.equipment_name,
                            sub_equipment_code: item.sub_equipment_code,
                            rent_type: item.rent_type,
                            ordered_quantity: ordered,
                            already_issued: alreadyIssued,
                            remaining_quantity: remaining,
                            issued_quantity: remaining, // Default to issuing all remaining
                            remarks: ""
                        };
                    });

                    renderItemsTable();
                    renderHistoryTable(result.history);

                } else {
                    swal("Error!", result.message, "error");
                }
            },
            error: function () {
                swal("Error!", "Failed to load invoice details", "error");
            }
        });
    }

    // Load Issue Note Details
    function loadIssueNoteDetails(noteId) {
        $.ajax({
            url: "ajax/php/issue-note.php",
            type: "POST",
            data: {
                action: "get_issue_note_details",
                note_id: noteId
            },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    var note = result.note;

                    // Populate Form
                    $("#issue_note_code").val(note.issue_note_code);
                    $("#issue_date").val(note.issue_date);
                    $("#issue_status").val(note.issue_status);
                    $("#department_id").val(note.department_id);
                    $("#remarks").val(note.remarks);

                    $("#rent_invoice_id").val(note.rent_invoice_id);
                    $("#selected_invoice_display").val(note.rent_invoice_ref);

                    $("#customer_id").val(note.customer_id);
                    $("#customer_name").val(note.customer_name);
                    $("#customer_phone").val(note.customer_phone);

                    savedNoteId = note.id;
                    $("#print_note").show();

                    // View Mode: Just show what was in THIS note
                    // We don't recalculate remaining etc here because this is a historic view
                    issueItems = result.items.map(function (item) {
                        return {
                            equipment_id: item.equipment_id,
                            sub_equipment_id: item.sub_equipment_id,
                            equipment_name: item.equipment_name,
                            sub_equipment_code: item.sub_equipment_code,
                            rent_type: item.rent_type,
                            ordered_quantity: item.ordered_quantity,
                            already_issued: 0, // Not relevant in view mode
                            remaining_quantity: 0, // Not relevant
                            issued_quantity: item.issued_quantity,
                            remarks: item.remarks,
                            is_view_mode: true // Flag to disable inputs
                        };
                    });

                    renderItemsTable();
                    $("#issueHistoryContainer").hide(); // Hide history when viewing a specific note

                } else {
                    swal("Error!", result.message, "error");
                }
            },
            error: function () {
                swal("Error!", "Failed to load issue note details", "error");
            }
        });
    }

    // Render Items Table
    function renderItemsTable() {
        var tbody = $("#issueItemsTable tbody");
        tbody.empty();

        if (issueItems.length === 0) {
            tbody.append('<tr><td colspan="9" class="text-center py-4 text-muted"><i class="uil uil-box font-size-24 d-block mb-2"></i>Select a Rent Invoice to load items</td></tr>');
            return;
        }

        issueItems.forEach(function (item, index) {

            var itemName = item.equipment_name;
            if (item.sub_equipment_code) {
                itemName += " (" + item.sub_equipment_code + ")";
            }

            var isReadOnly = item.is_view_mode || item.remaining_quantity <= 0;
            var inputDisabled = isReadOnly ? 'disabled' : '';
            var bgClass = item.remaining_quantity <= 0 && !item.is_view_mode ? 'table-success' : '';
            var issueVal = item.issued_quantity;

            var row = `
                <tr class="${bgClass}">
                    <td>${index + 1}</td>
                    <td>${itemName}
                        <input type="hidden" class="equipment_id" value="${item.equipment_id}">
                        <input type="hidden" class="sub_equipment_id" value="${item.sub_equipment_id || ''}">
                    </td>
                    <td>${item.rent_type}</td>
                    <td>${item.return_date || '-'}</td>
                    
                    <!-- Ordered -->
                    <td class="text-center bg-light">
                        <span class="fw-bold">${item.ordered_quantity}</span>
                    </td>
                    
                    <!-- Already Issued -->
                    <td class="text-center bg-light">
                        <span>${item.already_issued || '-'}</span>
                    </td>
                    
                    <!-- Remaining -->
                    <td class="text-center bg-light">
                        <span class="text-primary fw-bold remaining-display">${item.remaining_quantity !== undefined ? item.remaining_quantity : '-'}</span>
                    </td>
                    
                    <!-- Issue Now -->
                    <td>
                        <input type="number" class="form-control form-control-sm text-center issued-qty" 
                               data-index="${index}" value="${issueVal}" min="0" max="${item.remaining_quantity}" ${inputDisabled}>
                    </td>
                    
                    <td>
                        <input type="text" class="form-control form-control-sm item-remark" 
                               data-index="${index}" value="${item.remarks || ''}" placeholder="Remark" ${item.is_view_mode ? 'readonly' : ''}>
                    </td>
                    <td>
                        ${!item.is_view_mode ?
                    `<button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
                            <i class="uil uil-trash"></i>
                        </button>` : ''}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Render History Table
    function renderHistoryTable(history) {
        var tbody = $("#issueHistoryTable tbody");
        tbody.empty();

        if (!history || history.length === 0) {
            $("#issueHistoryContainer").hide();
            return;
        }

        $("#issueHistoryContainer").show();

        history.forEach(function (h) {
            var statusBadge = h.issue_status === 'issued' ? '<span class="badge bg-success">Issued</span>' : '<span class="badge bg-warning">Pending</span>';
            var totalQty = h.total_qty || 0;

            var row = `
                <tr style="cursor: pointer;" class="view-history-note" data-id="${h.id}">
                    <td>${h.issue_date}</td>
                    <td>${h.issue_note_code}</td>
                    <td>${statusBadge}</td>
                    <td><small>${h.items_summary || '-'}</small></td>
                    <td><span class="badge bg-info font-size-12">${totalQty}</span></td>
                    <td>${h.created_at}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // View History Note Click
    $(document).on("click", ".view-history-note", function () {
        var id = $(this).data("id");
        $("#IssueNoteHistoryModal").modal("hide");
        loadIssueNoteDetails(id);
    });

    // Update Issued Quantity
    $(document).on("change keyup", ".issued-qty", function () {
        var index = $(this).data("index");
        var val = parseInt($(this).val()) || 0;
        var max = parseInt(issueItems[index].quantity);

        if (val < 0) val = 0;

        issueItems[index].issued_quantity = val;
    });

    // Update Item Remark
    $(document).on("change keyup", ".item-remark", function () {
        var index = $(this).data("index");
        issueItems[index].remarks = $(this).val();
    });

    // Remove Item
    $(document).on("click", ".remove-item", function () {
        var index = $(this).data("index");
        issueItems.splice(index, 1);
        renderItemsTable();
    });

    // Save Issue Note
    $("#save_note").click(function (e) {
        e.preventDefault();

        // Validation
        if (!$("#rent_invoice_id").val()) {
            swal("Error!", "Please select a Rent Invoice first", "error");
            return;
        }

        if (!$("#department_id").val()) {
            swal("Error!", "Please select a Department", "error");
            return;
        }

        if (issueItems.length === 0) {
            swal("Error!", "No items to issue", "error");
            return;
        }

        var formData = {
            create: true,
            issue_note_code: $("#issue_note_code").val(),
            rent_invoice_id: $("#rent_invoice_id").val(),
            customer_id: $("#customer_id").val(),
            issue_date: $("#issue_date").val(),
            issue_status: $("#issue_status").val(),
            department_id: $("#department_id").val(),
            remarks: $("#remarks").val(),
            items: JSON.stringify(issueItems)
        };

        $.ajax({
            url: "ajax/php/issue-note.php",
            type: "POST",
            data: formData,
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    savedNoteId = result.id;
                    swal({
                        title: "Success!",
                        text: "Issue Note created successfully!",
                        type: "success",
                        showConfirmButton: true,
                        timer: 1500
                    }, function () {
                        // Open print page in new window
                        window.open("issue-note-print.php?id=" + savedNoteId, "_blank");
                        // Reload current page to reset
                        location.reload();
                    });

                    // Fallback if user doesn't click OK (for timer)
                    setTimeout(function () {
                        window.open("issue-note-print.php?id=" + savedNoteId, "_blank");
                        location.reload();
                    }, 1500);

                } else {
                    swal("Error!", result.message, "error");
                }
            },
            error: function () {
                swal("Error!", "Server error occurred", "error");
            }
        });
    });

    // Print Issue Note
    var savedNoteId = null;
    $("#print_note").click(function (e) {
        e.preventDefault();
        if (savedNoteId) {
            window.open("issue-note-print.php?id=" + savedNoteId, "_blank");
        }
    });

    // Create New
    $("#new_note").click(function (e) {
        e.preventDefault();
        location.reload();
    });
});
//issue note js

