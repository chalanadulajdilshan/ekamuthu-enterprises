/**
 * Rent & Return Bills Report JS
 * Handles date filtering, bill type selection, and report data loading
 */

// Number formatting function with thousand separators and 2 decimal places
function formatNumber(num) {
    return parseFloat(num || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

$(document).ready(function () {
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const fromDateParam = urlParams.get('from_date');
    const toDateParam = urlParams.get('to_date');

    // Initialize the datepicker with proper configuration
    $(".date-picker").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: '1900:2099',
        showButtonPanel: true,
        showOn: 'focus',
        showAnim: 'fadeIn',
        buttonImageOnly: false
    });

    const today = new Date();
    if (fromDateParam && toDateParam) {
        $("#fromDate").datepicker('setDate', fromDateParam);
        $("#toDate").datepicker('setDate', toDateParam);
    } else {
        $("#fromDate").datepicker('setDate', today);
        $("#toDate").datepicker('setDate', today);
    }

    // Initialize DataTable
    let reportTable = $('#reportTable').DataTable({
        columns: [
            {
                className: 'details-control text-center',
                orderable: false,
                data: null,
                defaultContent: '<button class="btn btn-sm btn-soft-primary"><i class="bx bx-plus"></i></button>',
                width: '30px'
            },
            {
                data: 'bill_type', render: function (data) {
                    let badgeClass = data === 'Return' ? 'badge-return' : 'badge-rent';
                    return `<span class="bill-type-badge ${badgeClass}">${data}</span>`;
                }
            },
            { data: 'bill_no' },
            { data: 'date' },
            { data: 'customer_name' },
            { data: 'customer_tel' },
            { data: 'customer_address' },
            { data: 'customer_nic' },
            {
                data: 'day_count', render: function (data, type, row) {
                    return `${data || '-'}${row.after_9am == 1 ? ' <span class="badge bg-danger">+1 DAY</span>' : ''}`;
                }
            },
            { data: 'rent_date' },
            { data: 'return_date' },
            { data: 'quantity', className: 'text-end' },
            {
                data: 'deposit', className: 'text-end', render: function (data) {
                    return formatNumber(data);
                }
            },
            {
                data: 'amount', className: 'text-end', render: function (data) {
                    return formatNumber(data);
                }
            },
            {
                data: 'extra_amount', className: 'text-end', render: function (data) {
                    return formatNumber(data);
                }
            },
            { data: 'profit_balance', className: 'text-end fw-bold' },
            { data: 'remarks' }
        ],
        order: [[3, 'desc']], // Sort by date by default
        pageLength: 50,
        responsive: false,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function () {
            // Adjust columns on draw
            $(this).DataTable().columns.adjust();
        }
    });

    // Add event listener for opening and closing details
    $('#reportTable tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = reportTable.row(tr);
        var icon = $(this).find('i');

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('bx-minus').addClass('bx-plus');
        } else {
            row.child(formatChildRow(row.data())).show();
            tr.addClass('shown');
            icon.removeClass('bx-plus').addClass('bx-minus');
        }
    });

    // Helper to format child row
    function formatChildRow(d) {
        return `
            <div class="p-3 bg-light rounded shadow-sm m-2">
                <table class="table table-sm table-bordered mb-0 bg-white">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th class="text-end">Daily Rent</th>
                            <th class="text-end">Day Count</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${renderItemsTable(d.items)}
                    </tbody>
                </table>
            </div>`;
    }

    // Auto-load report data immediately on page open
    loadReportData();

    // Set to today's date when clicking the Today button
    $('#setToday').click(function (e) {
        e.preventDefault();
        const today = new Date();
        $('#toDate').datepicker('setDate', today);
        $('#fromDate').datepicker('setDate', today);
        loadReportData(); // Auto-search when clicking Today
    });

    // Format date to YYYY-MM-DD
    function formatDate(date) {
        const d = new Date(date);
        let month = "" + (d.getMonth() + 1);
        let day = "" + d.getDate();
        const year = d.getFullYear();

        if (month.length < 2) month = "0" + month;
        if (day.length < 2) day = "0" + day;

        return [year, month, day].join("-");
    }

    // Search button click handler
    $("#searchBtn").on("click", function () {
        const fromDate = $("#fromDate").val();
        const toDate = $("#toDate").val();
        const billNo = $("#billNo").val().trim();

        // Validation: require either date range or bill number
        if ((!fromDate || !toDate) && billNo === "") {
            alert("Please select both From Date and To Date or enter a Bill No");
            return;
        }

        loadReportData();
    });

    // Reset button click handler
    $("#resetBtn").on("click", function () {
        $("#reportForm")[0].reset();
        $("#billType").val("all");
        $("#reportInfoSection").hide();
        reportTable.clear().draw();
        $("#totalAmount").text("0.00");
        $("#totalExtraAmount").text("0.00");
        $("#totalProfit").text("0.00");
        $("#totalDeposit").text("0.00");
        $("#totalQty").text("0");
        $("#totalBills").text("0");
        $("#totalRentBills").text("0");
        $("#totalReturnBills").text("0");
        $("#billNo").val("");

        // Reset dates to current date
        const today = new Date();
        $("#fromDate").datepicker('setDate', today);
        $("#toDate").datepicker('setDate', today);
    });

    // Load report data via AJAX
    function loadReportData() {
        const fromDate = $("#fromDate").val();
        const toDate = $("#toDate").val();
        const billType = $("#billType").val();
        const billNo = $("#billNo").val().trim();

        console.log("Loading Report Data for:", { fromDate, toDate, billType, billNo });

        // Validation: ensure either date range or bill number
        if ((!fromDate || !toDate) && billNo === "") {
            alert("Please select both From Date and To Date or enter a Bill No");
            return;
        }

        const requestData = {
            action: "get_rent_return_bills_report",
            from_date: fromDate,
            to_date: toDate,
            bill_type: billType,
            bill_no: billNo
        };

        $.ajax({
            url: "ajax/php/rent-return-bills-report.php",
            type: "POST",
            dataType: "json",
            data: requestData,
            beforeSend: function () {
                // We don't manually clear the table here because DataTables handles state
                // but let's clear it just in case
                reportTable.clear().draw();
                // Custom loading overlay if needed or use the built-in processing
            },
            success: function (response) {
                console.log("Server response:", response);
                if (response && response.status === "success") {
                    renderReportData(response.data, response.summary);

                    // Show report info section and update summary display
                    $("#reportInfoSection").show();
                    $("#dateRangeDisplay").text(response.summary.date_range);
                    $("#totalBills").text(response.summary.total_bills);
                    $("#totalRentBills").text(response.summary.total_rent_bills);
                    $("#totalReturnBills").text(response.summary.total_return_bills);
                } else {
                    const errorMsg =
                        response && response.message
                            ? response.message
                            : "Error loading data";
                    console.error("Error response:", errorMsg);
                    alert(errorMsg);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                });
                alert("Error loading data. Please check console for details.");
            }
        });
    }

    // Render report data in table
    function renderReportData(data, summary) {
        reportTable.clear();

        if (data && data.length > 0) {
            reportTable.rows.add(data).draw();
            // Need to adjust columns after data is loaded and rendered
            setTimeout(function () {
                reportTable.columns.adjust().draw(false);
            }, 100);
        }

        // Update totals
        $("#totalQty").text(summary.total_quantity);
        $('#totalAmount').text(summary.total_amount);
        $('#totalExtraAmount').text(summary.total_extra_amount);
        $('#totalProfit').text(summary.total_profit);
        $('#totalDeposit').text(summary.total_deposit);
    }


    // Helper to render items table rows
    function renderItemsTable(items) {
        if (!items || items.length === 0) return '<tr><td colspan="5" class="text-center">No items</td></tr>';

        return items.map(item => `
            <tr>
                <td>${item.name}</td>
                <td class="text-end">${parseFloat(item.daily_rent || 0).toFixed(2)}</td>
                <td class="text-end">${item.day_count}${item.after_9am == 1 ? ' <span class="badge bg-danger">+1 DAY</span>' : ''}</td>
                <td class="text-end">${item.quantity}</td>
                <td class="text-end">${formatNumber(item.amount || 0)}</td>
            </tr>
        `).join('');
    }


    // Export to PDF functionality
    $("#exportToPdf").on("click", function () {
        const fromDate = $("#fromDate").val();
        const toDate = $("#toDate").val();
        const billType = $("#billType").val();
        const billNo = $("#billNo").val().trim();

        if ((!fromDate || !toDate) && billNo === "") {
            alert("Please select a date range or enter a Bill No before exporting");
            return;
        }

        // Show loading state
        const originalText = $(this).html();
        $(this).html(
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exporting...'
        );
        $(this).prop("disabled", true);

        // Make AJAX request for report data
        $.ajax({
            url: "ajax/php/rent-return-bills-report.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_rent_return_bills_report",
                from_date: fromDate,
                to_date: toDate,
                bill_type: billType,
                bill_no: billNo
            },
            success: function (response) {
                if (response && response.status === "success") {
                    const data = response.data;
                    const summary = response.summary;

                    if (data && data.length > 0) {
                        exportToPdf(data, summary);
                    } else {
                        alert("No data available for export");
                    }
                } else {
                    alert(
                        "Failed to retrieve export data: " +
                        (response.message || "Unknown error")
                    );
                }
            },
            error: function (xhr, status, error) {
                alert("Export failed: " + error);
            },
            complete: function () {
                // Restore button state
                $("#exportToPdf").html(originalText);
                $("#exportToPdf").prop("disabled", false);
            },
        });
    });

    // Function to export report data to PDF
    function exportToPdf(data, summary) {
        const dateRange = summary.date_range;
        const totalBills = summary.total_bills;
        const totalRentBills = summary.total_rent_bills;
        const totalReturnBills = summary.total_return_bills;
        const totalAmount = summary.total_amount;
        const totalProfitExport = summary.total_profit;

        let html = `
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="utf-8">
      <title>කුලියට දීම සහ ආපසු ලබා ගැනීම් වාර්තාව - ${dateRange}</title>
      <style>
          @page { size: A4 landscape; margin: 10mm; }
          body {
              font-family: 'Iskoola Pota', 'Noto Sans Sinhala', 'Arial', sans-serif;
              margin: 0;
              padding: 0;
              color: #000;
              line-height: 1.2;
              font-size: 9px;
          }
          .header {
              text-align: center;
              margin-bottom: 15px;
              padding-bottom: 10px;
              border-bottom: 1px solid #000;
          }
          .header h1 {
              margin: 0 0 5px 0;
              font-size: 18px;
              font-weight: bold;
          }
          .header p {
              margin: 2px 0;
              font-size: 10px;
          }
          .summary {
              background-color: #f8f9fa;
              padding: 10px;
              border-radius: 4px;
              margin-bottom: 15px;
              border: 1px solid #ddd;
          }
          .summary h3 {
              margin: 0 0 8px 0;
              font-size: 12px;
              border-bottom: 1px solid #eee;
              padding-bottom: 4px;
          }
          .summary-grid {
              display: flex;
              flex-wrap: wrap;
              gap: 15px;
          }
          .summary-item {
              font-size: 10px;
          }
          table {
              width: 100%;
              border-collapse: collapse;
              margin: 10px 0;
              table-layout: fixed;
              border: 1px solid #000;
          }
          th, td {
              border: 1px solid #000;
              padding: 4px 6px;
              word-wrap: break-word;
              overflow: hidden;
              vertical-align: middle;
          }
          thead th {
              background-color: #f0f0f0;
              font-weight: bold;
              text-transform: uppercase;
              font-size: 9px;
              text-align: center;
          }
          .text-right { text-align: right; }
          .text-center { text-align: center; }
          .badge {
              padding: 2px 4px;
              border-radius: 2px;
              font-size: 8px;
              font-weight: bold;
              display: inline-block;
          }
          .footer {
              margin-top: 20px;
              font-size: 8px;
              text-align: center;
              color: #666;
          }
      </style>
  </head>
  <body>
      <div class="header">
          <h1>කුලියට දීම සහ ආපසු ලබා ගැනීම් වාර්තාව</h1>
          <p>සකස් කළ දිනය: ${new Date().toLocaleString()}</p>
      </div>

      <div class="summary">
          <h3>වාර්තා සාරාංශය</h3>
          <div class="summary-grid">
              <div class="summary-item"><strong>කාල පරාසය:</strong> ${dateRange}</div>
              <div class="summary-item"><strong>මුළු බිල්පත්:</strong> ${totalBills}</div>
              <div class="summary-item"><strong>කුලියට දීම:</strong> ${totalRentBills}</div>
              <div class="summary-item"><strong>ආපසු ලබා ගැනීම්:</strong> ${totalReturnBills}</div>
              <div class="summary-item"><strong>මුළු ලාභය:</strong> Rs. ${formatNumber(totalAmount)}</div>
              <div class="summary-item"><strong>මුළු ලැබීම්/ගෙවීම්:</strong> Rs. ${formatNumber(totalProfitExport)}</div>
          </div>
      </div>

      <table>
          <thead>
              <tr>
                <th style="width: 7%;">වර්ගය</th>
                <th style="width: 6%;">අංකය</th>
                <th style="width: 8%;">දිනය</th>
                <th style="width: 12%;">පාරිභෝගිකයා</th>
                <th style="width: 8%;">දුරකථන</th>
                <th style="width: 15%;">ලිපිනය</th>
                <th style="width: 10%;">හැඳුනුම්පත</th>
                <th style="width: 4%;">දින</th>
                <th style="width: 10%;">සටහන</th>
                <th style="width: 4%; text-align: right;">ප්‍රමාණය</th>
                <th style="width: 5%; text-align: right;">තැන්පතුව</th>
                <th style="width: 5%; text-align: right;">ලාභය</th>
                <th style="width: 6%; text-align: right;">අතිරේක</th>
              </tr>
          </thead>
          <tbody>`;

        data.forEach((item) => {
            const billTypeSinhala = item.bill_type === 'Rent' ? 'කුලියට' : 'ආපසු';
            const badgeBg = item.bill_type === 'Rent' ? '#f1b44c' : '#50a5f1';

            html += `
              <tr>
                  <td class="text-center"><span class="badge" style="background-color: ${badgeBg}; color: white;">${billTypeSinhala}</span></td>
                  <td class="text-center">${item.bill_no || '-'}</td>
                  <td class="text-center">${item.date || '-'}</td>
                  <td>${item.customer_name || '-'}</td>
                  <td class="text-center">${item.customer_tel || '-'}</td>
                  <td style="font-size: 8px;">${item.customer_address || '-'}</td>
                  <td class="text-center">${item.customer_nic || '-'}</td>
                  <td class="text-center">${item.day_count || '-'}${item.after_9am == 1 ? '<br><span style="color:red; font-size:7px;">+1 DAY</span>' : ''}</td>
                  <td style="font-size: 8px;">${item.remarks || '-'}</td>
                  <td class="text-right">${item.quantity || 0}</td>
                  <td class="text-right">${formatNumber(item.deposit)}</td>
                  <td class="text-right">${formatNumber(item.amount)}</td>
                  <td class="text-right">${formatNumber(item.extra_amount)}</td>
              </tr>`;
        });

        html += `
          </tbody>
          <tfoot>
              <tr style="background-color: #f8f9fa; font-weight: bold;">
                  <td colspan="9" class="text-right">එකතුව</td>
                  <td class="text-right">${summary.total_quantity}</td>
                  <td class="text-right">-</td>
                  <td class="text-right">${formatNumber(summary.total_amount)}</td>
                  <td class="text-right">${formatNumber(summary.total_extra_amount)}</td>
              </tr>
          </tfoot>
      </table>

      <div class="footer">
          <p>මෙම වාර්තාව ජනනය කරන ලද්දේ එකමුතු එන්ටර්ප්‍රයිසස් බිල්පත් කළමනාකරණ පද්ධතිය මගිනි</p>
          <p>පිටුව <span class="pageNumber"></span> න් <span class="totalPages"></span></p>
      </div>
  </body>
  </html>`;

        // Create a new window with the HTML content
        const printWindow = window.open("", "_blank");
        printWindow.document.write(html);
        printWindow.document.close();

        // Wait for content to load, then print (which will trigger PDF download in most browsers)
        printWindow.onload = function () {
            try {
                printWindow.print();
                printWindow.onafterprint = function () {
                    printWindow.close();
                };
                console.log("PDF export completed successfully.");
            } catch (error) {
                console.error("Error during PDF print:", error);
                alert("PDF export failed. Please check the console for details.");
                printWindow.close();
            }
        };
    }
});
