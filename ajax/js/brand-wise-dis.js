jQuery(document).ready(function () {

    // Create Discount
    $("#create").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#category').val() || $('#category').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a category",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#brand_id').val() || $('#brand_id').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a brand",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#discount_percent_01').val() || $('#discount_percent_01').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter discount percentage",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('create', true);

            $.ajax({
                url: "ajax/php/brand-wise-dis.php",
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
                            text: "Discount added successfully!",
                            type: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        setTimeout(function () {
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

    // Update Discount
    $("#update").click(function (event) {
        event.preventDefault();

        if (!$('#category').val() || $('#category').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a category",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#brand_id').val() || $('#brand_id').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a brand",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#discount_percent_01').val() || $('#discount_percent_01').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter discount percentage",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('update', true);

            $.ajax({
                url: "ajax/php/brand-wise-dis.php",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    $('.someBlock').preloader('remove');

                    if (result.status == 'success') {
                        swal({
                            title: "Success!",
                            text: "Discount updated successfully!",
                            type: 'success',
                            timer: 2500,
                            showConfirmButton: false
                        });

                        setTimeout(function () {
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

    // Reset input fields
    $("#new").click(function (e) {
        e.preventDefault();
        $('#form-data')[0].reset();
        $('#category').prop('selectedIndex', 0);
        $('#brand_id').prop('selectedIndex', 0);
        $("#create").show();
    });

    // Populate form from table click
    $(document).on('click', '.select-dis', function () {
        var $this = $(this);
        $('#dis_id').val($this.data('id'));
        
        // Update category select
        var categoryId = $this.data('category');
        $('#category').val(categoryId).trigger('change');
        
        // Update brand select
        var brandId = $this.data('brand');
        $('#brand_id').val(brandId).trigger('change');
        
        // Update discount
        $('#discount_percent_01').val($this.data('discount_01'));
        $('#discount_percent_02').val($this.data('discount_02'));
        $('#discount_percent_03').val($this.data('discount_03'));

        // Show update button and hide create button
        $("#create").hide();
        $("#update").show();
        $(".delete-discount").show();
    });

    // Delete Discount
    $(document).on('click', '.delete-discount', function (e) {
        e.preventDefault();

        var disId = $('#dis_id').val();

        if (!disId || disId === "") {
            swal({
                title: "Error!",
                text: "Please select a discount first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to delete this discount?",
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
                    url: 'ajax/php/brand-wise-dis.php',
                    type: 'POST',
                    data: {
                        id: disId,
                        delete: true
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        $('.someBlock').preloader('remove');

                        if (response.status === 'success') {
                            swal({
                                title: "Deleted!",
                                text: "Discount has been deleted.",
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
