jQuery(document).ready(function () {
    // Create Fuel Record
    $("#create").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$("#vehicle_id").val() || $("#vehicle_id").val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a vehicle",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#fuel_amount").val() || $("#fuel_amount").val() <= 0) {
            swal({
                title: "Error!",
                text: "Please enter valid fuel amount",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#date").val()) {
            swal({
                title: "Error!",
                text: "Please select date",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else {
            // Preloader start
            $(".someBlock").preloader();

            // Grab all form data
            var formData = new FormData($("#form-data")[0]);
            formData.append("create", true);

            $.ajax({
                url: "ajax/php/vehicle-fuel.php",
                type: "POST",
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function (result) {
                    // Remove preloader
                    $(".someBlock").preloader("remove");

                    if (result.status === "success") {
                        swal({
                            title: "Success!",
                            text: "Fuel record added Successfully!",
                            type: "success",
                            timer: 2000,
                            showConfirmButton: false,
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else if (result.status === "error") {
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: "error",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                },
            });
        }
        return false;
    });

    // Update Fuel Record
    $("#update").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$("#vehicle_id").val() || $("#vehicle_id").val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a vehicle",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#fuel_amount").val() || $("#fuel_amount").val() <= 0) {
            swal({
                title: "Error!",
                text: "Please enter valid fuel amount",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else {
            // Preloader start
            $(".someBlock").preloader();

            // Grab all form data
            var formData = new FormData($("#form-data")[0]);
            formData.append("update", true);

            $.ajax({
                url: "ajax/php/vehicle-fuel.php",
                type: "POST",
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function (result) {
                    // Remove preloader
                    $(".someBlock").preloader("remove");

                    if (result.status == "success") {
                        swal({
                            title: "Success!",
                            text: "Fuel record updated Successfully!",
                            type: "success",
                            timer: 2500,
                            showConfirmButton: false,
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else if (result.status === "error") {
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: "error",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                },
            });
        }
        return false;
    });

    // Delete Fuel Record
    $(document).on("click", ".delete-fuel", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        swal(
            {
                title: "Are you sure?",
                text: "Do you want to delete this fuel record?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
            },
            function (isConfirm) {
                if (isConfirm) {
                    $(".someBlock").preloader();
                    $.ajax({
                        url: "ajax/php/vehicle-fuel.php",
                        type: "POST",
                        data: {
                            id: id,
                            delete: true,
                        },
                        dataType: "json",
                        success: function (response) {
                            $(".someBlock").preloader("remove");
                            if (response.status === "success") {
                                swal({
                                    title: "Deleted!",
                                    text: "Fuel record has been deleted.",
                                    type: "success",
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                swal({
                                    title: "Error!",
                                    text: "Something went wrong.",
                                    type: "error",
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                            }
                        },
                    });
                }
            }
        );
    });

    // Edit Fuel Record (Fill data to form)
    $(document).on("click", ".edit-fuel", function (e) {
        e.preventDefault();
        $("#id").val($(this).data("id"));
        $("#vehicle_id").val($(this).data("vehicle_id"));
        $("#vehicle_no_display").val($(this).data("vehicle_no"));
        $("#fuel_amount").val($(this).data("fuel_amount"));
        $("#liters").val($(this).data("liters"));
        $("#date").val($(this).data("date"));
        $("#create").hide();
        $("#update").show();
        $('html, body').animate({
            scrollTop: $("#form-data").offset().top - 100
        }, 500);
    });

    // Reset form
    $("#new").click(function (e) {
        e.preventDefault();
        window.location.reload();
    });

    // Select vehicle from modal
    $(document).on("click", ".select-vehicle", function () {
        $("#vehicle_id").val($(this).data("id"));
        $("#vehicle_no_display").val($(this).data("vehicle_no"));
        $("#vehicleModel").modal("hide");
    });
});
