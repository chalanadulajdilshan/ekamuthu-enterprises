jQuery(document).ready(function ($) {
    // Initialize datepickers with range validation
    $(".date-picker").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        onSelect: function(selectedDate) {
            var input = $(this);
            var dateMin = null;
            
            if (input.attr('id') === 'date') {
                // If this is the 'from' date, update the 'to' date's minDate
                dateMin = $(this).datepicker('getDate');
                $("#date_to").datepicker("option", "minDate", dateMin);
                
                // If 'to' date is before 'from' date, reset it
                var toDate = $("#date_to").datepicker('getDate');
                if (toDate && toDate < dateMin) {
                    $("#date_to").datepicker('setDate', dateMin);
                }
            }
        }
    });

    // Set initial min date for 'to' date picker
    $("#date").on('change', function() {
        var fromDate = $(this).datepicker('getDate');
        if (fromDate) {
            $("#date_to").datepicker("option", "minDate", fromDate);
        }
    });

    // Initialize DataTable
    var cashbookTable = $('#cashbook-table').DataTable({
        responsive: true,
        searching: true,
        ordering: true,
        paging: true,
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25,
        language: {
            emptyTable: "No pending checks found for the selected date range",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)",
            lengthMenu: "Show _MENU_ entries",
            search: "Search:",
            zeroRecords: "No matching records found"
        },
        columnDefs: [
            { orderable: false, targets: [0] }, // Disable sorting on the # column
            { className: 'text-end', targets: [5] } // Right-align amount column
        ],
        order: [[2, 'desc']] // Default sort by Check Date descending
    });

    // Filter button click handler
    $("#btn-filter").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#date').val() || $('#date').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a date from!",
                type: "error",
                timer: 2000,
                showConfirmButton: false,
            });
            return false;
        } else if (!$('#date_to').val() || $('#date_to').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please select a date to!",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
            return false;
        }

        // Show preloader
        $('.someBlock').preloader({
            text: 'Loading...',
            zIndex: '99999'
        });

        var date = $('#date').val();
        var dateTo = $('#date_to').val();

        $.ajax({
            url: "ajax/php/pending-check.php",
            type: 'POST',
            dataType: 'json',
            data: {
                date: date,
                date_to: dateTo,
                action: 'filter'
            },
            success: function (result) {
                // Hide preloader on success
                $('.someBlock').preloader('remove');
                
                if (result.status === 'success') {
                    updatePendingChecksTable(result.checks || []);
                } else {
                    swal({
                        title: "Error!",
                        text: result.message || "Something went wrong.",
                        type: 'error',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr, status, error) {
                // Hide preloader on error
                $('.someBlock').preloader('remove');
                console.error("Error:", error);
                swal({
                    title: "Error!",
                    text: "Failed to load data. Please try again.",
                    type: 'error'
                });
            }
        });
        
        return false;
    });


 $('#btn-reset-filter').on('click', function(e) {
    e.preventDefault();
    
    // Reset the form
    $('form').trigger('reset');
    
    // Clear the DataTable
    var cashbookTable = $('#pending-check-table').DataTable();
    cashbookTable.clear().draw();
    
    // Reset the total
    $('#total-pb-check-value').text('0.00');
 });
});

// Update table with data using DataTables
function updatePendingChecksTable(checks) {
    var cashbookTable = $('#pending-check-table').DataTable();
    
    // Clear existing data
    cashbookTable.clear().draw();
    
    var totalAmount = 0;
    
    if (checks.length > 0) {
        // Add data rows
        $.each(checks, function(index, check) {
            totalAmount += parseFloat(check.amount || 0);
            
            cashbookTable.row.add([
                index + 1,
                check.cheq_no || 'N/A',
                check.cheq_date || 'N/A',
                check.bank_name || 'N/A',
                check.branch_name || 'N/A',
                '<div class="text-end">' + parseFloat(check.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div>'
            ]).draw(false);
        });
    }
    
    // Update total amount in footer
    var totalDisplay = totalAmount > 0 ? totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
    $('#total-pb-check-value').text(totalDisplay);
}

// Handle check settlement
function settleCheck(checkId, rowElement) {
    swal({
        title: "Are you sure?",
        text: "This will mark the check as settled. This action cannot be undone!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#34c38f",
        cancelButtonColor: "#f46a6a",
        confirmButtonText: "Yes, settle it!",
        cancelButtonText: "Cancel"
    }).then(function(result) {
        if (result.value) {
            $.ajax({
                url: "ajax/php/pending-check.php",
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'settle_check',
                    check_id: checkId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        rowElement.fadeOut(400, function() {
                            $(this).remove();
                            updateTotalAmount();
                        });
                        swal("Success!", response.message, "success");
                    } else {
                        swal("Error!", response.message, "error");
                    }
                },
                error: function() {
                    swal("Error!", "Failed to connect to server", "error");
                }
            });
        }
    });
}

// Update total amount
function updateTotalAmount() {
    var total = 0;
    $('#pending-check-table tr').each(function() {
        if ($(this).find('td').length > 1) {
            var amountText = $(this).find('td:eq(5)').text().replace(/,/g, '');
            var amount = parseFloat(amountText) || 0;
            total += amount;
        }
    });
    $('#total-pb-check-value').text(total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}
