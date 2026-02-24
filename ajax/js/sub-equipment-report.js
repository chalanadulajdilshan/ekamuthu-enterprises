jQuery(document).ready(function () {
    // Initialize datepickers
    if ($.fn.datepicker) {
        $("#date_from, #date_to").datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true
        });
    }

    // Load initial report data
    loadReportData();

    // Filter button click
    $("#filterBtn").click(function (e) {
        e.preventDefault();
        loadReportData();
    });

    // Print button click
    $("#printBtn").click(function (e) {
        e.preventDefault();
        window.print();
    });

    // Export Excel button click
    $("#exportExcelBtn").click(function (e) {
        e.preventDefault();
        exportToExcel();
    });

    // Function to load report data
    function loadReportData() {
        var formData = $("#filterForm").serialize();
        formData += '&action=get_report';

        $("#reportTableBody").html('<tr><td colspan="11" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');

        $.ajax({
            url: "ajax/php/sub-equipment-report.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    displayReportData(response.data, response.statistics);
                    updateReportInfo();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to load report data'
                    });
                    $("#reportTableBody").html('<tr><td colspan="11" class="text-center text-danger">Error loading data</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                console.error("Error loading report:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load report data. Please try again.'
                });
                $("#reportTableBody").html('<tr><td colspan="11" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    // Function to display report data
    function displayReportData(data, statistics) {
        var tbody = $("#reportTableBody");
        tbody.empty();

        if (data.length === 0) {
            tbody.html('<tr><td colspan="11" class="text-center text-muted">No records found</td></tr>');
        } else {
            $.each(data, function (index, item) {
                var row = '<tr>';
                row += '<td>' + (index + 1) + '</td>';
                row += '<td>' + (item.code || '-') + '</td>';
                row += '<td>' + (item.equipment_code ? item.equipment_code + ' - ' + item.equipment_name : '-') + '</td>';
                row += '<td>' + (item.department_name || '-') + '</td>';
                row += '<td>' + (item.purchase_date || '-') + '</td>';
                row += '<td>' + (item.value ? parseFloat(item.value).toFixed(2) : '0.00') + '</td>';
                row += '<td>' + (item.brand || '-') + '</td>';
                row += '<td>' + (item.company_customer_name || '-') + '</td>';
                
                // Condition badge
                var conditionBadge = '-';
                if (item.condition_type) {
                    var badgeClass = item.condition_type === 'new' ? 'bg-success' : 'bg-warning';
                    conditionBadge = '<span class="badge ' + badgeClass + '">' + item.condition_type.toUpperCase() + '</span>';
                }
                row += '<td>' + conditionBadge + '</td>';

                // Image thumbnail
                var imageThumb = '-';
                if (item.image) {
                    imageThumb = '<a href="' + item.image + '" target="_blank"><img src="' + item.image + '" alt="Equipment" style="width:40px;height:40px;object-fit:cover;border-radius:4px;"></a>';
                }
                row += '<td class="no-print">' + imageThumb + '</td>';

                // Status badge
                var statusBadge = '-';
                if (item.rental_status) {
                    var statusClass = 'bg-secondary';
                    var statusLabel = item.rental_status.toUpperCase();
                    
                    if (item.rental_status === 'available' || item.rental_status === 'returned') {
                        statusClass = 'bg-success';
                        statusLabel = 'AVAILABLE';
                    } else if (item.rental_status === 'rent') {
                        statusClass = 'bg-primary';
                        statusLabel = 'RENTED';
                    } else if (item.rental_status === 'damage') {
                        statusClass = 'bg-danger';
                        statusLabel = 'DAMAGED';
                    } else if (item.rental_status === 'repair') {
                        statusClass = 'bg-warning';
                        statusLabel = 'REPAIR';
                    }
                    
                    statusBadge = '<span class="badge ' + statusClass + '">' + statusLabel + '</span>';
                }
                row += '<td>' + statusBadge + '</td>';
                
                row += '</tr>';
                tbody.append(row);
            });
        }

        // Update statistics
        $("#totalValue").text(statistics.total_value);
        $("#newCount").text(statistics.new_count);
        $("#usedCount").text(statistics.used_count);
        $("#totalCount").text(statistics.total_count);
    }

    // Function to update report info text
    function updateReportInfo() {
        var info = [];
        
        var dateFrom = $("#date_from").val();
        var dateTo = $("#date_to").val();
        var condition = $("#condition_filter").val();
        var equipment = $("#equipment_filter option:selected").text();
        var brand = $("#brand_filter").val();
        var status = $("#status_filter option:selected").text();

        if (dateFrom || dateTo) {
            var dateRange = 'Date: ';
            if (dateFrom && dateTo) {
                dateRange += dateFrom + ' to ' + dateTo;
            } else if (dateFrom) {
                dateRange += 'from ' + dateFrom;
            } else {
                dateRange += 'until ' + dateTo;
            }
            info.push(dateRange);
        }

        if (condition) {
            info.push('Condition: ' + condition.toUpperCase());
        }

        if (equipment && equipment !== 'All Equipment') {
            info.push('Equipment: ' + equipment);
        }

        if (brand) {
            info.push('Brand: ' + brand);
        }

        if (status && status !== 'All Status') {
            info.push('Status: ' + status);
        }

        if (info.length > 0) {
            $("#reportInfo").text('Filtered by: ' + info.join(' | '));
        } else {
            $("#reportInfo").text('Showing all sub-equipment items');
        }
    }

    // Function to export to Excel (CSV)
    function exportToExcel() {
        var formData = $("#filterForm").serialize();
        formData += '&action=export_excel';

        // Create a hidden form to submit
        var form = $('<form>', {
            'method': 'POST',
            'action': 'ajax/php/sub-equipment-report.php',
            'target': '_blank'
        });

        // Add form data as hidden inputs
        $.each(formData.split('&'), function(index, field) {
            var parts = field.split('=');
            form.append($('<input>', {
                'type': 'hidden',
                'name': decodeURIComponent(parts[0]),
                'value': decodeURIComponent(parts[1] || '')
            }));
        });

        // Append to body, submit, and remove
        $('body').append(form);
        form.submit();
        form.remove();

        Swal.fire({
            icon: 'success',
            title: 'Export Started',
            text: 'Your report is being downloaded...',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Clear filters
    $("#filterForm").on("reset", function() {
        setTimeout(function() {
            loadReportData();
        }, 100);
    });
});
