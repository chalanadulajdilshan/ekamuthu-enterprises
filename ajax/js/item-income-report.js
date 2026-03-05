$(document).ready(function () {
    function fmt(n) { return parseFloat(n || 0).toFixed(2); }
    function pc(v) { return v >= 0 ? 'profit-positive' : 'profit-negative'; }

    function loadReport() {
        var fromDate = $('#fromDate').val(), toDate = $('#toDate').val(), code = $('#equipmentCode').val().trim();
        if (!fromDate || !toDate) { swal('Error', 'Please select a valid date range', 'error'); return; }
        $('#summarySection').show();
        $('#reportTableBody').html('<tr><td colspan="9" class="text-center">Loading...</td></tr>');
        $.ajax({
            url: 'ajax/php/item-income-report.php', method: 'POST', dataType: 'json',
            data: { action: 'get_item_income_report', from_date: fromDate, to_date: toDate, equipment_code: code },
            success: function (res) {
                if (res.status !== 'success') { swal('Error', res.message || 'Failed', 'error'); return; }
                $('#sumValue').text(res.summary.total_value);
                $('#sumRentedQty').text(res.summary.total_rented_qty);
                $('#sumRentValue').text(res.summary.total_rent_value);
                $('#sumRepairCost').text(res.summary.total_repair_cost);
                $('#sumProfit').text(res.summary.total_profit);
                var d = res.data, rows = '', tV=0,tRQ=0,tBD=0,tRV=0,tPQ=0,tPC=0,tPR=0;
                if (!d.length) { $('#reportTableBody').html('<tr><td colspan="9" class="text-center">No records found</td></tr>'); return; }
                d.forEach(function(eq, i) {
                    var id='eq_'+i, t=eq.totals, hasSub=eq.sub_equipment.some(function(s){return s.sub_equipment_code!==null;});
                    var btn = hasSub ? '<button class="btn btn-sm btn-outline-primary toggle-btn" data-target="'+id+'">+</button>' : '';
                    var label = eq.equipment_code + ' - ' + eq.equipment_name;
                    rows += '<tr class="equipment-row" data-target="'+id+'">';
                    rows += '<td class="text-center">'+btn+'</td>';
                    rows += '<td>'+label+'</td>';
                    rows += '<td class="text-end">'+fmt(t.value)+'</td>';
                    rows += '<td class="text-end">'+t.rented_qty+'</td>';
                    rows += '<td class="text-end">'+t.billable_days+'</td>';
                    rows += '<td class="text-end">'+fmt(t.rent_value)+'</td>';
                    rows += '<td class="text-end">'+t.repair_qty+'</td>';
                    rows += '<td class="text-end">'+fmt(t.repair_cost)+'</td>';
                    rows += '<td class="text-end '+pc(t.profit)+'">'+fmt(t.profit)+'</td>';
                    rows += '<td class="text-end">'+fmt(t.roi)+'%</td>';
                    rows += '</tr>';
                    if (hasSub) {
                        eq.sub_equipment.forEach(function(se) {
                            if (!se.sub_equipment_code) return;
                            rows += '<tr class="sub-equipment-row '+id+'" style="display:none;">';
                            rows += '<td></td>';
                            rows += '<td>'+se.sub_equipment_code+'</td>';
                            rows += '<td class="text-end">'+fmt(se.value)+'</td>';
                            rows += '<td class="text-end">'+se.rented_qty+'</td>';
                            rows += '<td class="text-end">'+se.billable_days+'</td>';
                            rows += '<td class="text-end">'+fmt(se.rent_value)+'</td>';
                            rows += '<td class="text-end">'+se.repair_qty+'</td>';
                            rows += '<td class="text-end">'+fmt(se.repair_cost)+'</td>';
                            rows += '<td class="text-end '+pc(se.profit)+'">'+fmt(se.profit)+'</td>';
                            rows += '<td class="text-end">'+fmt(se.roi)+'%</td>';
                            rows += '</tr>';
                        });
                    }
                    tV+=t.value; tRQ+=t.rented_qty; tBD+=t.billable_days; tRV+=t.rent_value; tPQ+=t.repair_qty; tPC+=t.repair_cost; tPR+=t.profit;
                });
                $('#reportTableBody').html(rows);
                var roi = tV>0 ? ((tPR/tV)*100).toFixed(2) : '0.00';
                $('#footValue').text(fmt(tV));
                $('#footRentedQty').text(tRQ);
                $('#footBillableDays').text(tBD);
                $('#footRentValue').text(fmt(tRV));
                $('#footRepairQty').text(tPQ);
                $('#footRepairCost').text(fmt(tPC));
                $('#footProfit').html('<span class="'+pc(tPR)+'">'+fmt(tPR)+'</span>');
                $('#footRoi').text(roi+'%');
            },
            error: function () {
                swal('Error', 'Server error occurred', 'error');
                $('#reportTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    $(document).on('click', '.toggle-btn', function (e) {
        e.stopPropagation();
        var target = $(this).data('target');
        var subs = $('.sub-equipment-row.' + target);
        if (subs.first().is(':visible')) { subs.hide(); $(this).text('+'); }
        else { subs.show(); $(this).text('-'); }
    });

    $(document).on('click', '.equipment-row', function () {
        $(this).find('.toggle-btn').click();
    });

    $('#searchBtn').click(loadReport);
    $('#resetBtn').click(function () { location.reload(); });
    $('#printBtn').click(function () {
        var f=$('#fromDate').val(), t=$('#toDate').val(), c=$('#equipmentCode').val().trim();
        if (f&&t) {
            var url='print-item-income-report.php?from='+f+'&to='+t;
            if(c) url += '&code='+encodeURIComponent(c);
            window.open(url,'_blank');
        } else swal('Error','Please select a date range first','error');
    });

    var now = new Date();
    function pad(d) { var m=('0'+(d.getMonth()+1)).slice(-2), dy=('0'+d.getDate()).slice(-2); return [d.getFullYear(),m,dy].join('-'); }
    $('#fromDate').val(pad(new Date(now.getFullYear(), now.getMonth(), 1)));
    $('#toDate').val(pad(new Date(now.getFullYear(), now.getMonth()+1, 0)));
    loadReport();
});
