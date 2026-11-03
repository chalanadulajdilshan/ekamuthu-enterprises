$(document).ready(function () {
    var allBills = [];
    var billMap = {};
    var selected = { followupTable: {}, noFollowupTable: {} };

    function formatNumber(num) {
        return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function lateBadgeClass(days) {
        if (days > 90) return 'bg-danger';
        if (days > 30) return 'bg-warning text-dark';
        return 'bg-info';
    }

    function matchesFilters(bill) {
        var location = $('#filterLocation').val().trim().toLowerCase();
        if (location && (bill.location || '').toLowerCase().indexOf(location) === -1) return false;

        var late = $('#filterLate').val();
        if (late) {
            var range = late.split('-');
            var min = parseInt(range[0], 10);
            var max = range[1] ? parseInt(range[1], 10) : Infinity;
            if (bill.overdue_days < min || bill.overdue_days > max) return false;
        }

        var rep = $('#filterRep').val();
        if (rep === 'none' && bill.rep_id) return false;
        if (rep && rep !== 'none' && String(bill.rep_id) !== rep) return false;

        return true;
    }

    function buildRow(bill, lastColumn) {
        var items = bill.items.map(function (it) {
            return '<li>' + esc(it.item) + (it.sub_equipment ? ' / ' + esc(it.sub_equipment) : '') + ' &times; ' + it.pending_qty + '</li>';
        }).join('');

        return '<tr data-rent="' + bill.rent_id + '">' +
            '<td class="text-center"><input type="checkbox" class="form-check-input row-check" value="' + bill.rent_id + '"></td>' +
            '<td>' + esc(bill.bill_number) + '<br><small class="text-muted">' + esc(bill.bill_date) + '</small></td>' +
            '<td>' + esc(bill.customer_code + ' - ' + bill.customer_name) + '<br><small class="text-muted">' + esc(bill.mobile_number) + '</small></td>' +
            '<td class="location-cell"><small>' + esc(bill.location || '-') + '</small></td>' +
            '<td><ul class="items-list">' + items + '</ul></td>' +
            '<td>' + esc(bill.due_date) + '</td>' +
            '<td data-order="' + bill.overdue_days + '"><span class="badge late-badge ' + lateBadgeClass(bill.overdue_days) + '">' + esc(bill.late_text) + '</span>' +
            '<br><small class="text-muted">' + bill.overdue_days + ' days</small></td>' +
            '<td class="text-end text-success fw-bold" data-order="' + bill.paid_total + '">' + formatNumber(bill.paid_total) + '</td>' +
            '<td class="text-end text-danger fw-bold" data-order="' + bill.projected_outstanding + '">' + formatNumber(bill.projected_outstanding) + '</td>' +
            '<td class="text-end text-warning fw-bold" data-order="' + bill.recorded_outstanding + '">' + formatNumber(bill.recorded_outstanding) + '</td>' +
            '<td>' + lastColumn + '</td>' +
            '<td class="text-center"><button class="btn btn-sm btn-outline-success view-payments" data-rent="' + bill.rent_id + '">View</button></td>' +
            '</tr>';
    }

    function renderTable(tableId, bills, lastColumnFn) {
        if ($.fn.DataTable.isDataTable('#' + tableId)) {
            $('#' + tableId).DataTable().destroy();
        }
        selected[tableId] = {};
        $('#' + tableId + ' .select-all').prop('checked', false);

        $('#' + tableId + ' tbody').html(bills.map(function (b) {
            return buildRow(b, lastColumnFn(b));
        }).join(''));

        $('#' + tableId).DataTable({
            order: [[6, 'desc']],
            pageLength: 50,
            columnDefs: [{ orderable: false, targets: [0, 4, 11] }],
            language: { emptyTable: 'No overdue bills found' }
        });
        updateSelectedCount(tableId);
    }

    function render() {
        var followup = [];
        var noFollowup = [];
        var unassigned = 0;
        var projected = 0;
        var locations = {};

        allBills.forEach(function (bill) {
            if (bill.location) locations[bill.location] = true;
            if (!matchesFilters(bill)) return;

            if (bill.followup_required) {
                followup.push(bill);
                projected += parseFloat(bill.projected_outstanding || 0);
                if (!bill.rep_id) unassigned++;
            } else {
                noFollowup.push(bill);
            }
        });

        $('#locationList').html(Object.keys(locations).sort().map(function (l) {
            return '<option value="' + esc(l) + '">';
        }).join(''));

        $('#sumFollowup, #tabFollowupCount').text(followup.length);
        $('#sumUnassigned').text(unassigned);
        $('#sumNoFollowup, #tabNoFollowupCount').text(noFollowup.length);
        $('#sumProjected').text(formatNumber(projected));

        renderTable('followupTable', followup, function (b) {
            return b.rep_id
                ? '<span class="badge bg-primary">' + esc(b.rep_name) + '</span>' + (b.assigned_at ? '<br><small class="text-muted">' + esc(b.assigned_at.substring(0, 10)) + '</small>' : '')
                : '<span class="badge bg-light text-muted">Not assigned</span>';
        });
        renderTable('noFollowupTable', noFollowup, function (b) {
            return '<small>' + esc(b.note || '-') + '</small>';
        });
    }

    function loadData() {
        var asOfDate = $('#asOfDate').val();
        if (!asOfDate) {
            swal('Error', 'Please select As of Date', 'error');
            return;
        }

        $('#followupTable tbody, #noFollowupTable tbody').html('<tr><td colspan="12" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: 'ajax/php/rent-overdue-followup.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_overdue_bills', as_of_date: asOfDate },
            success: function (result) {
                if (result.status !== 'success') {
                    swal('Error', result.message || 'Failed to load data', 'error');
                    return;
                }
                allBills = result.data || [];
                billMap = {};
                allBills.forEach(function (b) { billMap[b.rent_id] = b; });
                render();
            },
            error: function () {
                swal('Error', 'Server error occurred', 'error');
            }
        });
    }

    function selectedIds(tableId) {
        return Object.keys(selected[tableId]);
    }

    function updateSelectedCount(tableId) {
        var count = selectedIds(tableId).length;
        $(tableId === 'followupTable' ? '#followupSelectedCount' : '#noFollowupSelectedCount').text(count);
    }

    function postAction(data, done) {
        $.ajax({
            url: 'ajax/php/rent-overdue-followup.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function (result) {
                if (result.status === 'success') {
                    swal({ title: 'Done', text: result.message, type: 'success', timer: 1500, showConfirmButton: false });
                    if (done) done();
                    loadData();
                } else {
                    swal('Error', result.message || 'Action failed', 'error');
                }
            },
            error: function () {
                swal('Error', 'Server error occurred', 'error');
            }
        });
    }

    function requireSelection(tableId) {
        var ids = selectedIds(tableId);
        if (!ids.length) {
            swal('No bills selected', 'Please tick the bills first.', 'warning');
            return null;
        }
        return ids;
    }

    // Row / select-all checkboxes (select-all covers every filtered row, across pages)
    $('#followupTable, #noFollowupTable').on('change', '.row-check', function () {
        var tableId = $(this).closest('table').attr('id');
        if (this.checked) selected[tableId][this.value] = true;
        else delete selected[tableId][this.value];
        updateSelectedCount(tableId);
    });

    $('.select-all').on('change', function () {
        var tableId = $(this).data('table');
        var checked = this.checked;
        var nodes = $('#' + tableId).DataTable().rows({ search: 'applied' }).nodes();
        $(nodes).find('.row-check').each(function () {
            this.checked = checked;
            if (checked) selected[tableId][this.value] = true;
            else delete selected[tableId][this.value];
        });
        updateSelectedCount(tableId);
    });

    $('#assignBtn').click(function () {
        var ids = requireSelection('followupTable');
        if (!ids) return;
        var repId = $('#assignRep').val();
        if (!repId) {
            swal('Select a rep', 'Please choose the rep to assign.', 'warning');
            return;
        }
        postAction({ action: 'assign_rep', rent_ids: ids.join(','), rep_id: repId });
    });

    $('#unassignBtn').click(function () {
        var ids = requireSelection('followupTable');
        if (!ids) return;
        postAction({ action: 'assign_rep', rent_ids: ids.join(','), rep_id: 0 });
    });

    $('#markNoFollowupBtn').click(function () {
        var ids = requireSelection('followupTable');
        if (!ids) return;
        swal({
            title: 'Mark ' + ids.length + ' bill(s) as no follow-up?',
            text: 'Reason (optional). Any rep assignment will be removed.',
            type: 'input',
            showCancelButton: true,
            closeOnConfirm: false,
            inputPlaceholder: 'e.g. Customer will return next week'
        }, function (note) {
            if (note === false) return;
            postAction({ action: 'set_followup', rent_ids: ids.join(','), followup_required: 0, note: note });
        });
    });

    $('#markFollowupBtn').click(function () {
        var ids = requireSelection('noFollowupTable');
        if (!ids) return;
        postAction({ action: 'set_followup', rent_ids: ids.join(','), followup_required: 1, note: '' });
    });

    // Payments modal
    $('#followupTable, #noFollowupTable').on('click', '.view-payments', function () {
        var bill = billMap[$(this).data('rent')];
        if (!bill) return;

        $('#payBillNo').text(bill.bill_number);
        $('#payCustomer').text(bill.customer_code + ' - ' + bill.customer_name + (bill.mobile_number ? ' (' + bill.mobile_number + ')' : ''));
        $('#payLate').text(bill.late_text + ' (' + bill.overdue_days + ' days, due ' + bill.due_date + ')');

        var groups = [
            { key: 'deposits', title: 'Deposits' },
            { key: 'returns', title: 'Paid at Partial Returns' },
            { key: 'settlements', title: 'Outstanding Settlements' }
        ];
        var html = '';
        groups.forEach(function (g) {
            var list = bill.payments[g.key] || [];
            var subtotal = 0;
            var rows = list.map(function (p) {
                subtotal += parseFloat(p.amount || 0);
                return '<tr><td>' + esc(p.date || '-') + '</td><td>' + esc(p.remark || '-') + '</td><td class="text-end">' + formatNumber(p.amount) + '</td></tr>';
            }).join('');
            if (!rows) rows = '<tr><td colspan="3" class="text-center text-muted">None</td></tr>';

            html += '<h6 class="fw-bold mt-2">' + g.title + '</h6>' +
                '<table class="table table-sm table-bordered mb-3"><thead class="table-light"><tr><th style="width:120px">Date</th><th>Details</th><th class="text-end" style="width:140px">Amount</th></tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
                '<tfoot><tr class="fw-bold"><td colspan="2" class="text-end">Subtotal</td><td class="text-end">' + formatNumber(subtotal) + '</td></tr></tfoot></table>';
        });
        html += '<div class="alert alert-success mb-0 d-flex justify-content-between fw-bold"><span>Total Paid</span><span>Rs. ' + formatNumber(bill.paid_total) + '</span></div>';

        $('#paymentsBody').html(html);
        $('#paymentsModal').modal('show');
    });

    // Filters are applied client-side; only the as-of date needs a reload
    $('#filterLate, #filterRep').on('change', render);
    $('#filterLocation').on('change', render);
    $('#filterLocation').on('keyup', function (e) {
        if (e.key === 'Enter') render();
    });

    $('#searchBtn').click(loadData);
    $('#resetBtn').click(function () {
        location.reload();
    });

    $('.date-picker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });

    var today = new Date();
    $('#asOfDate').val(today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0'));

    loadData();
});
