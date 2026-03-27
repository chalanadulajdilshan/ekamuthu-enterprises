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
          '<tr><td colspan="12" class="text-center py-3"><i class="uil uil-spinner-alt uil-spin me-1"></i>Loading...</td></tr>'
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
              '<tr><td colspan="12" class="text-center text-muted py-3">No unsettled credit transport found.</td></tr>'
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
                data: "due_date",
                render: function (data, type, row) {
                  if (!data || data === "-") return data;
                  if (type !== "display") return data;

                  var dueDate = new Date(data);
                  var today = new Date();
                  today.setHours(0, 0, 0, 0);

                  var timeDiff = dueDate.getTime() - today.getTime();
                  var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

                  if (daysDiff < 0) {
                    return (
                      '<span class="badge bg-danger rounded-pill" title="Overdue by ' +
                      Math.abs(daysDiff) +
                      ' days">' +
                      data +
                      " (Overdue)</span>"
                    );
                  } else if (daysDiff === 0) {
                    return (
                      '<span class="badge bg-danger rounded-pill">' +
                      data +
                      " (Due Today)</span>"
                    );
                  } else if (daysDiff <= 3) {
                    return (
                      '<span class="badge bg-danger rounded-pill">' +
                      data +
                      " (Due Soon)</span>"
                    );
                  }
                  return data;
                },
              },
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
            createdRow: function (row, data, dataIndex) {
              if (!data.due_date || data.due_date === "-") return;

              var dueDate = new Date(data.due_date);
              var today = new Date();
              today.setHours(0, 0, 0, 0);

              var timeDiff = dueDate.getTime() - today.getTime();
              var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

              if (daysDiff < 0) {
                $(row).addClass("overdue-row");
              } else if (daysDiff === 0) {
                $(row).addClass("due-today-row");
              } else if (daysDiff <= 3) {
                $(row).addClass("due-soon-row");
              }
            },
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
          '<tr><td colspan="12" class="text-center text-danger py-3">Failed to load report</td></tr>'
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
    loadSettlementModal(transportId);
  });

  // Payment method change - show/hide fields
  $("#report_settlement_payment_type").on("change", function () {
    var method = $(this).val();
    $("#ts_bankDetails").hide();
    $("#ts_chequeDetails").hide();
    $("#ts_transferDetails").hide();

    if (method === "Cheque") {
      $("#ts_bankDetails").show();
      $("#ts_chequeDetails").show();
    } else if (method === "Bank Transfer") {
      $("#ts_bankDetails").show();
      $("#ts_transferDetails").show();
    }
  });

  // Bank change - load branches
  $("#ts_bank_id").on("change", function () {
    var bankId = $(this).val();
    var $branchSelect = $("#ts_branch_id");

    if (!bankId) {
      $branchSelect
        .html('<option value="">Select Bank First</option>')
        .prop("disabled", true);
      return;
    }

    $branchSelect
      .html('<option value="">Loading...</option>')
      .prop("disabled", true);

    $.post(
      "ajax/php/transport-settlement.php",
      { action: "get_branches", bank_id: bankId },
      function (response) {
        if (response.status === "success" && response.branches) {
          var html = '<option value="">Select Branch</option>';
          response.branches.forEach(function (b) {
            html +=
              '<option value="' +
              b.id +
              '">' +
              b.name +
              (b.code ? " (" + b.code + ")" : "") +
              "</option>";
          });
          $branchSelect.html(html).prop("disabled", false);
        } else {
          $branchSelect
            .html('<option value="">No branches found</option>')
            .prop("disabled", true);
        }
      },
      "json"
    );
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
              '<tr><td colspan="7" class="text-center text-muted py-3">No settlements recorded yet.</td></tr>'
            );
          } else {
            settlements.forEach(function (s) {
              var paymentType = s.payment_type || "Cash";
              var badgeClass = "bg-secondary";
              if (paymentType.toLowerCase().includes("cash"))
                badgeClass = "bg-success";
              else if (paymentType.toLowerCase().includes("cheque"))
                badgeClass = "bg-warning text-dark";
              else if (
                paymentType.toLowerCase().includes("bank") ||
                paymentType.toLowerCase().includes("transfer")
              )
                badgeClass = "bg-info text-dark";

              // Build details column
              var details = [];
              if (s.bank_name) details.push("Bank: " + s.bank_name);
              if (s.branch_name) details.push("Branch: " + s.branch_name);
              if (s.cheque_no) details.push("Cheque#: " + s.cheque_no);
              if (s.cheque_date) details.push("Cheque Date: " + s.cheque_date);
              if (s.account_no) details.push("A/C: " + s.account_no);
              if (s.reference_no) details.push("Ref: " + s.reference_no);
              if (s.transfer_date)
                details.push("Transfer Date: " + s.transfer_date);

              var detailsHtml = details.length > 0
                ? '<small>' + details.join("<br>") + '</small>'
                : "-";

              var html =
                "<tr>" +
                "<td>" + (s.id || "-") + "</td>" +
                "<td>" + (s.settlement_date || "-") + "</td>" +
                '<td><span class="badge ' + badgeClass + '">' + paymentType + "</span></td>" +
                "<td>" + detailsHtml + "</td>" +
                '<td class="text-end">' + formatAmount(s.amount || 0) + "</td>" +
                "<td>" + (s.remark || "-") + "</td>" +
                '<td><button type="button" class="btn btn-sm btn-outline-danger report-delete-settlement-btn" data-id="' +
                s.id + '" title="Delete"><i class="uil uil-trash-alt"></i></button></td>' +
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

  // Reset form fields
  function resetSettlementForm() {
    $("#report_settlement_amount").val("");
    $("#report_settlement_remark").val("");
    $("#report_settlement_payment_type").val("Cash").trigger("change");
    $("#ts_bank_id").val("");
    $("#ts_branch_id")
      .html('<option value="">Select Bank First</option>')
      .prop("disabled", true);
    $("#ts_cheque_no").val("");
    $("#ts_cheque_date").val("");
    $("#ts_transfer_date").val("");
    $("#ts_account_no").val("");
    $("#ts_reference_no").val("");
  }

  // Add settlement from report
  $("#btn-report-add-settlement").on("click", function () {
    var transportId = $("#report_settlement_transport_id").val();
    var amount = parseAmount($("#report_settlement_amount").val());
    var date = $("#report_settlement_date").val();
    var paymentType = $("#report_settlement_payment_type").val();
    var remark = $("#report_settlement_remark").val();

    if (amount <= 0) {
      swal("Error", "Please enter a valid amount", "error");
      return;
    }

    if (!date) {
      swal("Error", "Please select a date", "error");
      return;
    }

    // Build data object
    var postData = {
      action: "add_settlement",
      transport_id: transportId,
      amount: amount,
      settlement_date: date,
      payment_type: paymentType,
      remark: remark,
    };

    // Add payment-specific fields
    if (paymentType === "Cheque" || paymentType === "Bank Transfer") {
      postData.bank_id = $("#ts_bank_id").val();
      postData.branch_id = $("#ts_branch_id").val();
    }

    if (paymentType === "Cheque") {
      postData.cheque_date = $("#ts_cheque_date").val();
      postData.cheque_no = $("#ts_cheque_no").val();
    }

    if (paymentType === "Bank Transfer") {
      postData.transfer_date = $("#ts_transfer_date").val();
      postData.account_no = $("#ts_account_no").val();
      postData.reference_no = $("#ts_reference_no").val();
    }

    $.post(
      "ajax/php/transport-settlement.php",
      postData,
      function (response) {
        if (response.status === "success") {
          resetSettlementForm();

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

  // Auto-load report on page load
  loadReport();
});
