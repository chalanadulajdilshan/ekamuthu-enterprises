jQuery(document).ready(function () {

    // DataTable for modal
    var termsTable = $('#termsConditionTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ajax/php/terms-condition.php',
            type: 'POST',
            data: function (d) {
                d.filter = true;
            }
        },
        columns: [
            { data: 'key', width: '40px' },
            { data: 'sort_order', width: '80px' },
            {
                data: 'description',
                render: function (data) {
                    return data.length > 80 ? data.substring(0, 80) + '...' : data;
                }
            },
            { data: 'status_label', width: '80px' }
        ],
        order: [[1, 'asc']],
        pageLength: 10
    });

    // Click on table row to load data
    $('#termsConditionTable tbody').on('click', 'tr', function () {
        var data = termsTable.row(this).data();
        if (data) {
            $('#sort_order').val(data.sort_order);
            $('#description').val(data.description);
            $('#is_active').prop('checked', data.is_active == 1);
            $('#tc_id').val(data.id);

            $('#create').hide();
            $('#update').show();

            $('#TermsConditionModal').modal('hide');
        }
    });

    // New button - reset form
    $("#new").click(function (e) {
        e.preventDefault();

        $('#form-data')[0].reset();
        $('#is_active').prop('checked', true);
        $('#tc_id').val('');

        $('#create').show();
        $('#update').hide();
    });

    // Create
    $("#create").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#description').val() || $('#description').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter a description",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return false;
        }

        // Preloader start
        $('.someBlock').preloader();

        // Grab form data
        var formData = new FormData($("#form-data")[0]);
        formData.append('create', true);

        $.ajax({
            url: "ajax/php/terms-condition.php",
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
                        text: "Term & Condition added successfully!",
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
                        text: result.message || "Something went wrong.",
                        type: 'error',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }
        });

        return false;
    });

    // Update
    $("#update").click(function (event) {
        event.preventDefault();

        var id = $('#tc_id').val();
        if (!id) {
            swal({
                title: "Error!",
                text: "Please select a record to update",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return false;
        }

        // Validation
        if (!$('#description').val() || $('#description').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter a description",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return false;
        }

        // Preloader start
        $('.someBlock').preloader();

        // Grab form data
        var formData = new FormData($("#form-data")[0]);
        formData.append('update', true);
        formData.append('id', id);

        $.ajax({
            url: "ajax/php/terms-condition.php",
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

                if (result.status === 'success') {
                    swal({
                        title: "Success!",
                        text: "Term & Condition updated successfully!",
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

        return false;
    });

    // Delete
    $(document).on('click', '.delete-tc', function (e) {
        e.preventDefault();

        var id = $('#tc_id').val();

        if (!id || id === "") {
            swal({
                title: "Error!",
                text: "Please select a record to delete.",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to delete this term & condition?",
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
                    url: "ajax/php/terms-condition.php",
                    type: 'POST',
                    data: {
                        id: id,
                        delete: true
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        $('.someBlock').preloader('remove');

                        if (response.status === 'success') {
                            swal({
                                title: "Deleted!",
                                text: "Term & Condition has been deleted.",
                                type: "success",
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
