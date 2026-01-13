jQuery(document).ready(function () {
    // Create Daily Income
    $("#create").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$("#date").val() || $("#date").val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a date",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#amount").val() || $("#amount").val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter an amount",
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
                url: "ajax/php/daily-income.php",
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
                            text: "Daily income added successfully!",
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
                            text: result.message || "Something went wrong.",
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

    // Update Daily Income (if needed later)
    $("#update").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$("#date").val() || $("#date").val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a date",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
        } else if (!$("#amount").val() || $("#amount").val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter an amount",
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
                url: "ajax/php/daily-income.php",
                type: "POST",
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    // Remove preloader
                    $(".someBlock").preloader("remove");

                    if (result.status == "success") {
                        swal({
                            title: "Success!",
                            text: "Daily income updated successfully!",
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
                            text: result.message || "Something went wrong.",
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

    // Reset form
    $("#new").click(function (e) {
        e.preventDefault();

        // Reset all fields in the form
        $("#form-data")[0].reset();

        $("#id").val("0");
        $("#create").show();
        $("#update").hide();
    });

    // Filter records by date range
    $("#filter_records").click(function (event) {
        event.preventDefault();

        var dateFrom = $("#filter_from_date").val();
        var dateTo = $("#filter_to_date").val();

        if (!dateFrom || !dateTo) {
            swal({
                title: "Error!",
                text: "Please select both from and to dates",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        if (dateFrom > dateTo) {
            swal({
                title: "Error!",
                text: "From date cannot be greater than To date",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return;
        }

        // Preloader start
        $(".someBlock").preloader();

        $.ajax({
            url: "ajax/php/daily-income.php",
            type: "POST",
            data: {
                fetch_records: true,
                date_from: dateFrom,
                date_to: dateTo
            },
            dataType: "json",
            success: function (result) {
                // Remove preloader
                $(".someBlock").preloader("remove");

                if (result.status === "success") {
                    // Update total amount
                    $("#total_amount").val(result.total_amount);

                    // Clear existing records
                    $("#incomeTableBody").empty();

                    if (result.records.length > 0) {
                        // Add records to table
                        result.records.forEach(function (record, index) {
                            var row = '<tr class="select-income-record" data-id="' + record.id + '" ' +
                                'data-date="' + record.date + '" ' +
                                'data-amount="' + record.amount + '" ' +
                                'data-remark="' + (record.remark || '') + '">' +
                                '<td>' + (index + 1) + '</td>' +
                                '<td>' + record.date + '</td>' +
                                '<td>' + parseFloat(record.amount).toFixed(2) + '</td>' +
                                '<td>' + (record.remark || '-') + '</td>' +
                                '<td>' +
                                '<button class="btn btn-sm btn-warning edit-record" data-id="' + record.id + '">' +
                                '<i class="uil uil-edit"></i> Edit</button>' +
                                '</td>' +
                                '</tr>';
                            $("#incomeTableBody").append(row);
                        });
                    } else {
                        // No records found
                        $("#incomeTableBody").append(
                            '<tr id="noRecordsRow">' +
                            '<td colspan="5" class="text-center text-muted">No records found for the selected date range.</td>' +
                            '</tr>'
                        );
                    }
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Something went wrong while fetching records.",
                        type: "error",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            },
            error: function () {
                // Remove preloader
                $(".someBlock").preloader("remove");

                swal({
                    title: "Error!",
                    text: "Failed to fetch records. Please try again.",
                    type: "error",
                    timer: 2000,
                    showConfirmButton: false,
                });
            }
        });
    });

    // Edit record functionality
    $(document).on("click", ".edit-record", function () {
        var id = $(this).data("id");
        var row = $(this).closest("tr");

        // Populate form with record data
        $("#id").val(row.data("id"));
        $("#date").val(row.data("date"));
        $("#amount").val(row.data("amount"));
        $("#remark").val(row.data("remark"));

        // Show update button, hide create
        $("#create").hide();
        $("#update").show();

        // Scroll to top
        $('html, body').animate({
            scrollTop: 0
        }, 500);
    });
});
