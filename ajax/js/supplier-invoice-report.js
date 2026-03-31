/**
 * Supplier Invoice Analysis Report JS
 */

$(document).ready(function () {
    var supplierChart, paymentChart, trendChart;

    // Initialize datepickers
    $(".date-picker").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });

    // Set default date range (current month)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#fromDate').datepicker('setDate', firstDay);
    $('#toDate').datepicker('setDate', today);

    // Initial search
    loadReportData();

    // Search button click
    $('#searchBtn').on('click', function () {
        loadReportData();
    });

    // Reset button
    $('#resetBtn').on('click', function () {
        $('#reportForm')[0].reset();
        $('#customer_id').val('');
        $('#code').val('');
        $('#fromDate').datepicker('setDate', firstDay);
        $('#toDate').datepicker('setDate', today);
        loadReportData();
    });

    function loadReportData() {
        const requestData = {
            action: 'fetch_supplier_invoice_report',
            supplier_id: $('#customer_id').val() || '',
            from_date: $('#fromDate').val() || '',
            to_date: $('#toDate').val() || ''
        };

        $.ajax({
            url: 'ajax/php/supplier-invoice-report.php',
            type: 'POST',
            dataType: 'json',
            data: requestData,
            beforeSend: function () {
                $('#reportTableBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
            },
            success: function (response) {
                if (response && response.status === 'success') {
                    renderSummary(response.summary);
                    renderTable(response.data);
                    renderCharts(response.summary);
                } else {
                    alert(response.message || 'Error loading data');
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                alert('An error occurred. Check console.');
            }
        });
    }

    function renderSummary(summary) {
        if (!summary) return;

        const total = parseFloat(summary.total_purchases || 0);
        const paid = parseFloat(summary.total_paid || 0);
        const outstanding = parseFloat(summary.total_outstanding || 0);
        const count = summary.count || 0;
        const avg = count > 0 ? (total / count) : 0;
        const percentage = total > 0 ? (outstanding / total * 100) : 0;

        $('#summaryTotalPurchases').text('Rs. ' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#summaryTotalPaid').text('Rs. ' + paid.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#summaryTotalOutstanding').text('Rs. ' + outstanding.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#summaryAvgInvoice').text('Rs. ' + avg.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        
        // Update both display and print labels
        $('#invoiceCountDisp').text(count);
        $('#invoiceCountPrint').text(count);
        
        $('#outstandingPercentage').text(percentage.toFixed(1) + '%');

        // Update Print Filter Info
        const supplier = $('#code').val() || 'All Suppliers';
        const fromDate = $('#fromDate').val() || '-';
        const toDate = $('#toDate').val() || '-';
        $('#printFilterInfo').html(`Supplier: <strong>${supplier}</strong> | Range: <strong>${fromDate} to ${toDate}</strong>`);
    }

    function renderTable(data) {
        const tbody = $('#reportTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center py-4">No records found matching filters.</td></tr>');
            return;
        }

        data.forEach(function (row) {
            const tr = `
                <tr>
                    <td>
                        <span class="fw-bold">${row.grn_number}</span><br>
                        <small class="text-muted">${row.invoice_no || '-'}</small>
                    </td>
                    <td>${row.invoice_date}</td>
                    <td>
                        <span class="fw-medium">${row.supplier_name}</span><br>
                        <small class="text-muted">${row.supplier_code}</small>
                    </td>
                    <td><span class="badge ${getBadgeClass(row.payment_type)} font-size-11">${row.payment_type.toUpperCase()}</span></td>
                    <td class="text-end fw-bold">${formatMoney(row.grand_total)}</td>
                    <td class="text-end text-success">${formatMoney(row.paid_amount)}</td>
                    <td class="text-end text-danger">${formatMoney(row.outstanding)}</td>
                    <td class="text-center no-print">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="mdi mdi-dots-horizontal"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item view-details" href="javascript:void(0)" data-id="${row.id}"><i class="mdi mdi-eye me-1"></i> View Items</a></li>
                                <li><a class="dropdown-item" href="print-supplier-invoice.php?id=${row.id}" target="_blank"><i class="mdi mdi-printer me-1"></i> Print Invoice</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(tr);
        });

        updateFooter(data);
    }

    function updateFooter(data) {
        let total = 0, paid = 0, outstanding = 0;
        data.forEach(function (row) {
            total += parseFloat(row.grand_total || 0);
            paid += parseFloat(row.paid_amount || 0);
            outstanding += parseFloat(row.outstanding || 0);
        });
        $('#footerTotal').text(formatMoney(total));
        $('#footerPaid').text(formatMoney(paid));
        $('#footerOutstanding').text(formatMoney(outstanding));
    }

    function renderCharts(summary) {
        // Trend Chart
        const monthlyTrends = summary.monthly_trends || [];
        const monthNames = monthlyTrends.map(m => m.month);
        const monthTotals = monthlyTrends.map(m => m.total);

        const trendOptions = {
            chart: { height: 200, type: 'area', toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
            series: [{ name: 'Purchases (Rs)', data: monthTotals }],
            xaxis: { categories: monthNames },
            colors: ['#3b5de7'],
            title: { text: 'Monthly Purchase Trend', align: 'left', style: { fontWeight: '500', fontSize: '13px' } }
        };
        if (trendChart) trendChart.destroy();
        trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
        trendChart.render();

        // Top Suppliers Chart
        const topSuppliers = summary.top_suppliers || [];
        const supplierNames = topSuppliers.map(s => s.name);
        const supplierTotals = topSuppliers.map(s => s.total);

        const supplierOptions = {
            chart: { height: 200, type: 'bar', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '50%' } },
            colors: ['#44b57d'],
            series: [{ name: 'Purchases (Rs)', data: supplierTotals }],
            xaxis: { categories: supplierNames },
            title: { text: 'Top Suppliers Contribution', align: 'left', style: { fontWeight: '500', fontSize: '13px' } }
        };

        if (supplierChart) supplierChart.destroy();
        supplierChart = new ApexCharts(document.querySelector("#supplierChart"), supplierOptions);
        supplierChart.render();

        // Top Items Table
        const topItems = summary.top_items || [];
        const topItemsTbody = $('#topItemsTableBody');
        topItemsTbody.empty();
        if (topItems.length === 0) {
            topItemsTbody.append('<tr><td colspan="3" class="text-center text-muted">No items available in this range.</td></tr>');
        } else {
            topItems.forEach(item => {
                topItemsTbody.append(`
                    <tr>
                        <td class="fw-medium">${item.item_name}</td>
                        <td class="text-end text-muted">${parseFloat(item.total_qty).toFixed(2)}</td>
                        <td class="text-end fw-bold">Rs. ${formatMoney(item.total_spent)}</td>
                    </tr>
                `);
            });
        }

        // Payment Type Chart
        const paymentOptions = {
            chart: { height: 200, type: 'donut' },
            labels: ['Cash', 'Credit', 'Cheque'],
            series: [parseFloat(summary.cash_purchases) || 0, parseFloat(summary.credit_purchases) || 0, parseFloat(summary.cheque_purchases) || 0],
            colors: ['#44b57d', '#f46a6a', '#ffbe0b'],
            legend: { position: 'right', fontSize: '11px' },
            title: { text: 'Payment Composition', align: 'center', style: { fontWeight: '500', fontSize: '13px' } },
            dataLabels: { enabled: true, style: { fontSize: '10px' }, formatter: (val) => val.toFixed(0) + "%" }
        };

        if (paymentChart) paymentChart.destroy();
        paymentChart = new ApexCharts(document.querySelector("#paymentChart"), paymentOptions);
        paymentChart.render();
    }

    $(document).on('click', '.view-details', function () {
        const id = $(this).data('id');
        $('#printSingleInvoice').attr('href', 'print-supplier-invoice.php?id=' + id);
        
        $.ajax({
            url: 'ajax/php/supplier-invoice-report.php',
            type: 'POST',
            data: { action: 'get_invoice_items', id: id },
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    const tbody = $('#invoiceItemsTableBody');
                    tbody.empty();
                    res.data.forEach(function (item) {
                        tbody.append(`
                            <tr>
                                <td>${item.item_code}</td>
                                <td>${item.item_name}</td>
                                <td class="text-center">${item.quantity} ${item.unit}</td>
                                <td class="text-end">${parseFloat(item.rate).toFixed(2)}</td>
                                <td class="text-end fw-bold">${parseFloat(item.amount).toFixed(2)}</td>
                            </tr>
                        `);
                    });
                    $('#invoiceDetailsModal').modal('show');
                }
            }
        });
    });

    // Handle row selection for AllSupplierModal
    $('#allSupplierTable tbody').off('click').on('click', 'tr', function () {
        var table = $('#allSupplierTable').DataTable();
        var data = table.row(this).data();
        if (data) {
            $('#customer_id').val(data.id || '');
            $('#code').val(data.code || '');
            $('#AllSupplierModal').modal('hide');
        }
    });

    $('#printReport').on('click', function () {
        window.print();
    });

    function formatMoney(num) {
        return parseFloat(num).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getBadgeClass(type) {
        switch (type.toLowerCase()) {
            case 'cash': return 'bg-success';
            case 'credit': return 'bg-danger';
            case 'cheque': return 'bg-warning';
            default: return 'bg-secondary';
        }
    }
});
