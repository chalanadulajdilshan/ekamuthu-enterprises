jQuery(document).ready(function () {

    // Create Service Item
    $("#create").click(function (event) {
        
        event.preventDefault();

        // Validation
        if (!$('#item_code').val() || $('#item_code').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter item code",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#item_name').val() || $('#item_name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter item name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#cost').val() || isNaN($('#cost').val())) {
            swal({
                title: "Error!",
                text: "Please enter a valid cost",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#selling_price').val() || isNaN($('#selling_price').val())) {
            swal({
                title: "Error!",
                text: "Please enter a valid selling price",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#qty').val() || isNaN($('#qty').val())) {
            swal({
                title: "Error!",
                text: "Please enter a valid quantity",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('create', true);

            $.ajax({
                url: "ajax/php/service-item.php",
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
                            text: "Service Item added successfully!",
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

    // Update Service Item
    $("#update").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#item_code').val() || $('#item_code').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter item code",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#item_name').val() || $('#item_name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter item name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('update', true);

            $.ajax({
                url: "ajax/php/service-item.php",
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
                            text: "Service Item updated successfully!",
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

    $("#new").click(function (e) {
        e.preventDefault();
        $('#form-data')[0].reset();
        $("#create").show();
        $('#update').hide();
        $('#qty').prop('readonly', false);
        $('#qty_col').removeClass('col-md-1').addClass('col-md-2');
        $('#adjust_qty_col').addClass('d-none');
    });

    // Select Service Item (populate fields)
    $(document).on('click', '.select-item', function () {
        $('#item_id').val($(this).data('id'));
        $('#item_code').val($(this).data('code'));
        $('#item_name').val($(this).data('name'));
        $('#cost').val($(this).data('cost'));
        $('#selling_price').val($(this).data('selling'));
        $('#qty').val($(this).data('qty'));
        $('#current_qty').val($(this).data('qty'));
        $('#adjust_qty').val('');

        $("#create").hide();
        $('#update').show();
        $('#qty').prop('readonly', true);
        $('#qty_col').removeClass('col-md-2').addClass('col-md-1');
        $('#adjust_qty_col').removeClass('d-none');
        $('#service_item_modal').modal('hide');
    });

    // Dynamic qty calculation
    $('#adjust_qty').on('input', function() {
        var current = parseFloat($('#current_qty').val()) || 0;
        var adjust = parseFloat($(this).val()) || 0;
        var new_qty = current + adjust;
        $('#qty').val(new_qty.toFixed(2));
    });

    // Delete Service Item
    $(document).on('click', '.delete-service', function (e) {
        e.preventDefault();

        var itemId = $('#item_id').val();
        var itemName = $('#item_name').val();

        if (!itemId || itemId === "") {
            swal({
                title: "Error!",
                text: "Please select a service item first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to delete service item '" + itemName + "'?",
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
                    url: 'ajax/php/service-item.php',
                    type: 'POST',
                    data: {
                        item_id: itemId,
                        delete: true
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        $('.someBlock').preloader('remove');

                        if (response.status === 'success') {
                            swal({
                                title: "Deleted!",
                                text: "Service item has been deleted.",
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
