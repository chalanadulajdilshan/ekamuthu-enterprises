$(document).ready(function () {
    var table = $('#status_report_table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajax/php/equipment-status-report.php",
            "type": "POST",
            "data": function (d) {
                d.status = $('#status_filter').val();
            }
        },
        "columns": [
            { "data": "key" },
            { "data": "code" },
            { "data": "item_name" },
            { "data": "category" },
            { "data": "department" },
            {
                "data": "status",
                "render": function (data, type, row) {
                    var status = data ? data.toUpperCase() : 'UNKNOWN';
                    var badgeClass = 'bg-secondary';
                    var cursor = 'default';
                    var dataAttrs = '';

                    if (status === 'AVAILABLE') {
                        badgeClass = 'bg-success';
                    } else if (status === 'RENTED' || status === 'RENT') {
                        badgeClass = 'bg-primary';
                        cursor = 'pointer';
                        if (row.is_sub) {
                            dataAttrs = ' data-action="view_rent" data-id="' + row.active_rent_id + '"';
                        } else {
                            dataAttrs = ' data-action="view_rent_list" data-id="' + row.equipment_id + '" data-name="' + row.item_name + '"';
                        }
                    } else if (status === 'DAMAGE' || status === 'DAMAGED') {
                        badgeClass = 'bg-danger';
                        // Usually damaged items are linked to a rent return or a repair job?
                        // If sub item and has repair job, maybe link?
                        // For now, if repair is active, it links there.
                    } else if (status === 'REPAIR') {
                        badgeClass = 'bg-warning';
                        cursor = 'pointer';
                        if (row.is_sub && row.active_repair_job_id) {
                            dataAttrs = ' data-action="view_repair" data-id="' + row.active_repair_job_id + '"';
                        }
                    }

                    return '<span class="badge ' + badgeClass + ' status-badge" style="cursor:' + cursor + '" ' + dataAttrs + '>' + status + '</span>';
                }
            },
            {
                "data": "quantity",
                "className": "text-end"
            }
        ],
        "order": [[1, "asc"]], // Sort by Code
        "pageLength": 25
    });

    $('#btn-filter').click(function () {
        table.ajax.reload();
    });

    $('#status_filter').change(function () {
        table.ajax.reload();
    });

    // Handle Status Click
    $('#status_report_table tbody').on('click', '.status-badge', function () {
        var action = $(this).data('action');
        var id = $(this).data('id');
        var name = $(this).data('name');

        if (action === 'view_rent') {
            if (id) loadRentDetailsFromStock(id);
        } else if (action === 'view_rent_list') {
            if (id) loadRentedInvoices(id, name);
        } else if (action === 'view_repair') {
            if (id) loadRepairDetailsFromStock(id);
        }
    });

    loadStatusCounts();

    function loadStatusCounts() {
        $.ajax({
            url: "ajax/php/equipment-status-report.php",
            type: "POST",
            data: { action: "get_stats" },
            dataType: "json",
            success: function (resp) {
                if (resp.status === "success") {
                    var data = resp.data;
                    $('#count-total').text(data.total);
                    $('#count-available').text(data.available);
                    $('#count-rented').text(data.rented);
                    $('#count-damaged').text(data.damage);
                    $('#count-repair').text(data.repair);
                }
            }
        });
    }

    // --- Helper Functions (Ported from equipment-stock.js) ---

    // Rent Details Modal
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

    // Rented Invoices List
    var rentedInvoicesTable;
    function loadRentedInvoices(equipmentId, equipmentName) {
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
                equipment_id: equipmentId
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

    // Repair Details
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
                    $("#rp-open-full").attr("href", "repair-job.php?job_id=" + job.id);

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
});
