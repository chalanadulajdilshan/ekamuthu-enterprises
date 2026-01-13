jQuery(document).ready(function () {

    // Create Service
    $("#create-service").click(function (event) {

        event.preventDefault();

        // Validation
        if (!$('#service_code').val() || $('#service_code').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter service code",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#service_name').val() || $('#service_name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter service name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#service_price').val() || isNaN($('#service_price').val())) {
            swal({
                title: "Error!",
                text: "Please enter a valid price",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('create', true);

            $.ajax({
                url: "ajax/php/service.php",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    $('.someBlock').preloader('remove');

                    if (result.status === 'success') {
                        swal({
                            title: "Success!",
                            text: "Service added successfully!",
                            type: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);

                    } else {
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            });
        }
        return false;
    });

    // Update Service
    $("#update-service").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#service_id').val() || $('#service_id').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter service ID",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#service_name').val() || $('#service_name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter service name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('update', true);

            $.ajax({
                url: "ajax/php/service.php",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    $('.someBlock').preloader('remove');

                    if (result.status === 'success') {
                        swal({
                            title: "Success!",
                            text: "Service updated successfully!",
                            type: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);

                    } else {
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            });
        }
        return false;
    });

    // Reset Service Form
    $("#new").click(function (e) {
        e.preventDefault();
        $('#form-data')[0].reset();
        $("#create-service").show();
        $("#update-service").hide();
    });

    // Select Service (populate fields)
    $(document).on('click', '.select-service', function () {
        $('#service_id').val($(this).data('id'));
        $('#service_name').val($(this).data('name'));
        $('#service_price').val($(this).data('price'));

        $("#create-service").hide();
        $("#update-service").show();
        $('#service_modal').modal('hide');
    });

    // Delete Service
    $(document).on('click', '.delete-service', function (e) {
        e.preventDefault();

        var serviceId = $('#service_id').val();
        var serviceName = $('#service_name').val();

        if (!serviceId || serviceId === "") {
            swal({
                title: "Error!",
                text: "Please select a service first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to delete service '" + serviceName + "'?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $('.someBlock').preloader();

                $.ajax({
                    url: 'ajax/php/service.php',
                    type: 'POST',
                    data: {
                        service_id: serviceId,
                        delete: true
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        $('.someBlock').preloader('remove');

                        if (response.status === 'success') {
                            swal({
                                title: "Deleted!",
                                text: "Service has been deleted.",
                                type: "success",
                                timer: 2000,
                                showConfirmButton: false
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
                                showConfirmButton: false
                            });
                        }
                    }
                });
            }
        });
    });

});
