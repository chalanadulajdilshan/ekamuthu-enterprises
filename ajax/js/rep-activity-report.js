$(document).ready(function () {
    var activity = { visits: [], collections: [] };

    function formatNumber(num) {
        return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    var statusBadge = {
        no_proof: 'bg-warning text-dark',
        proof_added: 'bg-info',
        verified: 'bg-success',
        rejected: 'bg-danger'
    };

    function resetTable(id) {
        if ($.fn.DataTable.isDataTable('#' + id)) {
            $('#' + id).DataTable().destroy();
        }
    }

    function renderRepSummary() {
        var reps = {};
        function rep(id, name) {
            if (!reps[id]) reps[id] = { name: name || '-', visits: 0, payments: 0, collected: 0, confirmed: 0, noProofCount: 0, noProofAmount: 0, verified: 0 };
            return reps[id];
        }
        activity.visits.forEach(function (v) { rep(v.rep_id, v.rep_name).visits++; });
        activity.collections.forEach(function (c) {
            var r = rep(c.rep_id, c.rep_name);
            var amount = parseFloat(c.amount);
            r.payments++;
            if (c.status !== 'rejected') r.collected += amount;
            if (c.status === 'proof_added' || c.status === 'verified') r.confirmed += amount;
            if (c.status === 'verified') r.verified += amount;
            if (c.status === 'no_proof') { r.noProofCount++; r.noProofAmount += amount; }
        });

        var rows = Object.keys(reps).map(function (id) {
            var r = reps[id];
            return '<tr><td>' + esc(r.name) + '</td><td class="text-end">' + r.visits + '</td><td class="text-end">' + r.payments + '</td>' +
                '<td class="text-end text-success fw-bold">' + formatNumber(r.collected) + '</td>' +
                '<td class="text-end">' + formatNumber(r.confirmed) + '</td>' +
                '<td class="text-end ' + (r.noProofCount ? 'text-warning fw-bold' : '') + '">' + r.noProofCount + '</td>' +
                '<td class="text-end ' + (r.noProofCount ? 'text-warning fw-bold' : '') + '">' + formatNumber(r.noProofAmount) + '</td>' +
                '<td class="text-end">' + formatNumber(r.verified) + '</td></tr>';
        }).join('');
        $('#repSummaryBody').html(rows || '<tr><td colspan="8" class="text-center text-muted">No rep activity for this period</td></tr>');
    }

    function renderCollections() {
        var status = $('#filterStatus').val();
        resetTable('collectionsTable');
        var rows = activity.collections.filter(function (c) {
            return !status || c.status === status;
        }).map(function (c) {
            var proof = c.proof_image
                ? '<a href="' + esc(c.proof_image) + '" target="_blank"><img src="' + esc(c.proof_image) + '" class="proof-thumb" alt="Proof"></a>'
                : '<span class="text-warning">None</span>';
            var remarks = [c.reference ? 'Ref: ' + esc(c.reference) : '', c.remark ? esc(c.remark) : '', c.admin_remark ? '<b>Office:</b> ' + esc(c.admin_remark) : '']
                .filter(Boolean).join('<br>');
            var actions = '';
            if (c.status === 'proof_added') {
                actions += '<button class="btn btn-sm btn-success set-status mb-1" data-id="' + c.id + '" data-status="verified">Verify</button> ';
            }
            if (c.status !== 'verified' && c.status !== 'rejected') {
                actions += '<button class="btn btn-sm btn-outline-danger set-status mb-1" data-id="' + c.id + '" data-status="rejected">Reject</button>';
            }
            return '<tr><td data-order="' + esc(c.collected_at) + '">' + esc(c.collected_at.substring(0, 16)) + '</td>' +
                '<td>' + esc(c.rep_name) + '</td><td>' + esc(c.bill_number) + '</td>' +
                '<td>' + esc((c.customer_code || '') + ' - ' + (c.customer_name || '')) + '</td>' +
                '<td>' + esc(c.method_label) + '</td>' +
                '<td class="text-end fw-bold" data-order="' + c.amount + '">' + formatNumber(c.amount) + '</td>' +
                '<td>' + proof + '</td>' +
                '<td><span class="badge ' + (statusBadge[c.status] || 'bg-secondary') + '">' + esc(c.status_label) + '</span></td>' +
                '<td class="wrap-cell"><small>' + (remarks || '-') + '</small></td>' +
                '<td class="text-center">' + (actions || '-') + '</td></tr>';
        }).join('');
        $('#collectionsTable tbody').html(rows);
        $('#collectionsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 50,
            columnDefs: [{ orderable: false, targets: [6, 9] }],
            language: { emptyTable: 'No payments for this period' }
        });
    }

    function renderVisits() {
        resetTable('visitsTable');
        var rows = activity.visits.map(function (v) {
            var gps = v.latitude && v.longitude
                ? '<a href="https://www.google.com/maps?q=' + encodeURIComponent(v.latitude + ',' + v.longitude) + '" target="_blank">Map</a>'
                : '-';
            return '<tr><td data-order="' + esc(v.visit_at) + '">' + esc(v.visit_at.substring(0, 16)) + '</td>' +
                '<td>' + esc(v.rep_name) + '</td><td>' + esc(v.bill_number) + '</td>' +
                '<td>' + esc((v.customer_code || '') + ' - ' + (v.customer_name || '')) + '</td>' +
                '<td>' + esc(v.outcome_label) + '</td><td>' + esc(v.promised_date || '-') + '</td>' +
                '<td class="wrap-cell"><small>' + esc(v.remark) + '</small></td><td>' + gps + '</td></tr>';
        }).join('');
        $('#visitsTable tbody').html(rows);
        $('#visitsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 50,
            language: { emptyTable: 'No visits for this period' }
        });
    }

    function loadData() {
        $.ajax({
            url: 'ajax/php/rep-activity-report.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_activity',
                from_date: $('#fromDate').val(),
                to_date: $('#toDate').val(),
                rep_id: $('#filterRep').val()
            },
            success: function (result) {
                if (result.status !== 'success') {
                    swal('Error', result.message || 'Failed to load data', 'error');
                    return;
                }
                activity = result.data;
                var t = activity.totals;
                $('#sumCollected').text(formatNumber(t.collected));
                $('#sumConfirmed').text(formatNumber(t.confirmed));
                $('#sumNoProof').text(t.no_proof_count + ' / ' + formatNumber(t.no_proof_amount));
                $('#sumVisits').text(activity.visits.length);
                renderRepSummary();
                renderCollections();
                renderVisits();
            },
            error: function () {
                swal('Error', 'Server error occurred', 'error');
            }
        });
    }

    $('#collectionsTable').on('click', '.set-status', function () {
        var id = $(this).data('id');
        var status = $(this).data('status');
        swal({
            title: status === 'verified' ? 'Verify this payment?' : 'Reject this payment?',
            text: 'Office remark (optional)',
            type: 'input',
            showCancelButton: true,
            closeOnConfirm: false,
            inputPlaceholder: status === 'verified' ? 'e.g. Slip checked' : 'e.g. Slip not clear, resend'
        }, function (remark) {
            if (remark === false) return;
            $.post('ajax/php/rep-activity-report.php', { action: 'set_collection_status', id: id, status: status, admin_remark: remark }, function (result) {
                if (result.status === 'success') {
                    swal({ title: 'Done', text: result.message, type: 'success', timer: 1500, showConfirmButton: false });
                    loadData();
                } else {
                    swal('Error', result.message || 'Action failed', 'error');
                }
            }, 'json');
        });
    });

    $('#filterStatus').on('change', renderCollections);
    $('#searchBtn').click(loadData);

    $('.date-picker').datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
    var today = new Date();
    var todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    $('#fromDate, #toDate').val(todayStr);

    loadData();
});
