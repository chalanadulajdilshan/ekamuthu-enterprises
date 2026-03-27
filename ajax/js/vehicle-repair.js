jQuery(document).ready(function () {
    const resetForm = () => {
        $("#form-data")[0].reset();
        $("#amount").val("0.00");
        $("#repair_type").val("");
        $("#technician").val("");
        $("#description").val("");
        $("#remark").val("");
        $("#vehicle_id").val("");
        $("#vehicle_label").val("");
        $("#id").val("");
        $("#update, #delete").hide();
        $("#create").show();
    };

    // Save repair entry
    $("#create").on("click", function (event) {
        event.preventDefault();

        if (!$("#vehicle_id").val()) {
            swal({
                title: "Error!",
                text: "Please select a vehicle",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        }

        $(".someBlock").preloader();

        const formData = new FormData($("#form-data")[0]);
        formData.append("create", true);

        $.ajax({
            url: "ajax/php/vehicle-repair.php",
            type: "POST",
            data: formData,
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (result) {
                $(".someBlock").preloader("remove");

                if (result.status === "success") {
                    swal({
                        title: "Success!",
                        text: "Vehicle repair saved successfully!",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Something went wrong.",
                        type: "error",
                        timer: 2500,
                        showConfirmButton: false,
                    });
                }
            },
            error: function () {
                $(".someBlock").preloader("remove");
                swal({
                    title: "Error!",
                    text: "Request failed. Please try again.",
                    type: "error",
                    timer: 2500,
                    showConfirmButton: false,
                });
            },
        });

        return false;
    });

    // Reset form
    $("#new").on("click", function (event) {
        event.preventDefault();
        resetForm();
    });

    // Select vehicle from modal
    $(document).on("click", ".select-vehicle", function () {
        const id = $(this).data("id");
        const label = $(this).data("label");
        $("#vehicle_id").val(id);
        $("#vehicle_label").val(label);
        $("#vehicleSelectModal").modal("hide");
    });

    // Open repairs modal
    $("#openRepairModal, #repairModalBtn").on("click", function (e) {
        e.preventDefault();
        $("#repairSelectModal").modal("show");
    });

    // Select repair from modal
    $(document).on("click", ".select-repair", function () {
        const id = $(this).data("id");
        const ref_no = $(this).data("ref_no");
        const vehicle_id = $(this).data("vehicle_id");
        const vehicle_label = $(this).data("vehicle_label");
        const repair_date = $(this).data("repair_date");
        const repair_type = $(this).data("repair_type");
        const description = $(this).data("description");
        const technician = $(this).data("technician");
        const amount = $(this).data("amount");
        const remark = $(this).data("remark");

        $("#id").val(id);
        $("#ref_no").val(ref_no);
        $("#vehicle_id").val(vehicle_id);
        $("#vehicle_label").val(vehicle_label);
        $("#repair_date").val(repair_date);
        $("#repair_type").val(repair_type);
        $("#description").val(description);
        $("#technician").val(technician);
        $("#amount").val(amount);
        $("#remark").val(remark);

        $("#create").hide();
        $("#update, #delete").show();
        $("#repairSelectModal").modal("hide");
    });

    // Update repair
    $("#update").on("click", function (event) {
        event.preventDefault();
        const id = $("#id").val();
        if (!id) return false;
        if (!$("#vehicle_id").val()) {
            swal({ title: "Error!", text: "Please select a vehicle", type: "error", timer: 2000, showConfirmButton: false });
            return false;
        }

        $(".someBlock").preloader();
        const formData = new FormData($("#form-data")[0]);
        formData.append("id", id);
        formData.append("update", true);

        $.ajax({
            url: "ajax/php/vehicle-repair.php",
            type: "POST",
            data: formData,
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (result) {
                $(".someBlock").preloader("remove");
                if (result.status === "success") {
                    swal({ title: "Updated!", text: "Vehicle repair updated.", type: "success", timer: 1200, showConfirmButton: false });
                    setTimeout(function(){ window.location.reload(); }, 1200);
                } else {
                    swal({ title: "Error!", text: result.message || "Update failed.", type: "error", timer: 2500, showConfirmButton: false });
                }
            },
            error: function () {
                $(".someBlock").preloader("remove");
                swal({ title: "Error!", text: "Request failed. Please try again.", type: "error", timer: 2500, showConfirmButton: false });
            },
        });
        return false;
    });

    // Delete repair
    $("#delete").on("click", function (event) {
        event.preventDefault();
        const id = $("#id").val();
        if (!id) return false;
        swal({
            title: "Are you sure?",
            text: "Delete this repair entry?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, delete",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
        }, function (isConfirm) {
            if (!isConfirm) return;
            $(".someBlock").preloader();
            $.ajax({
                url: "ajax/php/vehicle-repair.php",
                type: "POST",
                data: { id: id, delete: true },
                dataType: "json",
                success: function (result) {
                    $(".someBlock").preloader("remove");
                    if (result.status === "success") {
                        swal({ title: "Deleted!", text: "Repair deleted.", type: "success", timer: 1200, showConfirmButton: false });
                        setTimeout(function(){ window.location.reload(); }, 1200);
                    } else {
                        swal({ title: "Error!", text: result.message || "Delete failed.", type: "error", timer: 2500, showConfirmButton: false });
                    }
                },
                error: function () {
                    $(".someBlock").preloader("remove");
                    swal({ title: "Error!", text: "Request failed. Please try again.", type: "error", timer: 2500, showConfirmButton: false });
                },
            });
        });
        return false;
    });
});
