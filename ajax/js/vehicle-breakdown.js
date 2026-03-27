$(document).ready(function () {
    const resetForm = () => {
        $("#form-data")[0].reset();
        $("#id").val("");
        $("#save").removeClass("d-none");
        $("#update").addClass("d-none");
    };

    $("#add-new").click(function () {
        resetForm();
    });

    $("#form-data").submit(function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append("create", "1");

        $.ajax({
            url: "ajax/php/vehicle-breakdown.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    swal({
                        title: "Success",
                        text: res.message,
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    }, function () {
                        location.reload();
                    });
                } else {
                    swal("Error", res.message, "error");
                }
            }
        });
    });

    $("#update").click(function () {
        const formData = new FormData($("#form-data")[0]);
        formData.append("update", "1");

        $.ajax({
            url: "ajax/php/vehicle-breakdown.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    swal({
                        title: "Success",
                        text: res.message,
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    }, function () {
                        location.reload();
                    });
                } else {
                    swal("Error", res.message, "error");
                }
            }
        });
    });

    $(document).on("click", ".edit-btn", function () {
        const id = $(this).data("id");
        const vehicle_id = $(this).data("vehicle_id");
        const breakdown_date = $(this).data("breakdown_date");
        const resolved_date = $(this).data("resolved_date");
        const description = $(this).data("description");
        const breakdown_status = $(this).data("breakdown_status");

        $("#id").val(id);
        $("#vehicle_id").val(vehicle_id);
        $("#breakdown_date").val(breakdown_date);
        $("#resolved_date").val(resolved_date);
        $("#description").val(description);
        $("#breakdown_status").val(breakdown_status);

        $("#save").addClass("d-none");
        $("#update").removeClass("d-none");

        $("html, body").animate({ scrollTop: 0 }, "slow");
    });

    $(document).on("click", ".delete-btn", function () {
        const id = $(this).data("id");

        swal({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }, function (isConfirmed) {
            if (isConfirmed) {
                $.ajax({
                    url: "ajax/php/vehicle-breakdown.php",
                    type: "POST",
                    data: { id: id, delete: "1" },
                    dataType: "json",
                    success: function (res) {
                        if (res.status === "success") {
                            swal({
                                title: "Deleted!",
                                text: res.message,
                                type: "success",
                                timer: 2000,
                                showConfirmButton: false
                            }, function () {
                                location.reload();
                            });
                        } else {
                            swal("Error", res.message, "error");
                        }
                    }
                });
            }
        });
    });
});
