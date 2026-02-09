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

    // Set default dates or use URL parameters
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

    if (fromDateParam && toDateParam) {
        $("#fromDate").val(fromDateParam);
        $("#toDate").val(toDateParam);
        // Auto-load report if URL parameters are present
        loadReportData();
    } else {
        $("#fromDate").val(formatDate(firstDay));
        $("#toDate").val(formatDate(today));
    }

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

        // Validation: Date range is required
        if (!fromDate || !toDate) {
            alert("Please select both From Date and To Date");
            return;
        }

        loadReportData();
    });

    // Reset button click handler
    $("#resetBtn").on("click", function () {
        $("#reportForm")[0].reset();
        $("#billType").val("all");
        $("#reportInfoSection").hide();
        $("#reportTableBody").empty();
        $("#totalAmount").text("0.00");
        $("#totalExtraAmount").text("0.00");
        $("#totalQty").text("0");
        $("#totalBills").text("0");
        $("#totalRentBills").text("0");
        $("#totalReturnBills").text("0");

        // Reset dates to current month
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        $("#fromDate").val(formatDate(firstDay));
        $("#toDate").val(formatDate(today));
    });

    // Load report data via AJAX
    function loadReportData() {
        const fromDate = $("#fromDate").val();
        const toDate = $("#toDate").val();
        const billType = $("#billType").val();

        console.log("From Date:", fromDate);
        console.log("To Date:", toDate);
        console.log("Bill Type:", billType);

        // Validation
        if (!fromDate || !toDate) {
            alert("Please select both From Date and To Date");
            return;
        }

        const requestData = {
            action: "get_rent_return_bills_report",
            from_date: fromDate,
            to_date: toDate,
            bill_type: billType
        };

        console.log("Sending request with data:", requestData);

        $.ajax({
            url: "ajax/php/rent-return-bills-report.php",
            type: "POST",
            dataType: "json",
            data: requestData,
            beforeSend: function () {
                console.log("Sending request...");
                $("#reportTableBody").html(
                    '<tr><td colspan="16" class="text-center">Loading...</td></tr>'
                );
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
                    $("#reportTableBody").html(
                        '<tr><td colspan="8" class="text-center">No data found</td></tr>'
                    );
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                });
                alert("Error loading data. Please check console for details.");
                $("#reportTableBody").html(
                    '<tr><td colspan="8" class="text-center">Error loading data</td></tr>'
                );
            },
            complete: function () {
                console.log("Request completed");
            },
        });
    }

    // Render report data in table
    function renderReportData(data, summary) {
        const tbody = $("#reportTableBody");
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html(
                '<tr><td colspan="16" class="text-center">No bills found for the selected date range</td></tr>'
            );
            $("#totalQty").text("0");
            $("#totalAmount").text("0.00");
            return;
        }

        data.forEach(function (item, index) {
            // Determine badge class based on bill type
            let badgeClass = 'badge-rent';
            if (item.bill_type === 'Return') {
                badgeClass = 'badge-return';
            }

            const rowId = `bill-${index}`;
            const detailsId = `details-${index}`;

            const row = `
                <tr id="${rowId}" class="main-row" data-details-id="#${detailsId}" style="cursor: pointer;">
                    <td class="text-center">
                        <button class="btn btn-sm btn-soft-primary details-control">
                            <i class="bx bx-plus"></i>
                        </button>
                    </td>
                    <td><span class="bill-type-badge ${badgeClass}">${item.bill_type}</span></td>
                    <td>${item.bill_no || '-'}</td>
                    <td>${item.date || '-'}</td>
                    <td>${item.customer_name || '-'}</td>
                    <td>${item.customer_tel || '-'}</td>
                    <td>${item.customer_address || '-'}</td>
                    <td>${item.customer_nic || '-'}</td>
                    <td>${item.day_count || '-'}</td>
                    <td>${item.rent_date || '-'}</td>
                    <td>${item.return_date || '-'}</td>
                    <td class="text-end">${item.quantity || 0}</td>
                    <td class="text-end">${parseFloat(item.deposit || 0).toFixed(2)}</td>
                    <td class="text-end">${item.amount || '0.00'}</td>
                    <td class="text-end">${item.extra_amount || '0.00'}</td>
                    <td class="text-end fw-bold">${item.profit_balance}</td>
                    <td>${item.remarks || "-"}</td>
                </tr>
                <tr id="${detailsId}" class="details-row" style="display: none; background-color: #f8f9fa;">
                    <td colspan="16" class="p-3">
                        <table class="table table-sm table-bordered mb-0" style="background-color: #fff;">
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
                                ${renderItemsTable(item.items)}
                            </tbody>
                        </table>
                    </td>
                </tr>`;

            tbody.append(row);
        });

        // Update totals
        $("#totalQty").text(summary.total_quantity);
        $('#totalAmount').text(summary.total_amount);
        $('#totalExtraAmount').text(summary.total_extra_amount);
        $('#totalProfit').text(summary.total_profit);

        // Re-attach click handlers (or rely on delegation if setup outside)
        // Since we empty tbody, delegation on #reportTableBody is best.
    }

    // Event Delegation for Row Click (Setup once)
    $(document).off('click', 'tr.main-row').on('click', 'tr.main-row', function (e) {
        // Prevent event from bubbling up if a child element (like a button) was clicked
        if ($(e.target).closest('.details-control').length) {
            // If the button was clicked, let its own handler (if any) or the delegated handler proceed
            // For this setup, the row click handler will also catch the button click,
            // so we just need to ensure the correct target is passed to toggleDetails.
            const targetId = $(this).data('details-id');
            const btn = $(this).find('.details-control');
            toggleDetails(targetId, btn);
        } else if (!$(e.target).is('a, button, input, select, textarea')) { // Ignore clicks on interactive elements
            const targetId = $(this).data('details-id');
            const btn = $(this).find('.details-control');
            toggleDetails(targetId, btn);
        }
    });

    // Helper to render items table rows
    function renderItemsTable(items) {
        if (!items || items.length === 0) return '<tr><td colspan="5" class="text-center">No items</td></tr>';

        return items.map(item => `
            <tr>
                <td>${item.name}</td>
                <td class="text-end">${parseFloat(item.daily_rent || 0).toFixed(2)}</td>
                <td class="text-end">${item.day_count}</td>
                <td class="text-end">${item.quantity}</td>
                <td class="text-end">${formatNumber(item.amount || 0)}</td>
            </tr>
        `).join('');
    }

    // Toggle Details Function
    function toggleDetails(targetId, btn) {
        const row = $(targetId);
        const icon = btn.find('i');

        if (row.is(':visible')) {
            row.hide();
            icon.removeClass('bx-minus').addClass('bx-plus');
        } else {
            row.show();
            icon.removeClass('bx-plus').addClass('bx-minus');
        }
    }

    // Export to PDF functionality
    $("#exportToPdf").on("click", function () {
        const fromDate = $("#fromDate").val();
        const toDate = $("#toDate").val();
        const billType = $("#billType").val();

        if (!fromDate || !toDate) {
            alert("Please select date range before exporting");
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
                bill_type: billType
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
      <title>Rent & Return Bills Report - ${dateRange}</title>
      <style>
          @page { margin: 20px; }
          body {
              font-family: 'Arial', sans-serif;
              margin: 0;
              padding: 0;
              color: #333;
              line-height: 1.4;
          }
          .header {
              text-align: center;
              margin-bottom: 30px;
              padding-bottom: 15px;
              border-bottom: 2px solid #eee;
          }
          .header h1 {
              margin: 0 0 10px 0;
              color: #2c3e50;
              font-size: 24px;
          }
          .header p {
              margin: 5px 0;
              color: #7f8c8d;
              font-size: 14px;
          }
          .summary {
              background-color: #f8f9fa;
              padding: 15px;
              border-radius: 5px;
              margin-bottom: 20px;
              border-left: 4px solid #667eea;
          }
          .summary h3 {
              margin: 0 0 10px 0;
              color: #2c3e50;
          }
          .summary-grid {
              display: grid;
              grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
              gap: 10px;
          }
          .summary-item {
              font-size: 14px;
          }
          .summary-item strong {
              color: #495057;
          }
          table {
              width: 100%;
              border-collapse: collapse;
              margin: 15px 0;
              font-size: 11px;
              page-break-inside: auto;
          }
          th, td {
              border: 1px solid #e0e0e0;
              padding: 8px 10px;
              text-align: left;
              vertical-align: top;
          }
          thead th {
              background-color: #f8f9fa;
              color: #2c3e50;
              font-weight: 600;
              text-transform: uppercase;
              font-size: 10px;
              padding: 10px;
          }
          .text-right { text-align: right; }
          .text-center { text-align: center; }
          .badge {
              padding: 3px 6px;
              border-radius: 3px;
              font-size: 9px;
              font-weight: bold;
          }
          .badge-rent { background-color: #28a745; color: white; }
          .badge-return { background-color: #dc3545; color: white; }
          .badge-issue { background-color: #ffc107; color: black; }
          .footer {
              margin-top: 40px;
              padding-top: 20px;
              text-align: center;
              font-size: 11px;
              color: #7f8c8d;
              border-top: 1px solid #eee;
          }
      </style>
  </head>
  <body>
      <div class="header">
          <h1>Rent & Return Bills Report</h1>
          <p>Generated on ${new Date().toLocaleString()}</p>
      </div>

      <div class="summary">
          <h3>Report Summary</h3>
          <div class="summary-grid">
              <div class="summary-item"><strong>Date Range:</strong> ${dateRange}</div>
              <div class="summary-item"><strong>Total Bills:</strong> ${totalBills}</div>
              <div class="summary-item"><strong>Rent Bills:</strong> ${totalRentBills}</div>
              <div class="summary-item"><strong>Return Bills:</strong> ${totalReturnBills}</div>
               <div class="summary-item"><strong>Total Profit:</strong> Rs. ${totalAmount}</div>
               <div class="summary-item"><strong>Total Refund / Cash In:</strong> Rs. ${totalProfitExport}</div>
          </div>
      </div>

      <table>
          <thead>
                <th style="border: 1px solid #ddd; padding: 8px;">Bill Type</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Bill No</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Customer</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Tel</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Address</th>
                <th style="border: 1px solid #ddd; padding: 8px;">NIC</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Items</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Daily Rent</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Day Count</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Rent Date</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Return Date</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Qty</th>
                 <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Deposit</th>
                 <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Profit</th>
                 <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Extra Amount</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Refund / Cash In</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Remarks</th>
            </tr>
        </thead>
        <tbody>`;

        data.forEach((item) => {
            let badgeClass = 'badge-rent';
            let badgeStyle = 'background-color: #f1b44c; color: #fff; padding: 3px 6px; border-radius: 4px; font-size: 10px;';

            if (item.bill_type === 'Return') {
                badgeClass = 'badge-return';
                badgeStyle = 'background-color: #50a5f1; color: #fff; padding: 3px 6px; border-radius: 4px; font-size: 10px;';
            }

            html += `
              <tr>
                  <td style="border: 1px solid #ddd; padding: 8px;"><span style="${badgeStyle}">${item.bill_type}</span></td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.bill_no || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.date || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.customer_name || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.customer_tel || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px; font-size: 10px;">${item.customer_address || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.customer_nic || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.equipment_item || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.daily_rent || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.day_count || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.rent_date || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.return_date || '-'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${item.quantity || 0}</td>
                  <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${item.deposit || '0.00'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${item.amount || '0.00'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${item.extra_amount || '0.00'}</td>
                  <td style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>${item.profit_balance}</strong></td>
                  <td style="border: 1px solid #ddd; padding: 8px;">${item.remarks || '-'}</td>
              </tr>`;
        });

        html += `
          </tbody>
      </table>

      <div class="footer">
          <p>This report was generated by the Rent & Return Bills Management System</p>
          <p style="margin: 5px 0 0 0; font-size: 10px; color: #bdc3c7;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></p>
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
