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
        },
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

    // Function to render sub-equipment table
    function renderSubEquipmentTable(subEquipments) {
        if (!Array.isArray(subEquipments) || subEquipments.length === 0) {
            return '<div class="p-2 text-muted">No sub-equipment available for this equipment</div>';
        }

        let html =
            '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
        html +=
            '<thead class="table-light"><tr>' +
            "<th>#</th>" +
            "<th>Sub Equipment Code</th>" +
            "<th>Sub Equipment Name</th>" +
            "<th>Sub Equipment Status</th>" +
            "</tr></thead><tbody>";

        subEquipments.forEach(function (item, index) {
            html +=
                "<tr>" +
                "<td>" + (index + 1) + "</td>" +
                "<td>" + (item.code || "-") + "</td>" +
                "<td>" + (item.name || "-") + "</td>" +
                // "<td>" + (item.status || "-") + "</td>" +
                "</tr>";
        });
        html += "</tbody></table></div>";
        return html;
    }

    // Toggle details on click of first column
    $("#equipmentStockTable tbody").on("click", "td.details-control", function (e) {
        e.stopPropagation();
        var tr = $(this).closest("tr");
        var row = table.row(tr);
        var icon = $(this).find("span.mdi");

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
                },
                success: function (resp) {
                    if (resp && resp.status === "success") {
                        row.child(renderSubEquipmentTable(resp.data)).show();
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

    // Row click: navigate to equipment-master page
    $("#equipmentStockTable tbody").on("click", "tr", function (e) {
        // Don't navigate if details-control was clicked
        if ($(e.target).closest("td.details-control").length) {
            return;
        }

        const rowData = table.row(this).data();
        if (!rowData) return;

        const equipmentId = rowData.id;
        if (equipmentId) {
            // Navigate to equipment master with the equipment pre-selected
            // Try to find the page_id from navigation
            const equipmentMasterAnchor = $(
                'a[href*="equipment-master.php"][href*="page_id="]'
            )
                .first()
                .attr("href");

            if (equipmentMasterAnchor) {
                try {
                    const linkUrl = new URL(
                        equipmentMasterAnchor,
                        window.location.origin
                    );
                    const pageId = linkUrl.searchParams.get("page_id");
                    if (pageId) {
                        window.location.href = `equipment-master.php?page_id=${pageId}&prefill_equipment_id=${equipmentId}`;
                        return;
                    }
                } catch (e) {
                    // Fallback
                }
            }

            // Fallback: navigate without page_id
            window.location.href = `equipment-master.php?prefill_equipment_id=${equipmentId}`;
        }
    });
});
