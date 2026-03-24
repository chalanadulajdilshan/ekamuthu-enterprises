$(document).ready(function () {
  var reportTable = null;

  function formatAmount(val) {
    var num = parseFloat(val) || 0;
    return num.toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function parseAmount(val) {
    if (!val) return 0;
    return parseFloat(String(val).replace(/,/g, "")) || 0;
  }

  // Load report
  function loadReport() {
    var customerId = $("#customer_id").val() || "";

    if (reportTable) {
      reportTable.destroy();
      reportTable = null;
    }

    $.ajax({
      url: "ajax/php/transport-outstanding-report.php",
      type: "POST",
      data: {
        action: "get_report",
        customer_id: customerId,
      },
      dataType: "JSON",
      beforeSend: function () {
        $("#reportTableBody").html(
          '<tr><td colspan="11" class="text-center py-3"><i class="uil uil-spinner-alt uil-spin me-1"></i>Loading...</td></tr>'
        );
      },
      success: function (response) {
        if (response.status === "success") {
          var data = response.data || [];
          var summary = response.summary || {};

          // Update summary cards
          $("#statTotalAmount").text(formatAmount(summary.total_amount));
          $("#statTotalSettled").text(formatAmount(summary.total_settled));
          $("#statTotalRemaining").text(formatAmount(summary.total_remaining));
          $("#statTotalRecords").text(summary.total_records || 0);

          $("#summarySection").show();

          // Build table
          var $tbody = $("#reportTableBody");
          $tbody.empty();

          if (data.length === 0) {
            $tbody.html(
              '<tr><td colspan="11" class="text-center text-muted py-3">No unsettled credit transport found.</td></tr>'
            );
            return;
          }

          // Initialize DataTable
          reportTable = $("#reportTable").DataTable({
            data: data,
            destroy: true,
            paging: true,
            pageLength: 25,
            ordering: true,
            order: [[0, "asc"]],
            columns: [
              {
                data: "customer_name",
                render: function (data, type, row) {
                  return (
                    (row.customer_code ? row.customer_code + " - " : "") + data
                  );
                },
              },
              {
                data: "bill_number",
                render: function (data, type, row) {
                  return (
                    '<a href="equipment-rent-master.php?page_id=' +
                    row.rent_id +
                    '" class="text-primary" title="Open Bill">' +
                    data +
                    "</a>"
                  );
                },
              },
              { data: "transport_date" },
              {
                data: "employee_name",
                render: function (data, type, row) {
                  return row.employee_code
                    ? row.employee_code + " - " + data
                    : data;
                },
              },
              {
                data: "vehicle_no",
                render: function (data, type, row) {
                  return (
                    data +
                    (row.vehicle_brand ? " (" + row.vehicle_brand + ")" : "")
                  );
                },
              },
              { data: "start_location" },
              { data: "end_location" },
              {
                data: "total_amount",
                className: "text-end",
                render: function (data, type) {
                  if (type !== "display") return parseAmount(data);
                  return formatAmount(data);
                },
              },
              {
                data: "total_settled",
                className: "text-end",
                render: function (data, type) {
                  if (type !== "display") return parseAmount(data);
                  var val = parseAmount(data);
                  if (val > 0) {
                    return (
                      '<span class="text-success">' +
                      formatAmount(data) +
                      "</span>"
                    );
                  }
                  return "0.00";
                },
              },
              {
                data: "remaining_amount",
                className: "text-end fw-bold",
                render: function (data, type) {
                  if (type !== "display") return parseAmount(data);
                  return (
                    '<span class="text-danger">' +
                    formatAmount(data) +
                    "</span>"
                  );
                },
              },
              {
                data: "transport_id",
                className: "text-center",
                orderable: false,
                render: function (data, type, row) {
                  return (
                    '<button type="button" class="btn btn-sm btn-outline-success btn-settle-transport" ' +
                    'data-id="' +
                    data +
                    '" data-amount="' +
                    row.remaining_amount +
                    '" data-total="' +
                    row.total_amount +
                    '" title="Add Settlement Payment">' +
                    '<i class="uil uil-money-bill me-1"></i>Settle</button>'
                  );
                },
              },
            ],
            drawCallback: function () {
              // Calculate footer totals
              var api = this.api();
              var totalAmt = 0,
                totalSettled = 0,
                totalRemaining = 0;

              api.rows({ search: "applied" }).every(function () {
                var d = this.data();
                totalAmt += parseAmount(d.total_amount);
                totalSettled += parseAmount(d.total_settled);
                totalRemaining += parseAmount(d.remaining_amount);
              });

              $("#tblTotalAmount").text(formatAmount(totalAmt));
              $("#tblTotalSettled").text(formatAmount(totalSettled));
              $("#tblTotalRemaining").text(formatAmount(totalRemaining));
            },
          });
        }
      },
      error: function () {
        $("#reportTableBody").html(
          '<tr><td colspan="11" class="text-center text-danger py-3">Failed to load report</td></tr>'
        );
      },
    });
  }

  // Search button
  $("#searchBtn").on("click", function () {
    loadReport();
  });

  // Reset button
  $("#resetBtn").on("click", function () {
    $("#customer_id").val("");
    $("#customer_code").val("");
    loadReport();
  });

  // Print button
  $("#printBtn").on("click", function () {
    window.print();
  });

  // Clear customer
  $("#clearCustomer").on("click", function () {
    $("#customer_id").val("");
    $("#customer_code").val("");
  });

  // Settle transport from report
  $(document).on("click", ".btn-settle-transport", function () {
    var transportId = $(this).data("id");
    var remainingAmount = $(this).data("amount");
    var totalAmount = $(this).data("total");

    // Load settlement history and show modal
    loadSettlementModal(transportId);
  });

  function loadSettlementModal(transportId) {
    $.post(
      "ajax/php/transport-settlement.php",
      {
        action: "get_settlements",
        transport_id: transportId,
      },
      function (response) {
        if (response.status === "success") {
          var status = response.settlement_status || {};
          var settlements = response.settlements || [];

          $("#report_settlement_transport_id").val(transportId);
          $("#report_settlement_total").text(
            formatAmount(status.total_amount || 0)
          );
          $("#report_settlement_paid").text(
            formatAmount(status.total_settled || 0)
          );
          $("#report_settlement_remaining").text(
            formatAmount(status.remaining_amount || 0)
          );

          // Render settlements table
          var $tbody = $("#reportSettlementsTableBody");
          $tbody.empty();

          if (settlements.length === 0) {
            $tbody.html(
              '<tr><td colspan="5" class="text-center text-muted py-3">No settlements recorded yet.</td></tr>'
            );
          } else {
            settlements.forEach(function (s) {
              var html =
                "<tr>" +
                "<td>" +
                (s.id || "-") +
                "</td>" +
                "<td>" +
                (s.settlement_date || "-") +
                "</td>" +
                '<td class="text-end">' +
                formatAmount(s.amount || 0) +
                "</td>" +
                "<td>" +
                (s.remark || "-") +
                "</td>" +
                '<td><button type="button" class="btn btn-sm btn-outline-danger report-delete-settlement-btn" data-id="' +
                s.id +
                '" title="Delete"><i class="uil uil-trash-alt"></i></button></td>' +
                "</tr>";
              $tbody.append(html);
            });
          }

          $("#ReportSettlementModal").modal("show");
        }
      },
      "json"
    );
  }

  // Add settlement from report
  $("#btn-report-add-settlement").on("click", function () {
    var transportId = $("#report_settlement_transport_id").val();
    var amount = parseAmount($("#report_settlement_amount").val());
    var date = $("#report_settlement_date").val();
    var remark = $("#report_settlement_remark").val();

    if (amount <= 0) {
      swal("Error", "Please enter a valid amount", "error");
      return;
    }

    if (!date) {
      swal("Error", "Please select a date", "error");
      return;
    }

    $.post(
      "ajax/php/transport-settlement.php",
      {
        action: "add_settlement",
        transport_id: transportId,
        amount: amount,
        settlement_date: date,
        remark: remark,
      },
      function (response) {
        if (response.status === "success") {
          $("#report_settlement_amount").val("");
          $("#report_settlement_remark").val("");

          // Reload settlement modal
          loadSettlementModal(transportId);

          swal({
            title: "Success!",
            text: "Settlement payment added",
            type: "success",
            timer: 1500,
            showConfirmButton: false,
          });

          // Reload report after short delay
          setTimeout(function () {
            loadReport();
          }, 1600);
        } else {
          swal(
            "Error",
            response.message || "Failed to add settlement",
            "error"
          );
        }
      },
      "json"
    );
  });

  // Delete settlement from report
  $(document).on("click", ".report-delete-settlement-btn", function () {
    var settlementId = $(this).data("id");

    swal(
      {
        title: "Delete Settlement?",
        text: "This will remove this settlement payment.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete!",
        cancelButtonText: "Cancel",
      },
      function (isConfirm) {
        if (isConfirm) {
          $.post(
            "ajax/php/transport-settlement.php",
            {
              action: "delete_settlement",
              settlement_id: settlementId,
            },
            function (response) {
              if (response.status === "success") {
                var transportId = $(
                  "#report_settlement_transport_id"
                ).val();
                loadSettlementModal(transportId);
                loadReport();
                swal("Deleted!", "Settlement removed", "success");
              } else {
                swal(
                  "Error",
                  response.message || "Failed to delete",
                  "error"
                );
              }
            },
            "json"
          );
        }
      }
    );
  });

  // Customer select modal
  var customerTable = null;

  $("#customerModal").on("shown.bs.modal", function () {
    if (customerTable) return;

    // Load customers
    $.post(
      "ajax/php/customer-master.php",
      { action: "get_all_customers" },
      function (response) {
        var customers = [];
        
        if (Array.isArray(response)) {
          customers = response;
        } else if (response.data && Array.isArray(response.data)) {
          customers = response.data;
        } else if (response.status === "success" && Array.isArray(response.customers)) {
          customers = response.customers;
        }

        customerTable = $("#customerSelectTbl").DataTable({
          data: customers,
          columns: [
            { data: "code" },
            { data: "name" },
            { data: "mobile" },
          ],
          pageLength: 10,
          ordering: true,
          order: [[1, "asc"]],
          destroy: true,
        });

        $("#customerSelectTbl tbody").on("click", "tr", function () {
          var data = customerTable.row(this).data();
          if (data) {
            $("#customer_id").val(data.id);
            $("#customer_code").val(data.code + " - " + data.name);
            $("#customerModal").modal("hide");
          }
        });
      },
      "json"
    ).fail(function() {
      alert("Failed to load customers");
    });
  });

  // Auto-load report on page load
  loadReport();
});
