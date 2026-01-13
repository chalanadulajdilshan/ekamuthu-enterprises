/**
 * Return Items Report JS
 * Handles date filtering and report data loading
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
    $("#returnInfoSection").hide();
    $("#reportTableBody").empty();
    $("[id^=total]").text("0.00");
    $("#totalReturnQty").text("0");
    $("#totalReturns").text("0");
    $("#totalItems").text("0");
    $("#totalAmount").text("0.00");

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

    console.log("From Date:", fromDate);
    console.log("To Date:", toDate);

    // Validation
    if (!fromDate || !toDate) {
      alert("Please select both From Date and To Date");
      return;
    }

    const requestData = {
      action: "get_return_items_report",
      from_date: fromDate,
      to_date: toDate,
    };

    console.log("Sending request with data:", requestData);

    $.ajax({
      url: "ajax/php/return-items-report.php",
      type: "POST",
      dataType: "json",
      data: requestData,
      beforeSend: function () {
        console.log("Sending request...");
        $("#reportTableBody").html(
          '<tr><td colspan="10" class="text-center">Loading...</td></tr>'
        );
      },
      success: function (response) {
        console.log("Server response:", response);
        if (response && response.status === "success") {
          renderReportData(response.data, response.summary);

          // Show return info section and update date range display
          $("#returnInfoSection").show();
          $("#dateRangeDisplay").text(response.summary.date_range);
          $("#totalReturns").text(response.summary.total_returns);
          $("#totalItems").text(response.summary.total_items);
          $("#totalAmount").text(response.summary.total_amount);
        } else {
          const errorMsg =
            response && response.message
              ? response.message
              : "Error loading data";
          console.error("Error response:", errorMsg);
          alert(errorMsg);
          $("#reportTableBody").html(
            '<tr><td colspan="10" class="text-center">No data found</td></tr>'
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
          '<tr><td colspan="10" class="text-center">Error loading data</td></tr>'
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
        '<tr><td colspan="10" class="text-center">No return items found for the selected date range</td></tr>'
      );
      $("#totalReturnQty").text("0");
      $("#totalReturnAmount").text("0.00");
      return;
    }

    data.forEach(function (item) {
      const row = `
                <tr>
                    <td>${item.return_no || ""}</td>
                    <td>${item.return_date || ""}</td>
                    <td>${item.invoice_no || ""}</td>
                    <td>${item.customer_name || ""}</td>
                    <td>${item.item_code || ""}</td>
                    <td>${item.item_name || ""}</td>
                    <td class="text-end">${item.return_quantity || 0}</td>
                    <td class="text-end">${item.unit_price || "0.00"}</td>
                    <td class="text-end">${item.total_amount || "0.00"}</td>
                    <td>${item.return_reason || "-"}</td>
                </tr>`;

      tbody.append(row);
    });

    // Update totals
    $("#totalReturnQty").text(summary.total_quantity);
    $("#totalReturnAmount").text(summary.total_amount);
  }

  // Export to PDF functionality
  $("#exportToPdf").on("click", function () {
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

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

    // Make AJAX request for return items report data
    $.ajax({
      url: "ajax/php/return-items-report.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "get_return_items_report",
        from_date: fromDate,
        to_date: toDate,
      },
      success: function (response) {
        if (response && response.status === "success") {
          const data = response.data;
          const summary = response.summary;

          if (data && data.length > 0) {
            exportReturnItemsToPdf(data, summary);
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

  // Function to export return items report data to PDF
  function exportReturnItemsToPdf(data, summary) {
    const dateRange = summary.date_range;
    const totalReturns = summary.total_returns;
    const totalItems = summary.total_items;
    const totalAmount = summary.total_amount;

    let html = `
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="utf-8">
      <title>Return Items Report - ${dateRange}</title>
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
              font-size: 12px;
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
              font-size: 11px;
              padding: 10px;
          }
          .text-right { text-align: right; }
          .text-center { text-align: center; }
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
          <h1>Return Items Report</h1>
          <p>Generated on ${new Date().toLocaleString()}</p>
      </div>

      <div class="summary">
          <h3>Report Summary</h3>
          <div class="summary-grid">
              <div class="summary-item"><strong>Date Range:</strong> ${dateRange}</div>
              <div class="summary-item"><strong>Total Returns:</strong> ${totalReturns}</div>
              <div class="summary-item"><strong>Total Items:</strong> ${totalItems}</div>
              <div class="summary-item"><strong>Total Amount:</strong> ${totalAmount}</div>
          </div>
      </div>

      <table>
          <thead>
              <tr>
                  <th>Return No</th>
                  <th>Return Date</th>
                  <th>Invoice No</th>
                  <th>Customer</th>
                  <th>Item Code</th>
                  <th>Item Name</th>
                  <th class="text-right">Return Qty</th>
                  <th class="text-right">Unit Price</th>
                  <th class="text-right">Total Amount</th>
                  <th>Return Reason</th>
              </tr>
          </thead>
          <tbody>`;

    data.forEach((item) => {
      html += `
              <tr>
                  <td>${item.return_no || "-"}</td>
                  <td>${item.return_date || "-"}</td>
                  <td>${item.invoice_no || "-"}</td>
                  <td>${item.customer_name || "-"}</td>
                  <td>${item.item_code || "-"}</td>
                  <td>${item.item_name || "-"}</td>
                  <td class="text-right">${item.return_quantity || 0}</td>
                  <td class="text-right">${item.unit_price || "0.00"}</td>
                  <td class="text-right">${item.total_amount || "0.00"}</td>
                  <td>${item.return_reason || "-"}</td>
              </tr>`;
    });

    html += `
          </tbody>
      </table>

      <div class="footer">
          <p>This report was generated by the Return Items Management System</p>
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
