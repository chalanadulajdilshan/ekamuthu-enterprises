$(document).ready(function () {
    var table = $('#status_report_table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajax/php/equipment-status-report.php",
            "type": "POST",
            "data": function (d) {
                d.status = $('#status_filter').val();
            }
        },
        "columns": [
            { "data": "key" },
            { "data": "code" },
            { "data": "item_name" },
            { "data": "category" },
            { "data": "department" },
            {
                "data": "status",
                "render": function (data, type, row) {
                    var status = data ? data.toUpperCase() : 'UNKNOWN';
                    var badgeClass = 'bg-secondary';

                    if (status === 'AVAILABLE') badgeClass = 'bg-success';
                    else if (status === 'RENTED' || status === 'RENT') badgeClass = 'bg-primary';
                    else if (status === 'DAMAGE' || status === 'DAMAGED') badgeClass = 'bg-danger';
                    else if (status === 'REPAIR') badgeClass = 'bg-warning';

                    return '<span class="badge ' + badgeClass + '">' + status + '</span>';
                }
            },
            {
                "data": "quantity",
                "className": "text-end"
            }
        ],
        "order": [[1, "asc"]], // Sort by Code
        "pageLength": 25
    });

    $('#btn-filter').click(function () {
        table.ajax.reload();
    });

    $('#status_filter').change(function () {
        table.ajax.reload();
    });
});
