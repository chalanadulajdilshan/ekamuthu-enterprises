$(document).ready(function () {
    var issueItems = [];

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

                    // Store items
                    issueItems = result.items.map(function (item) {
                        item.ordered_quantity = item.quantity;
                        if (!item.issued_quantity) {
                            item.issued_quantity = item.quantity;
                        }
                        return item;
                    });

                    renderItemsTable();
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
                    $("#remarks").val(note.remarks);

                    $("#rent_invoice_id").val(note.rent_invoice_id);
                    $("#selected_invoice_display").val(note.rent_invoice_ref);

                    $("#customer_id").val(note.customer_id);
                    $("#customer_name").val(note.customer_name);
                    $("#customer_phone").val(note.customer_phone);

                    savedNoteId = note.id;
                    $("#print_note").show();

                    // Store items and render
                    // Note: items from get_issue_note_details structure matches renderItemsTable expectations
                    // but we need to map keys if they differ. 
                    // IssueNoteItem::getItems returns: equipment_name, sub_equipment_code, ordered_quantity, issued_quantity
                    // renderItemsTable expects: equipment_name, sub_equipment_code, quantity (for max), issued_quantity

                    issueItems = result.items.map(function (item) {
                        return {
                            equipment_id: item.equipment_id,
                            sub_equipment_id: item.sub_equipment_id,
                            equipment_name: item.equipment_name,
                            sub_equipment_code: item.sub_equipment_code,
                            rent_type: item.rent_type,
                            quantity: item.ordered_quantity, // Map ordered to quantity for validation max
                            ordered_quantity: item.ordered_quantity,
                            issued_quantity: item.issued_quantity,
                            remarks: item.remarks
                        };
                    });

                    renderItemsTable();

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
            tbody.append('<tr><td colspan="7" class="text-center py-4 text-muted"><i class="uil uil-box font-size-24 d-block mb-2"></i>Select a Rent Invoice to load items</td></tr>');
            return;
        }

        issueItems.forEach(function (item, index) {

            var itemName = item.equipment_name;
            if (item.sub_equipment_code) {
                itemName += " (" + item.sub_equipment_code + ")";
            }

            var row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${itemName}
                        <input type="hidden" class="equipment_id" value="${item.equipment_id}">
                        <input type="hidden" class="sub_equipment_id" value="${item.sub_equipment_id || ''}">
                    </td>
                    <td>${item.rent_type}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm text-center" value="${item.quantity}" readonly>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-center issued-qty" 
                               data-index="${index}" value="${item.issued_quantity}" min="0" max="${item.quantity}">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-remark" 
                               data-index="${index}" value="${item.remarks || ''}" placeholder="Remark">
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

    // Update Issued Quantity
    $(document).on("change keyup", ".issued-qty", function () {
        var index = $(this).data("index");
        var val = parseInt($(this).val()) || 0;
        var max = parseInt(issueItems[index].quantity);

        if (val < 0) val = 0;
        // Optional: Enforcement to not exceed ordered quantity
        // if (val > max) val = max; 

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
                        // Redirect to print page
                        window.location.href = "issue-note-print.php?id=" + savedNoteId;
                    });

                    // Fallback if user doesn't click OK (for timer)
                    setTimeout(function () {
                        window.location.href = "issue-note-print.php?id=" + savedNoteId;
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
