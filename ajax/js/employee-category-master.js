jQuery(document).ready(function () {

    // Create Employee Category
    $("#create").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#name').val() || $('#name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter employee category name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
          
        } else {

            // Preloader start
            $('.someBlock').preloader();

            // Grab all form data
            var formData = new FormData($("#form-data")[0]);
            formData.append('create', true);

            $.ajax({
                url: "ajax/php/employee-category-master.php",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function (result) {
                    // Remove preloader
                    $('.someBlock').preloader('remove');

                    if (result.status === 'success') {
                        swal({
                            title: "Success!",
                            text: "Employee Category added successfully!",
                            type: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);

                    } else if (result.status === 'error') {
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

    // Update Employee Category
    $("#update").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#name').val() || $('#name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter employee category name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
         
        } else {

            // Preloader start
            $('.someBlock').preloader();

            // Grab all form data
            var formData = new FormData($("#form-data")[0]);
            formData.append('update', true);

            $.ajax({
                url: "ajax/php/employee-category-master.php",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    // Remove preloader
                    $('.someBlock').preloader('remove');

                    if (result.status == 'success') {
                        swal({
                            title: "Success!",
                            text: "Employee Category updated successfully!",
                            type: 'success',
                            timer: 2500,
                            showConfirmButton: false
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);

                    } else if (result.status === 'error') {
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

    // Reset form (New button)
    $("#new").click(function (e) {
        e.preventDefault();

        // Reset all fields in the form
        $('#form-data')[0].reset();

        // Reset selects to default option
        $('#name').prop('selectedIndex', 0);
        $("#create").show();
        $("#update").hide();
    });

    // Modal click append value to form
    $(document).on('click', '.select-employee-category', function () {
        $('#employee_category_id').val($(this).data('id'));
        $('#code').val($(this).data('code'));
        $('#name').val($(this).data('name'));
        $('#remark').val($(this).data('remark'));
    
        if ($(this).data('status') == 1) {
            $('#is_active').prop('checked', true);
        } else {
            $('#is_active').prop('checked', false);
        }
    
        $("#create").hide();
        $("#update").show();
        $('#employeeCategoryModal').modal('hide');
    });
    

    // Delete Employee Category
    $(document).on('click', '.delete-employee-category', function (e) {
        e.preventDefault();
    
        var categoryId = $('#employee_category_id').val();
        var categoryName = $('#name').val();
    
        if (!categoryId || categoryId === "") {
            swal({
                title: "Error!",
                text: "Please select an employee category first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
    
        swal({
            title: "Are you sure?",
            text: "Do you want to delete employee category '" + categoryName + "'?",
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
                    url: 'ajax/php/employee-category-master.php',
                    type: 'POST',
                    data: {
                        id: categoryId,
                        delete: true
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        $('.someBlock').preloader('remove');
    
                        if (response.status === 'success') {
                            swal({
                                title: "Deleted!",
                                text: "Employee Category has been deleted.",
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
