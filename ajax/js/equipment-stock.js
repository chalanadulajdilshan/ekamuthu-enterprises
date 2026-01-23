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
            "<th>ID</th>" +
            "<th>Equipment ID</th>" +
            "<th>Sub Equipment Code</th>" +
            "</tr></thead><tbody>";

        subEquipments.forEach(function (item, index) {
            html +=
                "<tr>" +
                "<td>" + (item.id || "-") + "</td>" +
                "<td>" + (item.equipment_id || "-") + "</td>" +
                "<td>" + (item.code || "-") + "</td>" +
                "</tr>";
        });
        html += "</tbody></table></div>";
        return html;
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
});
