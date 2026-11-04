$(document).ready(function () {
    var stops = [];
    var available = [];

    function formatNumber(num) {
        return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function ymd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function params(extra) {
        return $.extend({ rep_id: $('#routeRep').val(), route_date: $('#routeDate').val() }, extra);
    }

    // Google Maps directions from the current location; Maps allows ~10 stops per link, so long routes are split
    function mapsLinks(locations) {
        var links = [];
        for (var i = 0; i < locations.length; i += 10) {
            var part = locations.slice(i, i + 10).map(function (l) { return l + ', Sri Lanka'; });
            var url = 'https://www.google.com/maps/dir/?api=1&travelmode=driving&destination=' + encodeURIComponent(part[part.length - 1]);
            if (part.length > 1) {
                url += '&waypoints=' + encodeURIComponent(part.slice(0, -1).join('|'));
            }
            links.push({ url: url, label: locations.length > 10 ? 'Stops ' + (i + 1) + '-' + Math.min(i + 10, locations.length) : 'Open in Google Maps' });
        }
        return links;
    }

    function renderStops() {
        var repName = $('#routeRep option:selected').text();
        $('#routeTitle').text(stops.length + ' stop(s) - ' + repName + ' - ' + $('#routeDate').val());

        if (!stops.length) {
            $('#stopsBody').html('<tr><td colspan="7" class="text-center text-muted">No stops on this route yet. Add bills from the right.</td></tr>');
            $('#routeActions').attr('style', 'display:none !important;');
            return;
        }

        var html = stops.map(function (s, i) {
            var done = s.visits.length > 0;
            var late = s.overdue
                ? '<span class="badge bg-danger">' + esc(s.overdue.late_text) + '</span>'
                : '<span class="badge bg-success">All returned</span>';
            var status = s.visits.map(function (v) {
                return '<div><small><b>' + esc(v.visit_at.substring(11, 16)) + '</b> ' + esc(v.outcome) + '</small></div>';
            }).join('');
            if (s.collected > 0) status += '<div><small class="text-success fw-bold">Rs. ' + formatNumber(s.collected) + ' collected</small></div>';
            if (!s.still_assigned) status += '<div><small class="text-danger">No longer assigned</small></div>';

            return '<tr>' +
                '<td class="text-center"><span class="stop-no ' + (done ? 'done' : '') + '">' + (i + 1) + '</span></td>' +
                '<td>#' + esc(s.bill_number) + '<br><b>' + esc(s.customer_name) + '</b><br><small class="text-muted">' + esc(s.mobile_number) + '</small></td>' +
                '<td class="wrap-cell"><small>' + esc(s.location || '-') + '</small></td>' +
                '<td>' + late + '</td>' +
                '<td class="text-end">' + (s.overdue ? formatNumber(s.overdue.outstanding) : '-') + '</td>' +
                '<td>' + (status || '<small class="text-muted">Not yet</small>') + '</td>' +
                '<td class="text-center text-nowrap no-print">' +
                '<button class="btn btn-sm btn-light move-stop" data-index="' + i + '" data-dir="-1" ' + (i === 0 ? 'disabled' : '') + ' title="Move up"><i class="mdi mdi-arrow-up"></i></button> ' +
                '<button class="btn btn-sm btn-light move-stop" data-index="' + i + '" data-dir="1" ' + (i === stops.length - 1 ? 'disabled' : '') + ' title="Move down"><i class="mdi mdi-arrow-down"></i></button> ' +
                '<button class="btn btn-sm btn-outline-danger remove-stop" data-rent="' + s.rent_id + '" title="Remove"><i class="mdi mdi-close"></i></button>' +
                '</td></tr>';
        }).join('');
        $('#stopsBody').html(html);

        var locations = stops.map(function (s) { return s.location; }).filter(Boolean);
        $('#mapsButtons').html(mapsLinks(locations).map(function (l) {
            return '<a href="' + l.url + '" target="_blank" class="btn btn-sm btn-success"><i class="mdi mdi-google-maps me-1"></i>' + esc(l.label) + '</a>';
        }).join(''));
        $('#routeActions').attr('style', '');
    }

    function renderAvailable() {
        var q = $('#availableSearch').val().trim().toLowerCase();
        var list = available.filter(function (b) {
            return !q || (b.bill_number + ' ' + b.customer_name + ' ' + b.location).toLowerCase().indexOf(q) !== -1;
        });
        $('#selectAllAvailable').prop('checked', false);

        if (!list.length) {
            $('#availableList').html('<div class="text-center text-muted small py-3">' +
                (available.length ? 'No bills match the filter' : 'No more assigned bills to add') + '</div>');
            return;
        }
        $('#availableList').html(list.map(function (b) {
            var lastVisit = b.last_visit ? '<br><small class="text-muted">Last visit ' + esc(b.last_visit.visit_at.substring(0, 10)) + ': ' + esc(b.last_visit.outcome) + '</small>' : '';
            return '<label class="list-group-item d-flex gap-2">' +
                '<input class="form-check-input flex-shrink-0 mt-1 available-check" type="checkbox" value="' + b.rent_id + '">' +
                '<span class="flex-grow-1"><b>' + esc(b.customer_name) + '</b> <small>#' + esc(b.bill_number) + '</small><br>' +
                '<small>' + esc(b.location || '-') + '</small><br>' +
                '<span class="badge bg-danger">' + esc(b.late_text) + '</span> <small class="text-danger">Rs. ' + formatNumber(b.outstanding) + '</small>' +
                lastVisit + '</span></label>';
        }).join(''));
    }

    function load() {
        if (!$('#routeRep').val()) {
            swal('Select a rep', 'Please choose the rep first.', 'warning');
            return;
        }
        $('#stopsBody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');
        $.post('ajax/php/rep-route-plan.php', params({ action: 'get_route' }), function (result) {
            if (result.status !== 'success') {
                swal('Error', result.message || 'Failed to load route', 'error');
                return;
            }
            stops = result.data.stops;
            available = result.data.available;
            renderStops();
            renderAvailable();
        }, 'json').fail(function () {
            swal('Error', 'Server error occurred', 'error');
        });
    }

    function post(extra, reload) {
        $.post('ajax/php/rep-route-plan.php', params(extra), function (result) {
            if (result.status !== 'success') {
                swal('Error', result.message || 'Action failed', 'error');
            }
            if (reload !== false) load();
        }, 'json').fail(function () {
            swal('Error', 'Server error occurred', 'error');
        });
    }

    $('#addStopsBtn').click(function () {
        var ids = $('.available-check:checked').map(function () { return this.value; }).get();
        if (!ids.length) {
            swal('No bills selected', 'Tick the bills to add to the route.', 'warning');
            return;
        }
        post({ action: 'add_stops', rent_ids: ids.join(',') });
    });

    $('#selectAllAvailable').on('change', function () {
        $('.available-check').prop('checked', this.checked);
    });

    $('#stopsBody').on('click', '.move-stop', function () {
        var i = $(this).data('index');
        var j = i + $(this).data('dir');
        var moved = stops.splice(i, 1)[0];
        stops.splice(j, 0, moved);
        renderStops();
        post({ action: 'reorder', rent_ids: stops.map(function (s) { return s.rent_id; }).join(',') }, false);
    });

    $('#stopsBody').on('click', '.remove-stop', function () {
        post({ action: 'remove_stop', rent_id: $(this).data('rent') });
    });

    $('#availableSearch').on('input', renderAvailable);
    $('#loadBtn').click(load);
    $('#routeRep').on('change', function () { if (this.value) load(); });

    function shiftDay(days) {
        var d = new Date($('#routeDate').val() + 'T00:00:00');
        d.setDate(d.getDate() + days);
        $('#routeDate').val(ymd(d));
        if ($('#routeRep').val()) load();
    }
    $('#prevDay').click(function () { shiftDay(-1); });
    $('#nextDay').click(function () { shiftDay(1); });

    $('.date-picker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        onSelect: function () { if ($('#routeRep').val()) load(); }
    });
    $('#routeDate').val(ymd(new Date()));
});
