$(document).ready(function () {
    var stockTable;
    var currentEquipmentId;

    // Initialize when modal is shown
    $("#department_stock").on("shown.bs.modal", function (e) {
        currentEquipmentId = $("#stock_equipment_id").val();
        console.log("Modal Shown. Equipment ID:", currentEquipmentId);
        loadStockTable();

        // Reset form
        $("#form-department-stock")[0].reset();
        $("#stock_equipment_id").val(currentEquipmentId);
        $("#save_stock").show();
        $("#update_stock").hide();
    });

    // Refresh equipment quantity when modal is closed
    $("#department_stock").on("hidden.bs.modal", function () {
        if (currentEquipmentId) {
            refreshEquipmentQuantity(currentEquipmentId);
        }
    });

    function refreshEquipmentQuantity(equipmentId) {
        $.ajax({
            url: "ajax/php/equipment-master.php",
            type: "POST",
            data: { action: "get_equipment_quantity", equipment_id: equipmentId },
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    $("#quantity").val(result.quantity);
                }
            }
        });
    }

    function loadStockTable() {
        if (!currentEquipmentId) {
            console.warn("No Equipment ID found for Stock Table");
            return;
        }

        if ($.fn.DataTable.isDataTable("#departmentStockTable")) {
            // If table exists, just reload it
            $("#departmentStockTable").DataTable().ajax.reload();
            return;
        }

        stockTable = $("#departmentStockTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/php/department-stock.php",
                type: "POST",
                data: function (d) {
                    d.fetch = true;
                    d.equipment_id = currentEquipmentId;
                },
                dataSrc: function (json) {
                    console.log("Stock Table Data Received:", json);
                    return json.data;
                }
            },
            columns: [
                { data: "key", title: "#ID" },
                { data: "department_name", title: "Department" },
                { data: "qty", title: "Qty" },
                {
                    data: "rental_status",
                    render: function (data) {
                        return '<span class="badge bg-success">Active</span>';
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return '<button class="btn btn-sm btn-info edit-stock me-1" data-id="' + data.id + '"><i class="uil uil-edit"></i></button>' +
                            '<button class="btn btn-sm btn-danger delete-stock" data-id="' + data.id + '"><i class="uil uil-trash-alt"></i></button>';
                    }
                }
            ],
            order: [[0, "desc"]],
            pageLength: 10
        });
    }

    // Add Stock
    $("#save_stock").click(function (e) {
        e.preventDefault();

        if (!$("#stock_department_id").val() || !$("#stock_qty").val()) {
            swal("Error", "Please fill all required fields", "error");
            return;
        }

        var formData = new FormData($("#form-department-stock")[0]);
        formData.append("create", true);

        $.ajax({
            url: "ajax/php/department-stock.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    swal({
                        title: "Success",
                        text: "Stock added successfully",
                        type: "success",
                        timer: 1500,
                        showConfirmButton: false
                    });

                    $("#form-department-stock")[0].reset();
                    $("#stock_equipment_id").val(currentEquipmentId);

                    // Reload table
                    if (stockTable) {
                        stockTable.ajax.reload();
                    } else {
                        loadStockTable();
                    }

                    // Refresh equipment quantity in main form
                    refreshEquipmentQuantity(currentEquipmentId);

                } else {
                    swal("Error", result.message || "Failed to add stock", "error");
                }
            },
            error: function () {
                swal("Error", "Server error", "error");
            }
        });
    });

    // Update Stock Logic
    $("#update_stock").click(function (e) {
        e.preventDefault();

        if (!$("#stock_department_id").val() || !$("#stock_qty").val()) {
            swal("Error", "Please fill all required fields", "error");
            return;
        }

        var formData = new FormData($("#form-department-stock")[0]);
        formData.append("update", true);

        $.ajax({
            url: "ajax/php/department-stock.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function (result) {
                if (result.status === "success") {
                    swal({
                        title: "Success",
                        text: "Stock updated successfully",
                        type: "success",
                        timer: 1500,
                        showConfirmButton: false
                    });

                    $("#form-department-stock")[0].reset();
                    $("#stock_equipment_id").val(currentEquipmentId);
                    $("#save_stock").show();
                    $("#update_stock").hide();

                    if (stockTable) {
                        stockTable.ajax.reload();
                    } else {
                        loadStockTable();
                    }

                    // Refresh equipment quantity in main form
                    refreshEquipmentQuantity(currentEquipmentId);
                } else {
                    swal("Error", "Failed to update stock", "error");
                }
            },
            error: function () {
                swal("Error", "Server error", "error");
            }
        });
    });

    // Edit Button Click
    $("#departmentStockTable").on("click", ".edit-stock", function () {
        var data = stockTable.row($(this).closest("tr")).data();

        $("#stock_id").val(data.id);
        $("#stock_equipment_id").val(data.equipment_id);
        $("#stock_department_id").val(data.department_id);
        $("#stock_qty").val(data.qty);

        $("#save_stock").hide();
        $("#update_stock").show();
    });

    // Delete Stock
    $("#departmentStockTable").on("click", ".delete-stock", function () {
        var id = $(this).data("id");

        swal({
            title: "Are you sure?",
            text: "You will not be able to recover this stock entry!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: "ajax/php/department-stock.php",
                type: "POST",
                data: { delete: true, id: id },
                dataType: "JSON",
                success: function (result) {
                    if (result.status === "success") {
                        swal({
                            title: "Deleted!",
                            text: "Stock entry has been deleted.",
                            type: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if (stockTable) {
                            stockTable.ajax.reload();
                        } else {
                            loadStockTable();
                        }

                        // Refresh equipment quantity in main form
                        refreshEquipmentQuantity(currentEquipmentId);
                    } else {
                        swal("Error", "Failed to delete", "error");
                    }
                }
            });
        });
    });
});
