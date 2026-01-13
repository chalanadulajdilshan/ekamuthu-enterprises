$(document).ready(function() {

    // Initialize DataTable with buttons
    var arnHistoryTable = $('#arnHistoryTable').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "ordering": true,
        "info": true,
        "paging": true,
        "searching": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "language": {
            "emptyTable": "No data available in table",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "lengthMenu": "Show _MENU_ entries",
            "loadingRecords": "Loading...",
            "processing": "Processing...",
            "search": "Search:",
            "zeroRecords": "No matching records found"
        },
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            }
        ]
    });

    // Filter button click
    $('#filterBtn').on('click', function() {
        filterArnHistory();
    });

    // Reset button click
    $('#resetBtn').on('click', function() {
        $('#date_from').val('');
        $('#date_to').val('');
        $('#supplier_code').val('');
        $('#supplier_id').val('');
        $('#payment_type').val('');
        $('#grn_id').val('');
        filterArnHistory();
    });

    // Enter key on search input
    $('#grn_id').on('keypress', function(e) {
        if (e.which == 13) {
            filterArnHistory();
        }
    });

    function filterArnHistory() {
        var date_from = $('#date_from').val();
        var date_to = $('#date_to').val();
        var supplier = $('#supplier_id').val();
        var payment_type = $('#payment_type').val();
        var grn_id = $('#grn_id').val();

        $.ajax({
            url: 'ajax/php/arn-history.php',
            type: 'POST',
            data: {
                command: 'filter_arn_history',
                date_from: date_from,
                date_to: date_to,
                supplier: supplier,
                payment_type: payment_type,
                grn_id: grn_id
            },
            success: function(response) {
                arnHistoryTable.clear();
                if (response.trim() === '<tr><td colspan="10" class="text-center">No records found</td></tr>') {
                    arnHistoryTable.row.add(['<td colspan="10" class="text-center">No records found</td>', '', '', '', '', '', '', '', '', '']);
                } else {
                    // Parse and add rows
                    var tempDiv = $('<div>').html(response);
                    tempDiv.find('tr').each(function() {
                        var rowData = [];
                        $(this).find('td').each(function() {
                            rowData.push($(this).html());
                        });
                        arnHistoryTable.row.add(rowData);
                    });
                }
                arnHistoryTable.draw();
            },
            error: function() {
                alert('Error loading ARN history');
            }
        });
    }
});


// ARN Modal
$("#arnModal").on("shown.bs.modal", function () {
    loadArnTable();
});

// Toggle Returns Rows
$(document).on('click', '.toggle-returns', function(e) {
    e.stopPropagation();
    var btn = $(this);
    var arnId = btn.data('arn-id');
    var parentRow = btn.closest('tr');
    var isExpanded = btn.hasClass('expanded');
    
    // Remove existing return rows for this ARN
    $('tr.return-row[data-parent-arn="' + arnId + '"]').remove();
    
    if (isExpanded) {
        btn.removeClass('expanded');
        btn.find('i').removeClass('fa-chevron-up').addClass('fa-undo-alt');
        return;
    }
    
    btn.addClass('expanded');
    btn.find('i').removeClass('fa-undo-alt').addClass('fa-chevron-up');
    
    // Fetch returns data
    $.ajax({
        url: 'ajax/php/arn-history.php',
        type: 'POST',
        dataType: 'json',
        data: {
            command: 'get_arn_returns',
            arn_id: arnId
        },
        success: function(response) {
            if (response.status === 'success' && response.returns.length > 0) {
                var returnRows = '';
                
                response.returns.forEach(function(ret, idx) {
                    // Return card row
                    returnRows += '<tr class="return-row" data-parent-arn="' + arnId + '" data-return-id="' + ret.id + '">';
                    returnRows += '<td colspan="10" class="p-0 border-0">';
                    returnRows += '<div class="return-card">';
                    
                    // Card Header
                    returnRows += '<div class="return-card-header">';
                    returnRows += '<div class="d-flex justify-content-between align-items-center flex-wrap">';
                    returnRows += '<div class="return-info">';
                    returnRows += '<span class="return-badge"><i class="fas fa-undo-alt"></i> Return #' + (idx + 1) + '</span>';
                    returnRows += '<span class="return-info-item"><i class="fas fa-hashtag"></i> <strong>' + escapeHtml(ret.ref_no) + '</strong></span>';
                    returnRows += '<span class="return-info-item"><i class="fas fa-calendar-alt"></i> ' + escapeHtml(ret.return_date) + '</span>';
                    if (ret.department_name) {
                        returnRows += '<span class="return-info-item"><i class="fas fa-building"></i> ' + escapeHtml(ret.department_name) + '</span>';
                    }
                    if (ret.return_reason) {
                        returnRows += '<span class="return-info-item"><i class="fas fa-comment-alt"></i> ' + escapeHtml(ret.return_reason) + '</span>';
                    }
                    returnRows += '</div>';
                    returnRows += '<div class="mt-2 mt-md-0">';
                    returnRows += '<span class="return-total-badge"><i class="fas fa-coins"></i> ' + formatNumber(ret.total_amount) + '</span>';
                    returnRows += '</div>';
                    returnRows += '</div>';
                    returnRows += '</div>';
                    
                    // Card Body - Items Table
                    returnRows += '<div class="return-card-body">';
                    if (ret.items && ret.items.length > 0) {
                        returnRows += '<table class="table return-items-table">';
                        returnRows += '<thead>';
                        returnRows += '<tr>';
                        returnRows += '<th style="width:50px;">#</th>';
                        returnRows += '<th><i class="fas fa-barcode me-1"></i>Item Code</th>';
                        returnRows += '<th><i class="fas fa-box me-1"></i>Item Name</th>';
                        returnRows += '<th class="text-end"><i class="fas fa-cubes me-1"></i>Qty</th>';
                        returnRows += '<th class="text-end"><i class="fas fa-tag me-1"></i>Unit Price</th>';
                        returnRows += '<th class="text-end"><i class="fas fa-calculator me-1"></i>Net Amount</th>';
                        returnRows += '</tr>';
                        returnRows += '</thead>';
                        returnRows += '<tbody>';
                        
                        ret.items.forEach(function(item, itemIdx) {
                            returnRows += '<tr>';
                            returnRows += '<td class="text-center"><span class="badge bg-secondary">' + (itemIdx + 1) + '</span></td>';
                            returnRows += '<td><code>' + escapeHtml(item.item_code || '-') + '</code></td>';
                            returnRows += '<td>' + escapeHtml(item.item_name || '-') + '</td>';
                            returnRows += '<td class="text-end fw-bold">' + formatNumber(item.quantity, 0) + '</td>';
                            returnRows += '<td class="text-end">' + formatNumber(item.unit_price) + '</td>';
                            returnRows += '<td class="text-end"><strong class="text-danger">' + formatNumber(item.net_amount) + '</strong></td>';
                            returnRows += '</tr>';
                        });
                        
                        returnRows += '</tbody>';
                        returnRows += '</table>';
                    } else {
                        returnRows += '<div class="no-returns-msg"><i class="fas fa-info-circle me-2"></i>No items in this return</div>';
                    }
                    returnRows += '</div>';
                    
                    returnRows += '</div>';
                    returnRows += '</td>';
                    returnRows += '</tr>';
                });
                
                // Insert after the parent row
                parentRow.after(returnRows);
            } else {
                // No returns found
                var noReturnRow = '<tr class="return-row" data-parent-arn="' + arnId + '">';
                noReturnRow += '<td colspan="10" class="p-3 border-0">';
                noReturnRow += '<div class="return-card">';
                noReturnRow += '<div class="no-returns-msg"><i class="fas fa-info-circle me-2"></i>No return details found for this ARN</div>';
                noReturnRow += '</div>';
                noReturnRow += '</td>';
                noReturnRow += '</tr>';
                parentRow.after(noReturnRow);
            }
        },
        error: function() {
            alert('Error loading return details');
            btn.removeClass('expanded');
            btn.find('i').removeClass('fa-chevron-up').addClass('fa-undo-alt');
        }
    });
});

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to format numbers
function formatNumber(num, decimals) {
    if (decimals === undefined) decimals = 2;
    return parseFloat(num || 0).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function loadArnTable() {
    // Destroy if already initialized
    if ($.fn.DataTable.isDataTable("#arnTable")) {
        $("#arnTable").DataTable().destroy();
    }

    $("#arnTable").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/php/arn-master.php",
            type: "POST",
            data: function (d) {
                d.filter = true;
            },
            dataSrc: function (json) {
                return json.data;
            },
            error: function (xhr) {
                console.error("Server Error Response:", xhr.responseText);
                alert('Error loading ARN table: ' + xhr.responseText);
            },
        },
        columns: [
            { data: "id", title: "#" },
            { data: "arn_no", title: "ARN No" },
            { data: "invoice_date", title: "Date" },
            { data: "supplier_name", title: "Supplier" },
            { data: "payment_type", title: "Payment Type" },
            { data: "total_arn_value", title: "Amount" },
            { data: "paid_amount", title: "Paid" },
            { data: "status", title: "Status" },
        ],
        order: [[1, "desc"]],
        pageLength: 100,
    });

    $("#arnTable tbody").on("click", "tr", function () {
        var data = $("#arnTable").DataTable().row(this).data();

        if (data) {
            $("#grn_id").val(data.arn_no);
            $("#arnModal").modal("hide");
        }
    });
}
