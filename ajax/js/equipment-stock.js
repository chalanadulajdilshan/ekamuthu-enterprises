jQuery(document).ready(function () {
    // Initialize DataTable with server-side processing
    var table = $("#equipmentStockTable").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/php/equipment-master.php",
            type: "POST",
            data: function (d) {
                d.filter = true;
                d.search_sub_only = $("#searchSubOnly").is(":checked");
            },
            dataSrc: function (json) {
                return json.data;
            },
            error: function (xhr) {
                console.error("Server Error Response:", xhr.responseText);
            },
        },
        columns: [
            {
                data: null,
                title: "",
                className: "details-control",
                orderable: false,
                defaultContent:
                    '<span class="mdi mdi-plus-circle-outline" style="font-size:18px; cursor:pointer;"></span>',
                width: "30px",
            },
            { data: "code", title: "Equipment Code" },
            { data: "item_name", title: "Item Name" },
            {
                data: "category_label",
                title: "Category",
                render: function (data) {
                    return data || "-";
                },
            },
            {
                data: "serial_number",
                title: "Serial Number",
                render: function (data) {
                    return data || "-";
                },
            },
            {
                data: "size",
                title: "Size",
                render: function (data) {
                    return data || "-";
                },
            },
            {
                data: "value",
                title: "Value",
                render: function (data) {
                    return data || "0.00";
                },
            },


            {
                data: "quantity",
                title: "Quantity",
                render: function (data) {
                    return data || "0";
                },
            },
        ],
        order: [[1, "asc"]],
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25,
        responsive: true,
        language: {
            paginate: {
                previous: "<i class='mdi mdi-chevron-left'>",
                next: "<i class='mdi mdi-chevron-right'>",
            },
        },
        drawCallback: function () {
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");

            // Auto-expand rows with sub-equipment matches
            var api = this.api();
            var searchTerm = api.search();
            var isSubSearch = $("#searchSubOnly").is(":checked");

            api.rows().every(function () {
                var data = this.data();
                // Expand if backend says there's a sub match OR if we are in sub-only search mode and there's a search term
                if ((data.has_sub_match === true) && searchTerm.length > 0) {
                    var tr = $(this.node());
                    var row = this;

                    if (!row.child.isShown()) {
                        // Trigger expansion
                        tr.find('td.details-control').trigger('click');
                    }
                }
            });
        }
    });

    // Reload table on checkbox change
    $("#searchSubOnly").on("change", function () {
        table.ajax.reload();
    });

    // Make rows appear clickable
    $("#equipmentStockTable tbody").css("cursor", "pointer");

    // Function to load summary totals
    function loadSummaryTotals() {
        $.ajax({
            url: "ajax/php/equipment-master.php",
            type: "POST",
            dataType: "json",
            data: { action: "get_equipment_totals" },
            success: function (resp) {
                if (resp && resp.status === "success") {
                    const data = resp.data;
                    $("#total-equipment").text(data.total || 0);

                } else {
                    $("#total-equipment").text("Error");

                }
            },
            error: function () {
                $("#total-equipment").text("Error");

            },
        });
    }

    // Load summary totals on page load
    loadSummaryTotals();

    // Highlight text helper
    function highlightText(text, term) {
        if (!term || term.trim() === "") {
            return text;
        }
        var pattern = new RegExp("(" + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ")", "gi");
        return text.replace(pattern, '<span class="highlight-text">$1</span>');
    }

    // Function to render sub-equipment table
    function renderSubEquipmentTable(subEquipments, meta, searchTerm, isSubSearch) {
        // If equipment has no sub-items, show summary badges
        if (meta && meta.no_sub_items == 1) {
            var available = parseFloat(meta.available_qty || 0).toFixed(0);
            var rented = parseFloat(meta.rented_qty || 0).toFixed(0);
            var total = parseFloat(meta.total_qty || 0).toFixed(0);

            var cardsHtml = (
                '<div class="row m-2">' +

                '<div class="col-md-4">' +
                '<div class="p-3 bg-white rounded border shadow-sm d-flex align-items-center justify-content-center h-100">' +
                '<span class="text-muted fw-bold me-2 text-uppercase font-size-14">Available</span>' +
                '<span class="text-muted fw-bold me-2">-</span>' +
                '<span class="text-success fw-bold font-size-22">' + available + '</span>' +
                '</div>' +
                '</div>' +

                '<div class="col-md-4">' +
                '<div class="p-3 bg-white rounded border shadow-sm d-flex align-items-center justify-content-center h-100 rented-card" style="cursor: pointer;" data-id="' + meta.equipment_id + '" data-name="' + meta.equipment_name + '">' +
                '<span class="text-muted fw-bold me-2 text-uppercase font-size-14">Rented</span>' +
                '<span class="text-muted fw-bold me-2">-</span>' +
                '<span class="text-danger fw-bold font-size-22">' + rented + '</span>' +
                '</div>' +
                '</div>' +

                '<div class="col-md-4">' +
                '<div class="p-3 bg-white rounded border shadow-sm d-flex align-items-center justify-content-center h-100">' +
                '<span class="text-muted fw-bold me-2 text-uppercase font-size-14">Total</span>' +
                '<span class="text-muted fw-bold me-2">-</span>' +
                '<span class="text-dark fw-bold font-size-22">' + total + '</span>' +
                '</div>' +
                '</div>' +

                '</div>'
            );

            if (meta.department_stock && meta.department_stock.length > 0) {
                var deptHtml = '<div class="row m-2"><div class="col-12"><h5 class="text-muted font-size-14 mb-3">Department Wise Stock</h5><div class="table-responsive"><table class="table table-bordered border-secondary mb-0"><thead><tr><th>Department</th><th class="text-center">Total</th><th class="text-center">Available</th><th class="text-center">Rented</th></tr></thead><tbody>';

                meta.department_stock.forEach(function (dept) {
                    var qty = parseFloat(dept.qty) || 0;
                    var rented = parseFloat(dept.rented_qty) || 0;
                    var available = parseFloat(dept.available_qty) || 0;

                    deptHtml += '<tr>' +
                        '<td>' + (dept.department_name || '-') + '</td>' +
                        '<td class="text-center"><span class="badge bg-secondary font-size-12">' + qty + '</span></td>' +
                        '<td class="text-center"><span class="badge bg-success font-size-12">' + available + '</span></td>' +
                        '<td class="text-center dept-rented-cell" style="cursor: pointer;" data-id="' + meta.equipment_id + '" data-name="' + meta.equipment_name + '" data-dept-id="' + dept.department_id + '"><span class="badge bg-danger font-size-12">' + rented + '</span></td>' +
                        '</tr>';
                });

                deptHtml += '</tbody></table></div></div></div>';
                return cardsHtml + deptHtml;
            }

            return cardsHtml;
        }

        if (!Array.isArray(subEquipments) || subEquipments.length === 0) {
            return '<div class="p-2 text-muted">No sub-equipment available for this equipment</div>';
        }

        let html =
            '<div class="table-responsive"><table class="table table-sm table-bordered mb-0 sub-equipment-table">';
        html +=
            '<thead class="table-light"><tr>' +
            "<th>ID</th>" +
            "<th>Equipment ID</th>" +
            "<th>Sub Equipment Code</th>" +
            "<th>Status</th>" +
            "</tr></thead><tbody>";

        subEquipments.forEach(function (item, index) {
            var code = item.code || "-";
            // Highlight only if sub-search is active or if we want generic highlight
            if (searchTerm) {
                code = highlightText(code, searchTerm);
            }

            var isRented = (item.rental_status || "").toLowerCase() === "rented" || (item.rental_status || "").toLowerCase() === "rent";
            var isRepair = (item.rental_status || "").toLowerCase() === "repair" || item.is_repair == 1;

            var rentAttr = '';
            if (isRented && item.active_rent_id) {
                rentAttr = ' data-rent-id="' + item.active_rent_id + '" data-bill="' + (item.active_bill_number || '') + '" data-customer="' + (item.active_customer_name || '') + '"';
            }

            var repairAttr = '';
            if (isRepair && item.active_repair_job_id) {
                repairAttr = ' data-repair-id="' + item.active_repair_job_id + '"';
            }

            // Override status if under repair (Deprecated - now handled via rental_status in DB)
            var statusToShow = item.rental_status;

            html +=
                "<tr class='sub-eq-row" + (isRented ? " sub-eq-rented" : "") + (isRepair ? " sub-eq-repair" : "") + "'" + rentAttr + repairAttr + ">" +
                "<td>" + (item.id || "-") + "</td>" +
                "<td>" + (item.equipment_id || "-") + "</td>" +
                "<td>" + code + "</td>" +
                "<td>" + renderStatusBadge(statusToShow) + "</td>" +
                "</tr>";
        });
        html += "</tbody></table></div>";
        return html;
    }

    // Map status to colored badges
    function renderStatusBadge(status) {
        if (!status) return '<span class="badge bg-secondary">UNKNOWN</span>';

        const normalized = status.toLowerCase();
        let badgeClass = "bg-secondary";
        let label = normalized.toUpperCase();

        if (normalized === "available" || normalized === "returned") {
            badgeClass = "bg-success";
            label = "AVAILABLE";
        } else if (normalized === "rent" || normalized === "rented") {
            badgeClass = "bg-primary";
            label = "RENTED";
        } else if (normalized === "damage" || normalized === "damaged") {
            badgeClass = "bg-danger";
            label = "DAMAGED";
        } else if (normalized === "repair" || normalized === "maintenance") {
            badgeClass = "bg-warning";
            label = "REPAIR";
        }

        return '<span class="badge ' + badgeClass + '">' + label + "</span>";
    }

    // Toggle details on row click
    $("#equipmentStockTable tbody").on("click", "tr", function (e) {
        var tr = $(this).closest("tr");
        var row = table.row(tr);
        var icon = tr.find("td.details-control span.mdi");

        if (row.child.isShown()) {
            // Close
            row.child.hide();
            tr.removeClass("shown");
            icon.removeClass("mdi-minus-circle-outline").addClass(
                "mdi-plus-circle-outline"
            );
        } else {
            // Open
            const data = row.data();
            if (!data) return;

            // Show temporary loading content
            const loading =
                '<div class="p-2 text-muted">Loading sub-equipment...</div>';
            row.child(loading).show();
            tr.addClass("shown");
            icon.removeClass("mdi-plus-circle-outline").addClass(
                "mdi-minus-circle-outline"
            );

            // Fetch sub-equipment by equipment_id
            $.ajax({
                url: "ajax/php/equipment-master.php",
                type: "POST",
                dataType: "json",
                data: {
                    action: "get_sub_equipment",
                    equipment_id: data.id,
                    search: table.search(), // Pass current search term
                    search_sub_only: $("#searchSubOnly").is(":checked") // Pass check state
                },
                success: function (resp) {
                    if (resp && resp.status === "success") {
                        // Pass equipment name for the meta header as well
                        if (resp.meta) {
                            resp.meta.equipment_id = data.id;
                            resp.meta.equipment_name = data.item_name;
                        }
                        row.child(renderSubEquipmentTable(resp.data, resp.meta, table.search(), $("#searchSubOnly").is(":checked"))).show();

                        // Attach click handler to Rented Card
                        row.child().find(".rented-card").on("click", function () {
                            const eqId = $(this).data("id");
                            const eqName = $(this).data("name");
                            loadRentedInvoices(eqId, eqName);
                        });

                        // Attach click handler to Department Rented Cell
                        row.child().find(".dept-rented-cell").on("click", function () {
                            const eqId = $(this).data("id");
                            const eqName = $(this).data("name");
                            const deptId = $(this).data("dept-id");
                            loadRentedInvoices(eqId, eqName, deptId);
                        });

                        // Attach click handler to rented sub-equipment rows to open rent details
                        row.child().find("table.sub-equipment-table tbody").on("click", "tr.sub-eq-rented", function (evt) {
                            evt.stopPropagation();
                            var rentId = $(this).data("rent-id");
                            if (rentId) {
                                loadRentDetailsFromStock(rentId);
                            }
                        });

                        // Attach click handler to repair sub-equipment rows to open repair details
                        row.child().find("table.sub-equipment-table tbody").on("click", "tr.sub-eq-repair", function (evt) {
                            evt.stopPropagation();
                            var repairId = $(this).data("repair-id");
                            if (repairId) {
                                loadRepairDetailsFromStock(repairId);
                            }
                        });
                    } else {
                        row.child(
                            '<div class="p-2 text-muted">No sub-equipment available</div>'
                        ).show();
                    }
                },
                error: function () {
                    row.child(
                        '<div class="p-2 text-danger">Failed to load sub-equipment</div>'
                    ).show();
                },
            });
        }
    });

    // --- Rent details loader for equipment stock page ---
    function resetRentDetailsModal() {
        $("#rd-bill, #rd-customer, #rd-status, #rd-rental-date, #rd-received-date, #rd-payment, #rd-transport, #rd-deposit, #rd-refund, #rd-remark").text("-");
        const tbody = $("#rentItemsTable tbody");
        tbody.html('<tr><td colspan="9" class="text-center text-muted">Loading...</td></tr>');
    }

    function renderRentItems(items) {
        const tbody = $("#rentItemsTable tbody");
        tbody.empty();
        if (!Array.isArray(items) || !items.length) {
            tbody.html('<tr><td colspan="9" class="text-center text-muted">No items</td></tr>');
            return;
        }
        items.forEach(function (item, idx) {
            tbody.append(
                "<tr>" +
                "<td>" + (idx + 1) + "</td>" +
                "<td>" + (item.equipment_code || "") + " - " + (item.equipment_name || "") + "</td>" +
                "<td>" + (item.sub_equipment_code || "-") + "</td>" +
                "<td>" + (item.quantity || 0) + "</td>" +
                "<td>" + (item.total_returned_qty || 0) + "</td>" +
                "<td>" + (item.rent_type || "-") + "</td>" +
                "<td>" + (item.duration || "-") + "</td>" +
                "<td>" + (item.amount || "0.00") + "</td>" +
                "<td>" + (item.status ? item.status.toUpperCase() : "-") + "</td>" +
                "</tr>"
            );
        });
    }

    function loadRentDetailsFromStock(rentId) {
        resetRentDetailsModal();
        $.ajax({
            url: "ajax/php/equipment-rent-master.php",
            type: "POST",
            data: { action: "get_rent_details", rent_id: rentId },
            dataType: "json",
            success: function (result) {
                if (result.status === "success") {
                    var rent = result.rent;
                    $("#rd-bill").text(rent.bill_number || "-");
                    $("#rd-customer").text(rent.customer_name || "-");
                    $("#rd-status").text((rent.status || "-").toUpperCase());
                    $("#rd-rental-date").text(rent.rental_date || "-");
                    $("#rd-received-date").text(rent.received_date || "-");
                    $("#rd-payment").text(rent.payment_type_name || "-");
                    $("#rd-transport").text(rent.transport_cost || "0.00");
                    $("#rd-deposit").text(rent.deposit_total || "0.00");
                    $("#rd-refund").text(rent.refund_balance || "0.00");
                    $("#rd-remark").text(rent.remark || "-");
                    $("#rd-open-full").attr("href", "equipment-rent-master.php?rent_id=" + rent.id);
                    renderRentItems(result.items || []);
                    $("#rentDetailsModal").modal("show");
                } else {
                    renderRentItems([]);
                    $("#rentItemsTable tbody").html('<tr><td colspan="9" class="text-center text-danger">Failed to load rent details</td></tr>');
                }
            },
            error: function (xhr) {
                console.error("Rent details load failed", xhr.responseText);
                $("#rentItemsTable tbody").html('<tr><td colspan="9" class="text-center text-danger">Server error</td></tr>');
            }
        });
    }

    // --- Repair details loader for equipment stock page ---
    function resetRepairDetailsModal() {
        $("#rp-job-code, #rp-status, #rp-machine, #rp-date, #rp-issue, #rp-remark, #rp-is-outsource, #rp-outsource-name, #rp-charge, #rp-total-cost").text("-");
        $("#repairItemsTable tbody").html('<tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>');
    }

    function loadRepairDetailsFromStock(repairId) {
        resetRepairDetailsModal();
        $.ajax({
            url: "ajax/php/repair-job.php",
            type: "POST",
            data: { action: "get_job_details", job_id: repairId },
            dataType: "json",
            success: function (result) {
                if (result.status === "success") {
                    var job = result.job;
                    $("#rp-job-code").text(job.job_code || "-");
                    $("#rp-status").text((job.job_status || "-").toUpperCase());
                    $("#rp-machine").text((job.machine_code || "") + " - " + (job.machine_name || ""));
                    $("#rp-date").text(job.item_breakdown_date || "-");
                    $("#rp-issue").text(job.technical_issue || "-");
                    $("#rp-remark").text(job.remark || "-");
                    $("#rp-is-outsource").text(job.is_outsource == 1 ? "YES" : "NO");
                    $("#rp-outsource-name").text(job.outsource_name || "-");
                    $("#rp-charge").text(parseFloat(job.repair_charge || 0).toFixed(2));
                    $("#rp-total-cost").text(parseFloat(job.total_cost || 0).toFixed(2));

                    // Render items
                    const tbody = $("#repairItemsTable tbody");
                    tbody.empty();
                    if (result.items && result.items.length) {
                        result.items.forEach(function (item, idx) {
                            tbody.append(
                                "<tr>" +
                                "<td>" + (idx + 1) + "</td>" +
                                "<td>" + (item.item_name || "-") + "</td>" +
                                "<td class='text-center'>" + (item.quantity || 0) + "</td>" +
                                "<td class='text-end'>" + parseFloat(item.unit_price || 0).toFixed(2) + "</td>" +
                                "<td class='text-end'>" + parseFloat(item.total_price || 0).toFixed(2) + "</td>" +
                                "</tr>"
                            );
                        });
                    } else {
                        tbody.html('<tr><td colspan="5" class="text-center text-muted">No items recorded</td></tr>');
                    }

                    $("#repairDetailsModal").modal("show");
                } else {
                    $("#repairItemsTable tbody").html('<tr><td colspan="5" class="text-center text-danger">Failed to load repair details</td></tr>');
                }
            },
            error: function (xhr) {
                console.error("Repair details load failed", xhr.responseText);
                $("#repairItemsTable tbody").html('<tr><td colspan="5" class="text-center text-danger">Server error</td></tr>');
            }
        });
    }

    var rentedInvoicesTable;

    function loadRentedInvoices(equipmentId, equipmentName, departmentId = null) {
        $("#ri-equipment-name").text(equipmentName);
        $("#rentInvoicesModal").modal("show");

        // Destroy existing table if it exists
        if ($.fn.DataTable.isDataTable('#rentInvoicesTable')) {
            $('#rentInvoicesTable').DataTable().destroy();
        }
        $('#rentInvoicesTable tbody').empty();

        $.ajax({
            url: "ajax/php/equipment-master.php",
            type: "POST",
            data: {
                action: "get_rented_invoices",
                equipment_id: equipmentId,
                department_id: departmentId
            },
            dataType: "json",
            success: function (resp) {
                if (resp && resp.status === "success") {
                    rentedInvoicesTable = $('#rentInvoicesTable').DataTable({
                        data: resp.data,
                        destroy: true,
                        autoWidth: false,
                        pageLength: 10,
                        order: [[4, 'desc']], // Sort by date desc
                        columns: [
                            { data: 'bill_number' },
                            { data: 'customer_name' },
                            { data: 'quantity', className: 'text-center' },
                            { data: 'returned_qty', className: 'text-center' },
                            { data: 'date' },
                            {
                                data: null,
                                className: 'text-center',
                                render: function (data, type, row) {
                                    return '<button class="btn btn-sm btn-soft-primary view-rent-btn" data-id="' + row.rent_id + '"><i class="uil uil-eye"></i> View</button>';
                                }
                            }
                        ],
                        language: {
                            emptyTable: "No rented invoices found"
                        }
                    });
                } else {
                    $('#rentInvoicesTable').DataTable({
                        data: [],
                        destroy: true,
                        language: {
                            emptyTable: "No rented invoices found"
                        }
                    });
                }
            },
            error: function () {
                $('#rentInvoicesTable').DataTable({
                    data: [],
                    destroy: true,
                    language: {
                        emptyTable: "Failed to load data"
                    }
                });
            }
        });
    }

    // Attach handler for the view button (delegated)
    $('#rentInvoicesTable tbody').on('click', '.view-rent-btn', function () {
        const rentId = $(this).data("id");
        $("#rentInvoicesModal").modal("hide");
        loadRentDetailsFromStock(rentId);
    });


});
