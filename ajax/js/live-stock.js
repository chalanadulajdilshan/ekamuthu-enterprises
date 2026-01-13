jQuery(document).ready(function () {
  // Define department colors - add more colors if you have more departments
  const departmentColors = {
    1: "#d1e7ff", // Light Blue
    2: "#fff3cd", // Light Yellow
    3: "#f8d7da", // Light Red
    4: "#d1e7dd", // Light Green
    5: "#e2d9f3", // Light Purple
    6: "#fff8e1", // Light Orange
    7: "#e0f7fa", // Light Cyan
    8: "#f3e5f5", // Light Pink
  };

  // Function to get department color
  function getDepartmentColor(departmentId) {
    // If we have a color defined for this department ID, return it
    if (departmentColors[departmentId]) {
      return departmentColors[departmentId];
    }
    // For departments without a defined color, generate a consistent color based on the ID
    const colors = Object.values(departmentColors);
    return colors[departmentId % colors.length] || "#f8f9fa"; // Default light gray
  }

  function formatQty(value) {
    const num = Number(value);
    if (!Number.isFinite(num)) {
      return "0";
    }
    const normalized = Math.abs(num) < 1e-9 ? 0 : num;
    if (Number.isInteger(normalized)) {
      return normalized.toLocaleString("en-US", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      });
    }
    return normalized.toLocaleString("en-US", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    });
  }

  // Initialize DataTable with server-side processing
  var table = $("#stockTable").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "ajax/php/item-master.php",
      type: "POST",
      data: function (d) {
        d.filter = true;
        d.status = 1; // Only show active items
        d.stock_only = true; // Only show items with stock tracking enabled
        const depVal = $("#filter_department_id").val();
        d.department_id = depVal; // Get selected department ("all" becomes 0 on server)
        d.expand_departments = depVal === "all"; // Expand into per-department rows when All is selected
      },
      beforeSend: function () {
        try {
          var $target = $(".someBlock");
          if ($target.length && typeof $target.preloader === "function") {
            $target.preloader();
          } else {
            var $wrap = $("#stockTable").closest(".dataTables_wrapper");
            if ($wrap.length && typeof $wrap.preloader === "function") {
              $wrap.preloader();
            }
          }
        } catch (e) {
          /* noop */
        }
      },
      dataSrc: function (json) {
        return json.data;
      },
      error: function (xhr) {
        console.error("Server Error Response:", xhr.responseText);
      },
      complete: function () {
        try {
          var $target = $(".someBlock");
          if ($target.length && typeof $target.preloader === "function") {
            $target.preloader("remove");
          }
          var $wrap = $("#stockTable").closest(".dataTables_wrapper");
          if ($wrap.length && typeof $wrap.preloader === "function") {
            $wrap.preloader("remove");
          }
        } catch (e) {
          /* noop */
        }
      },
    },
    columns: [
      {
        data: null,
        title: "",
        className: "details-control",
        orderable: false,
        defaultContent:
          '<span class="mdi mdi-plus-circle-outline" style="font-size:18px; cursor:pointer;"></span>',
        width: "30px",
      },
      { data: "code", title: "Item Code" },
      { data: "name", title: "Item Description" },
      {
        data: null,
        title: "Department",
        render: function (data, type, row) {
          const departmentId = $("#filter_department_id").val();
          // If expanded (All selected), show the department for this row
          if (
            departmentId === "all" ||
            departmentId === "" ||
            departmentId === null
          ) {
            const depId =
              row.row_department_id ||
              (row.department_stock && row.department_stock[0]
                ? row.department_stock[0].department_id
                : null);
            if (depId !== null && depId !== undefined) {
              const name = $(
                '#filter_department_id option[value="' + depId + '"]'
              ).text();
              return name || "Dept " + depId;
            }
            return "All Departments";
          }
          // Otherwise show the selected department name
          return $("#filter_department_id option:selected").text();
        },
      },
      { data: "category", title: "Category" },

      {
        data: "list_price",
        title: "List Price",
        render: function (data, type, row) {
          return parseFloat(data || 0).toLocaleString("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });
        },
      },
      {
        data: "invoice_price",
        title: "Selling Price",
        render: function (data, type, row) {
          return parseFloat(data || 0).toLocaleString("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });
        },
      },
      {
        data: "department_stock",
        title: "Quantity",
        render: function (data, type, row) {
          const departmentId = $("#filter_department_id").val();
          // If expanded (All selected), use row's department qty
          if (
            departmentId === "all" ||
            departmentId === "" ||
            departmentId === null
          ) {
            const q =
              row.row_department_qty != null
                ? row.row_department_qty
                : row.available_qty || 0;
            const num = parseFloat(q || 0) || 0;
            if (type === "sort" || type === "type") return num;
            return formatQty(num);
          }
          // Otherwise, show quantity for the selected department
          if (data && data.length > 0) {
            const stock = data.find((s) => s.department_id == departmentId);
            const num = stock ? parseFloat(stock.quantity || 0) || 0 : 0;
            if (type === "sort" || type === "type") return num;
            return formatQty(num);
          }
          if (type === "sort" || type === "type") return 0;
          return "0";
        },
      },
      {
        data: "status",
        title: "Stock Status",
        render: function (data, type, row) {
          const departmentId = $("#filter_department_id").val();
          let quantity = 0;

          if (
            departmentId === "all" ||
            departmentId === "" ||
            departmentId === null
          ) {
            // Use per-department qty for the expanded row
            quantity = parseFloat(
              row.row_department_qty != null
                ? row.row_department_qty
                : row.available_qty || 0
            );
          } else if (row.department_stock && row.department_stock.length > 0) {
            const stock = row.department_stock.find(
              (s) => s.department_id == departmentId
            );
            quantity = stock ? parseFloat(stock.quantity) : 0;
          }

          const reorderLevel = parseFloat(row.re_order_level) || 0;
          const isLowStock = quantity <= reorderLevel && quantity > 0;
          const isOutOfStock = quantity <= 0;

          let statusText = "";
          let statusClass = "";

          if (isOutOfStock) {
            statusText = "Out of Stock";
            statusClass = "danger";
          } else if (isLowStock) {
            statusText = "Re-order";
            statusClass = "warning";
          } else {
            statusText = "In Stock";
            statusClass = "success";
          }

          return `<span class="badge bg-soft-${statusClass} font-size-12">${statusText}</span>`;
        },
        orderable: false,
      },
    ],
    order: [[2, "asc"]], // Default sort by item name (shifted due to details column)
    lengthMenu: [10, 25, 50, 100],
    pageLength: 25,
    responsive: true,
    language: {
      paginate: {
        previous: "<i class='mdi mdi-chevron-left'>",
        next: "<i class='mdi mdi-chevron-right'>",
      },
    },
    createdRow: function (row, data, dataIndex) {
      // Only apply colors when viewing 'All Departments'
      const departmentFilter = $("#filter_department_id").val();
      if (
        departmentFilter === "all" ||
        departmentFilter === "" ||
        departmentFilter === null
      ) {
        // Get the department ID for this row
        let departmentId = 0;
        if (data.row_department_id !== undefined) {
          departmentId = data.row_department_id;
        } else if (data.department_stock && data.department_stock[0]) {
          departmentId = data.department_stock[0].department_id;
        }

        // Apply background color based on department ID
        if (departmentId) {
          $(row).css(
            "background-color",
            getDepartmentColor(parseInt(departmentId))
          );
        }
      }
    },
    drawCallback: function () {
      // Any draw callbacks can go here
    },
    drawCallback: function () {
      $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
    },
  });

  // Make rows appear clickable
  $("#stockTable tbody").css("cursor", "pointer");

  // Function to load summary totals
  function loadSummaryTotals(departmentId = "all") {
    const data = { action: "get_totals" };
    if (departmentId !== "all" && departmentId !== "") {
      data.department_id = departmentId;
    }
    $.ajax({
      url: "ajax/php/item-master.php",
      type: "POST",
      dataType: "json",
      data: data,
      success: function (resp) {
        if (resp && resp.status === "success") {
          const data = resp.data;
          $("#total-cost").text(
            "Rs. " +
              parseFloat(data.total_cost || 0).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
          );
          $("#total-invoice").text(
            "Rs. " +
              parseFloat(data.total_invoice || 0).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
          );
          $("#profit-percentage").text(
            parseFloat(data.profit_percentage || 0).toLocaleString("en-US", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }) + "%"
          );
        } else {
          $("#total-cost").text("Error");
          $("#total-invoice").text("Error");
          $("#profit-percentage").text("Error");
        }
      },
      error: function () {
        $("#total-cost").text("Error");
        $("#total-invoice").text("Error");
        $("#profit-percentage").text("Error");
      },
    });
  }

  // Load summary totals on page load
  loadSummaryTotals();

  // Format function for row details (ARN-wise Last Price and Invoice Price)
  function renderArnWiseTable(lots) {
    if (!Array.isArray(lots) || lots.length === 0) {
      return '<div class="p-2 text-muted">No ARN lots available</div>';
    }
    let html =
      '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
    html +=
      '<thead class="table-light"><tr>' +
      "<th>ARN No</th>" +
      '<th class="text-end">Cost</th>' +
      '<th class="text-end">Qty</th>' +
      '<th class="text-end">Total</th>' +
      '<th class="text-end">List Price</th>' +
      '<th class="text-end">Selling Price</th>' +
      '<th class="text-end">Total</th>' +
      "</tr></thead><tbody>";
    lots.forEach(function (l) {
      html +=
        "<tr>" +
        "<td>" +
        (l.arn_no || "-") +
        "</td>" +
        '<td class="text-end">' +
        Number(l.cost || 0).toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }) +
        "</td>" +
        '<td class="text-end">' +
        formatQty(l.qty || 0) +
        "</td>" +
        '<td class="text-end">' +
        Number((l.cost || 0) * (l.qty || 0)).toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }) +
        "</td>" +
        '<td class="text-end">' +
        Number(l.list_price || 0).toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }) +
        "</td>" +
        '<td class="text-end">' +
        Number(l.invoice_price || 0).toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }) +
        "</td>" +
        '<td class="text-end">' +
        Number((l.invoice_price || 0) * (l.qty || 0)).toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }) +
        "</td>" +
        "</tr>";
    });
    html += "</tbody></table></div>";
    return html;
  }

  // Toggle details on click of first column
  $("#stockTable tbody").on("click", "td.details-control", function (e) {
    e.stopPropagation();
    var tr = $(this).closest("tr");
    var row = table.row(tr);
    var icon = $(this).find("span.mdi");

    if (row.child.isShown()) {
      // Close
      row.child.hide();
      tr.removeClass("shown");
      icon
        .removeClass("mdi-minus-circle-outline")
        .addClass("mdi-plus-circle-outline");
    } else {
      // Open
      const data = row.data();
      // Show temporary loading content
      const loading = '<div class="p-2 text-muted">Loading ARN lots...</div>';
      row.child(loading).show();
      tr.addClass("shown");
      icon
        .removeClass("mdi-plus-circle-outline")
        .addClass("mdi-minus-circle-outline");

      // Resolve department id context for this row
      let depId = null;
      const filterVal = $("#filter_department_id").val();
      if (filterVal && filterVal !== "all") {
        depId = parseInt(filterVal);
      } else if (data.row_department_id) {
        depId = parseInt(data.row_department_id);
      } else if (
        Array.isArray(data.department_stock) &&
        data.department_stock.length > 0
      ) {
        depId = parseInt(data.department_stock[0].department_id);
      }

      // Fetch fresh lots by item_id (and department if available)
      $.ajax({
        url: "ajax/php/item-master.php",
        type: "POST",
        dataType: "json",
        data: {
          action: "get_stock_tmp_by_item",
          item_id: data.id,
          department_id: depId || 0,
        },
        success: function (resp) {
          if (resp && resp.status === "success") {
            row.child(renderArnWiseTable(resp.data)).show();
          } else {
            row
              .child('<div class="p-2 text-muted">No ARN lots available</div>')
              .show();
          }
        },
        error: function () {
          row
            .child('<div class="p-2 text-danger">Failed to load ARN lots</div>')
            .show();
        },
      });
    }
  });

  // Department filter change handler
  $("#filter_department_id").on("change", function () {
    const depId = $(this).val();
    loadSummaryTotals(depId);
    table.ajax.reload();
  });

  // Row click: navigate to sales-invoice with prefilled item and department
  $("#stockTable tbody").on("click", "tr", function () {
    const rowData = table.row(this).data();
    if (!rowData) return;

    const depFilter = $("#filter_department_id").val();
    let depId = null;
    if (depFilter === "all" || depFilter === "" || depFilter === null) {
      depId =
        rowData.row_department_id ||
        (rowData.department_stock && rowData.department_stock[0]
          ? rowData.department_stock[0].department_id
          : null);
    } else {
      depId = depFilter;
    }

    const itemCode = rowData.code;
    if (itemCode) {
      // Prefer item-master page_id injected from PHP, fallback to navigation anchor
      let pageId =
        typeof window !== "undefined" &&
        window.ITEM_MASTER_PAGE_ID &&
        Number(window.ITEM_MASTER_PAGE_ID) > 0
          ? Number(window.ITEM_MASTER_PAGE_ID)
          : null;

      if (!pageId) {
        const itemMasterAnchor = $(
          'a[href*="item-master.php"][href*="page_id="]'
        )
          .first()
          .attr("href");
        if (itemMasterAnchor) {
          try {
            const linkUrl = new URL(itemMasterAnchor, window.location.origin);
            const pid = linkUrl.searchParams.get("page_id");
            if (pid) {
              pageId = Number(pid);
            }
          } catch (e) {
            const match = itemMasterAnchor.match(/[?&]page_id=([^&]+)/);
            if (match && match[1]) {
              pageId = Number(match[1]);
            }
          }
        }
      }

      // If we cannot detect a valid page_id, don't navigate. Show a message instead.
      if (!pageId || isNaN(pageId) || pageId <= 0) {
        if (typeof swal === "function") {
          swal({
            title: "No Permission",
            text: "You don't have permission to open Item Master.",
            type: "warning",
            timer: 2200,
            showConfirmButton: false,
          });
        } else {
          alert("You don't have permission to open Item Master.");
        }
        return;
      }

      const itemId = rowData.id;
      const url = `item-master.php?page_id=${pageId}&from=live_stock&prefill_item_id=${
        itemId || ""
      }&prefill_item_code=${encodeURIComponent(itemCode)}${
        depId ? `&department_id=${depId}` : ""
      }`;
      window.location.href = url;
    }
  });

  // Export functionality
  $("#exportToExcel, #exportToPdf").on("click", function () {
    const buttonText = $(this).text().trim();
    const isExcelExport = buttonText === "Export to Excel";
    const isPdfExport = buttonText === "Export to PDF";

    // Show loading state
    const originalText = $(this).html();
    $(this).html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exporting...'
    );
    $(this).prop("disabled", true);

    // Get current filter values
    const departmentId = $("#filter_department_id").val();

    // Make AJAX request for export data
    $.ajax({
      url: "ajax/php/item-master.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "export_stock",
        department_id: departmentId,
        status: 1,
        stock_only: 1,
      },
      success: function (response) {
        if (response && response.status === "success") {
          const data = response.data;

          if (data && data.length > 0) {
            if (isPdfExport) {
              exportToPdf(data, departmentId);
            } else if (isExcelExport) {
              exportToExcel(data, departmentId);
            }
          } else {
            showAlert("No data available for export", "warning");
          }
        } else {
          showAlert(
            "Failed to retrieve export data: " +
              (response.message || "Unknown error"),
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        showAlert("Export failed: " + error, "error");
      },
      complete: function () {
        // Restore button state
        $("#exportAllStock, #exportToExcel, #exportToPdf").each(function () {
          const btn = $(this);
          const text =
            btn.attr("id") === "exportAllStock"
              ? "Export All Stock"
              : btn.attr("id") === "exportToExcel"
              ? "Export to Excel"
              : "Export to PDF";
          btn.html(text);
          btn.prop("disabled", false);
        });
      },
    });
  });

  // Function to export data to Excel (CSV format that opens in Excel)
  function exportToExcel(data, departmentId) {
    const deptName =
      departmentId === "all"
        ? "All Departments"
        : $("#filter_department_id option:selected").text();

    let html = `
  <html xmlns:x="urn:schemas-microsoft-com:office:excel">
  <head>
    <meta charset="UTF-8">
    <title>Stock Report - ${deptName}</title>
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
      table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 15px 0;
        font-size: 13px;
      }
      th, td { 
        border: 1px solid #e0e0e0; 
        padding: 10px 12px; 
        text-align: left; 
        vertical-align: top;
      }
      thead th {
        background-color: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        padding: 12px;
      }
      .text-right { text-align: right; }
      .text-center { text-align: center; }
      .summary { 
        margin: 30px 0; 
        padding: 20px; 
        background-color: #f8f9fa; 
        border-radius: 4px;
        border-left: 4px solid #3498db;
      }
      .arn-details { 
        margin: 15px 0; 
        padding: 12px 15px; 
        background-color: #f8fafc; 
        border-radius: 4px;
        border-left: 3px solid #b8daff;
      }
      .arn-table { 
        width: 100%; 
        margin: 10px 0 5px 0;
        font-size: 12px;
      }
      .arn-table thead th {
        background-color: #e7f1ff;
        color: #2c3e50;
        font-size: 11px;
        padding: 8px 10px;
      }
      .item-row { 
        border-bottom: 2px solid #f1f1f1; 
      }
      .status-badge {
        padding: 4px 8px; 
        border-radius: 3px; 
        font-size: 11px; 
        font-weight: 500;
        display: inline-block;
      }
    </style>
  </head>
  <body>
    <div class="header">
      <h1>Live Stock Report - ${deptName}</h1>
      <p>Generated on ${new Date().toLocaleString()}</p>
    </div>

    <table>
      <thead>
        <tr>
          <th>Item Code</th>
          <th>Description</th>
          <th>Category</th>
          <th class="text-right">List Price</th>
          <th class="text-right">Selling Price</th>
          <th class="text-right">Qty</th>
          <th class="text-center">Status</th>
        </tr>
      </thead>
      <tbody>`;

    data.forEach((item) => {
      const statusStyle = {
        "Out of Stock": { bg: "#fee2e2", color: "#991b1b" },
        "Re-order": { bg: "#fef3c7", color: "#92400e" },
        "In Stock": { bg: "#dcfce7", color: "#166534" },
      }[item.stock_status] || { bg: "#f3f4f6", color: "#374151" };

      html += `
        <tr class="item-row">
          <td><strong>${item.code || "-"}</strong></td>
          <td>${item.name || "-"}</td>
          <td>${item.category || "-"}</td>
          <td class="text-right">${
            item.list_price ? parseFloat(item.list_price).toFixed(2) : "0.00"
          }</td>
          <td class="text-right">${
            item.invoice_price
              ? parseFloat(item.invoice_price).toFixed(2)
              : "0.00"
          }</td>
          <td class="text-right">${
            item.quantity != null ? formatQty(item.quantity) : "0"
          }</td>
          <td class="text-center">
            <span class="status-badge" style="background-color: ${
              statusStyle.bg
            }; color: ${statusStyle.color}">
              ${item.stock_status || "-"}
            </span>
          </td>
        </tr>`;

      if (item.arn_lots && item.arn_lots.length > 0) {
        html += `
        <tr>
          <td colspan="7" class="arn-details">
            <strong>ARN Details:</strong>
            <table class="arn-table">
              <thead>
                <tr>
                  <th>ARN No</th>
                  <th class="text-right">Cost</th>
                  <th class="text-right">Qty</th>
                  <th class="text-right">Total Cost</th>
                  <th class="text-right">List Price</th>
                  <th class="text-right">Selling Price</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>`;

        let totalQty = 0;
        let totalValue = 0;

        item.arn_lots.forEach((lot) => {
          const cost = parseFloat(lot.cost || 0);
          const qty = parseFloat(lot.qty || 0);
          const listPrice = parseFloat(lot.list_price || 0);
          const invoicePrice = parseFloat(lot.invoice_price || 0);
          const totalCost = cost * qty;
          const totalInvoice = invoicePrice * qty;

          totalQty += qty;
          totalValue += totalInvoice;

          html += `
                <tr>
                  <td>${lot.arn_no || "-"}</td>
                  <td class="text-right">${cost.toFixed(2)}</td>
                  <td class="text-right">${formatQty(qty)}</td>
                  <td class="text-right">${totalCost.toFixed(2)}</td>
                  <td class="text-right">${listPrice.toFixed(2)}</td>
                  <td class="text-right">${invoicePrice.toFixed(2)}</td>
                  <td class="text-right"><strong>${totalInvoice.toFixed(
                    2
                  )}</strong></td>
                </tr>`;
        });

        html += `
                <tr style="background-color: #f8f9fa; font-weight: 500;">
                  <td><strong>Total</strong></td>
                  <td></td>
                  <td class="text-right"><strong>${formatQty(
                    totalQty
                  )}</strong></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td class="text-right"><strong>${totalValue.toFixed(
                    2
                  )}</strong></td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>`;
      }
    });

    // Add summary section
    const totalItems = data.length;
    const totalQty = data.reduce(
      (sum, item) => sum + parseFloat(item.quantity || 0),
      0
    );

    html += `
      </tbody>
    </table>

    <div class="summary">
      <h3 style="margin-top: 0; color: #2c3e50;">Report Summary</h3>
      <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px; margin: 5px 0;">
          <div style="font-size: 13px; color: #7f8c8d;">Total Items</div>
          <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">${totalItems}</div>
        </div>
        <div style="flex: 1; min-width: 200px; margin: 5px 0;">
          <div style="font-size: 13px; color: #7f8c8d;">Total Quantity</div>
          <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">
            ${formatQty(totalQty)}
          </div>
        </div>
        <div style="flex: 1; min-width: 200px; margin: 5px 0;">
          <div style="font-size: 13px; color: #7f8c8d;">Report Generated</div>
          <div style="font-size: 14px; font-weight: 500; color: #2c3e50;">${new Date().toLocaleString()}</div>
        </div>
      </div>
    </div>

    <div style="margin-top: 40px; padding-top: 20px; text-align: center; font-size: 11px; color: #7f8c8d; border-top: 1px solid #eee;">
      <p>This report was generated by the Live Stock Management System</p>
    </div>
  </body>
  </html>`;

    // Convert HTML to Blob for Excel download
    const blob = new Blob([html], {
      type: "application/vnd.ms-excel;charset=utf-8;",
    });

    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.href = url;

    const timestamp = new Date()
      .toISOString()
      .slice(0, 19)
      .replace(/[:.]/g, "-");
    link.download = `stock_report_${deptName.replace(
      /\s+/g,
      "_"
    )}_${timestamp}.xls`;

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showAlert("Excel file downloaded successfully.", "success");
  }

  // Function to export data to PDF
  function exportToPdf(data, departmentId) {
    // Create HTML content for PDF
    const deptName =
      departmentId === "all"
        ? "All Departments"
        : $("#filter_department_id option:selected").text();

    let html = `
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="utf-8">
      <title>Stock Report - ${deptName}</title>
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
          .report-title {
              font-size: 18px;
              font-weight: bold;
              margin-bottom: 5px;
          }
          table { 
              width: 100%; 
              border-collapse: collapse; 
              margin: 15px 0;
              font-size: 13px;
              page-break-inside: auto;
          }
          th, td { 
              border: 1px solid #e0e0e0; 
              padding: 10px 12px; 
              text-align: left; 
              vertical-align: top;
          }
          thead th {
              background-color: #f8f9fa;
              color: #2c3e50;
              font-weight: 600;
              text-transform: uppercase;
              font-size: 12px;
              padding: 12px;
          }
          .text-right { text-align: right; }
          .text-center { text-align: center; }
          .summary { 
              margin: 30px 0; 
              padding: 20px; 
              background-color: #f8f9fa; 
              border-radius: 4px;
              border-left: 4px solid #3498db;
          }
          .footer { 
              margin-top: 40px; 
              padding-top: 20px;
              text-align: center; 
              font-size: 11px; 
              color: #7f8c8d;
              border-top: 1px solid #eee;
          }
          .arn-details { 
              margin: 15px 0; 
              padding: 12px 15px; 
              background-color: #f8fafc; 
              border-radius: 4px;
              border-left: 3px solid #b8daff;
          }
          .arn-table { 
              width: 100%; 
              margin: 10px 0 5px 0;
              font-size: 12px;
          }
          .arn-table thead th {
              background-color: #e7f1ff;
              color: #2c3e50;
              font-size: 11px;
              padding: 8px 10px;
          }
          .arn-table td {
              padding: 6px 10px;
          }
          .item-row { 
              border-bottom: 2px solid #f1f1f1; 
          }
          .item-row:hover {
              background-color: #f8f9fa;
          }
          .no-data {
              color: #7f8c8d;
              font-style: italic;
              text-align: center;
              padding: 20px;
          }
          .section-title {
              font-size: 14px;
              font-weight: 600;
              color: #2c3e50;
              margin: 5px 0;
          }
      </style>
  </head>
  <body>
      <div class="header">
          <div class="report-title">Live Stock Report - ${deptName}</div>
          <p>Generated on ${new Date().toLocaleString()}</p>
      </div>

      <table>
          <thead>
              <tr>
                  <th>Item Code</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th class="text-right">List Price</th>
                  <th class="text-right">Selling Price</th>
                  <th class="text-right">Qty</th>
                  <th class="text-center">Status</th>
              </tr>
          </thead>
          <tbody>
`;

    data.forEach((item) => {
      html += `
              <tr class="item-row">
                  <td><strong>${item.code || "-"}</strong></td>
                  <td>${item.name || "-"}</td>
                  <td>${item.category || "-"}</td>
                  <td class="text-right">${
                    item.list_price
                      ? parseFloat(item.list_price).toFixed(2)
                      : "0.00"
                  }</td>
                  <td class="text-right">${
                    item.invoice_price
                      ? parseFloat(item.invoice_price).toFixed(2)
                      : "0.00"
                  }</td>
                  <td class="text-right">${
                    item.quantity
                      ? formatQty(item.quantity)
                      : "0"
                  }</td>
                  <td class="text-center"><span style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; 
                      background-color: ${
                        item.stock_status === "Out of Stock"
                          ? "#fee2e2"
                          : item.stock_status === "Re-order"
                          ? "#fef3c7"
                          : "#dcfce7"
                      }; 
                      color: ${
                        item.stock_status === "Out of Stock"
                          ? "#991b1b"
                          : item.stock_status === "Re-order"
                          ? "#92400e"
                          : "#166534"
                      };">
                      ${item.stock_status || "-"}
                  </span></td>
              </tr>`;

      if (item.arn_lots && item.arn_lots.length > 0) {
        html += `
              <tr>
                  <td colspan="7" class="arn-details">
                      <div class="section-title">ARN Details</div>
                      <table class="arn-table">
                          <thead>
                              <tr>
                                  <th>ARN No</th>
                                  <th class="text-right">Cost</th>
                                  <th class="text-right">Qty</th>
                                  <th class="text-right">Total Cost</th>
                                  <th class="text-right">List Price</th>
                                  <th class="text-right">Selling Price</th>
                                  <th class="text-right">Total</th>
                              </tr>
                          </thead>
                          <tbody>`;

        item.arn_lots.forEach((lot) => {
          const cost = parseFloat(lot.cost || 0);
          const qty = parseFloat(lot.qty || 0);
          const listPrice = parseFloat(lot.list_price || 0);
          const invoicePrice = parseFloat(lot.invoice_price || 0);

          html += `
                              <tr>
                                  <td>${lot.arn_no || "-"}</td>
                                  <td class="text-right">${cost.toFixed(2)}</td>
                                  <td class="text-right">${formatQty(qty)}</td>
                                  <td class="text-right">${(cost * qty).toFixed(
                                    2
                                  )}</td>
                                  <td class="text-right">${listPrice.toFixed(
                                    2
                                  )}</td>
                                  <td class="text-right">${invoicePrice.toFixed(
                                    2
                                  )}</td>
                                  <td class="text-right"><strong>${(
                                    invoicePrice * qty
                                  ).toFixed(2)}</strong></td>
                              </tr>`;
        });

        // Add ARN totals row
        const totalQty = item.arn_lots.reduce(
          (sum, lot) => sum + parseFloat(lot.qty || 0),
          0
        );
        const totalValue = item.arn_lots.reduce((sum, lot) => {
          return (
            sum + parseFloat(lot.invoice_price || 0) * parseFloat(lot.qty || 0)
          );
        }, 0);

        html += `
                              <tr style="background-color: #f8f9fa; font-weight: 500;">
                                  <td><strong>Total</strong></td>
                                  <td></td>
                                  <td class="text-right"><strong>${formatQty(
                                    totalQty
                                  )}</strong></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td class="text-right"><strong>${totalValue.toFixed(
                                    2
                                  )}</strong></td>
                              </tr>
                          </tbody>
                      </table>
                  </td>
              </tr>`;
      }
    });

    html += `
          </tbody>
      </table>

      <div class="summary">
          <h3 style="margin-top: 0; color: #2c3e50;">Report Summary</h3>
          <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Total Items</div>
                  <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">${
                    data.length
                  }</div>
              </div>
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Total Quantity</div>
                  <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">
                      ${formatQty(
                        data.reduce(
                          (sum, item) => sum + parseFloat(item.quantity || 0),
                          0
                        )
                      )}
                  </div>
              </div>
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Report Generated</div>
                  <div style="font-size: 14px; font-weight: 500; color: #2c3e50;">${new Date().toLocaleString()}</div>
              </div>
          </div>
      </div>

      <div class="footer">
          <p>This report was generated by the Live Stock Management System</p>
          <p style="margin: 5px 0 0 0; font-size: 10px; color: #bdc3c7;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></p>
      </div>

      <script>
          // Add page numbers
          document.addEventListener('DOMContentLoaded', function() {
              const totalPages = Math.ceil(document.getElementsByTagName('table').length / 2); // Adjust divisor based on content
              document.querySelectorAll('.pageNumber').forEach(el => el.textContent = '1');
              document.querySelectorAll('.totalPages').forEach(el => el.textContent = totalPages);
          });
      </script>
  </body>
  </html>`;

    // Create a new window with the HTML content
    const printWindow = window.open("", "_blank");
    printWindow.document.write(html);
    printWindow.document.close();

    // Wait for content to load, then print (which will trigger PDF download in most browsers)
    printWindow.onload = function () {
      printWindow.print();
      printWindow.onafterprint = function () {
        printWindow.close();
      };
    };

    showAlert(
      "PDF export completed. Use browser print dialog to save as PDF.",
      "success"
    );
  }

  // Function to show alerts
  function showAlert(message, type) {
    if (typeof swal === "function") {
      swal({
        title:
          type === "error"
            ? "Error"
            : type === "warning"
            ? "Warning"
            : "Success",
        text: message,
        type: type,
        timer: 3000,
        showConfirmButton: false,
      });
    } else {
      alert(message);
    }
  }
  if ($.fn.select2) {
    $("#filter_department_id").select2({
      placeholder: "Select Department",
      allowClear: true,
    });
  }
});
