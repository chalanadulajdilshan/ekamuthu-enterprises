jQuery(document).ready(function () {
    // Initialize DataTable
    var table = $('#stockCheckTable').DataTable({
        processing: true,
        serverSide: false, // We'll load all data at once
        ajax: function (data, callback, settings) {
            const selectedDate = $('#stock_date').val();
            if (!selectedDate) {
                callback({ data: [] });
                return;
            }

            $.ajax({
                url: "ajax/php/stock-check.php",
                type: "POST",
                data: {
                    action: 'get_stock_by_date',
                    date: selectedDate
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        callback({ data: response.data });
                    } else {
                        console.error("Error:", response.message);
                        callback({ data: [] });
                    }
                },
                error: function (xhr) {
                    console.error("AJAX Error:", xhr.responseText);
                    callback({ data: [] });
                }
            });
        },
        columns: [
            { data: "item_name", title: "Item Name" },
            { data: "quantity", title: "Quantity" }
        ],
        order: [[0, 'asc']], // Default sort by item name
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25,
        responsive: true,
        language: {
            paginate: {
                previous: "<i class='mdi mdi-chevron-left'>",
                next: "<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback: function () {
            $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
        }
    });

    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    $('#stock_date').val(today);

    // Load data on date change
    $('#stock_date').on('change', function () {
        table.ajax.reload();
    });

    // Initial load
    table.ajax.reload();
});
