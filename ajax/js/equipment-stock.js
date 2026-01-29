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

            api.rows().every(function () {
                var data = this.data();
                if (data.has_sub_match === true && searchTerm.length > 0) {
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
    function renderSubEquipmentTable(subEquipments, meta, searchTerm) {
        // If equipment has no sub-items, show summary badges
        if (meta && meta.no_sub_items == 1) {
            var available = parseFloat(meta.available_qty || 0).toFixed(0);
            var rented = parseFloat(meta.rented_qty || 0).toFixed(0);
            var total = parseFloat(meta.total_qty || 0).toFixed(0);

            return (
                '<div class="row m-2">' +

                '<div class="col-md-4">' +
                '<div class="p-3 bg-white rounded border shadow-sm d-flex align-items-center justify-content-center h-100">' +
                '<span class="text-muted fw-bold me-2 text-uppercase font-size-14">Available</span>' +
                '<span class="text-muted fw-bold me-2">-</span>' +
                '<span class="text-success fw-bold font-size-22">' + available + '</span>' +
                '</div>' +
                '</div>' +

                '<div class="col-md-4">' +
                '<div class="p-3 bg-white rounded border shadow-sm d-flex align-items-center justify-content-center h-100">' +
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
        }

        if (!Array.isArray(subEquipments) || subEquipments.length === 0) {
            return '<div class="p-2 text-muted">No sub-equipment available for this equipment</div>';
        }

        let html =
            '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
        html +=
            '<thead class="table-light"><tr>' +
            "<th>ID</th>" +
            "<th>Equipment ID</th>" +
            "<th>Sub Equipment Code</th>" +
            "<th>Status</th>" +
            "</tr></thead><tbody>";

        subEquipments.forEach(function (item, index) {
            var code = item.code || "-";
            if (searchTerm) {
                code = highlightText(code, searchTerm);
            }

            html +=
                "<tr>" +
                "<td>" + (item.id || "-") + "</td>" +
                "<td>" + (item.equipment_id || "-") + "</td>" +
                "<td>" + code + "</td>" +
                "<td>" + renderStatusBadge(item.rental_status) + "</td>" +
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
                    search: table.search() // Pass current search term
                },
                success: function (resp) {
                    if (resp && resp.status === "success") {
                        row.child(renderSubEquipmentTable(resp.data, resp.meta, table.search())).show();
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
});
