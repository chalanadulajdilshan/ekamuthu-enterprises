jQuery(document).ready(function () {
  // Store rental items in memory
  var rentItems = [];
  var currentRentOneDay = 0;
  var currentRentOneMonth = 0;
  var currentDepositOneDay = 0;
  var totalCalculatedDeposit = 0;

  // Calculate amount and return date
  function calculateRentDetails() {
    var rentType = $("#item_rent_type").val();
    var duration = parseFloat($("#item_duration").val()) || 0;
    var qty = parseFloat($("#item_qty").val()) || 1;
    var rentalDate = $("#item_rental_date").val();

    if (!rentalDate || duration <= 0) {
      $("#item_amount").val("0.00");
      return;
    }

    var amount = 0;
    var returnDate = new Date(rentalDate);

    if (rentType === "day") {
      amount = currentRentOneDay * duration * qty;
      returnDate.setDate(returnDate.getDate() + duration);
      $("#duration_label").text("Days");
    } else {
      amount = currentRentOneMonth * duration * qty;
      returnDate.setMonth(returnDate.getMonth() + duration);
      $("#duration_label").text("Months");
    }

    // Format amount
    $("#item_amount").val(amount.toFixed(2));

    // Format return date YYYY-MM-DD
    var yyyy = returnDate.getFullYear();
    var mm = String(returnDate.getMonth() + 1).padStart(2, "0");
    var dd = String(returnDate.getDate()).padStart(2, "0");
    $("#item_return_date").val(yyyy + "-" + mm + "-" + dd);
  }

  // Calculate duration from return date
  function calculateDurationFromDates() {
    var rentType = $("#item_rent_type").val();
    var rentalDateStr = $("#item_rental_date").val();
    var returnDateStr = $("#item_return_date").val();

    if (!rentalDateStr || !returnDateStr) return;

    var rentalDate = new Date(rentalDateStr);
    var returnDate = new Date(returnDateStr);
    var duration = 0;
    var timeDiff = returnDate - rentalDate;

    if (timeDiff <= 0) {
      $("#item_duration").val(0);
      $("#item_amount").val("0.00");
      return;
    }

    if (rentType === "day") {
      // Calculate days
      duration = Math.ceil(timeDiff / (1000 * 3600 * 24));
      $("#duration_label").text("Days");
    } else {
      // Calculate months
      var months = (returnDate.getFullYear() - rentalDate.getFullYear()) * 12;
      months -= rentalDate.getMonth();
      months += returnDate.getMonth();
      // Adjust for partial months (simple logic, can be refined)
      if (returnDate.getDate() < rentalDate.getDate()) {
        months--;
      }
      // If less than a month but positive, count as 1 or use fractional?
      // For simplicity allow integers for now, or use fractional months?
      // Let's try to get nearest whole number or 1 decimal.
      duration = months <= 0 ? 0 : months;

      // Alternative simple month calc: days / 30
      // duration = (timeDiff / (1000 * 3600 * 24 * 30)).toFixed(1);
    }

    $("#item_duration").val(duration);

    // Recalculate Amount only
    var amount = 0;
    var qty = parseFloat($("#item_qty").val()) || 1;
    if (rentType === "day") {
      amount = currentRentOneDay * duration * qty;
    } else {
      amount = currentRentOneMonth * duration * qty;
    }
    $("#item_amount").val(amount.toFixed(2));
  }

  // Update items table display
  function updateItemsTable() {
    var tbody = $("#rentItemsTable tbody");
    tbody.empty();

    if (rentItems.length === 0) {
      $("#noItemsMessage").show();
      $("#rentItemsTable").hide();
    } else {
      $("#noItemsMessage").hide();
      $("#rentItemsTable").show();

      rentItems.forEach(function (item, index) {
        var statusBadge =
          item.status === "returned"
            ? '<span class="badge bg-soft-info">Returned</span>'
            : '<span class="badge bg-soft-warning">Rented</span>';

        var actionBtns = "";
        if (item.status === "rented" || (item.pending_qty && item.pending_qty > 0)) {
          actionBtns =
            '<button class="btn btn-sm btn-success process-return-btn me-1" data-item-id="' +
            (item.id || '') +
            '" data-index="' +
            index +
            '" title="Process Return"><i class="uil uil-redo"></i></button>';
        }
        if (item.id && (item.total_returned_qty > 0 || item.status === "returned")) {
          actionBtns +=
            '<button class="btn btn-sm btn-info view-returns-btn me-1" data-item-id="' +
            item.id +
            '" title="View Returns History"><i class="uil uil-history"></i></button>';
        }
        actionBtns +=
          '<button class="btn btn-sm btn-danger remove-item-btn" data-index="' +
          index +
          '" title="Remove"><i class="uil uil-trash"></i></button>';

        var row =
          "<tr>" +
          "<td>" +
          (index + 1) +
          "</td>" +
          "<td>" +
          item.equipment_display +
          "</td>" +
          "<td>" +
          (item.sub_equipment_display || "-") +
          "</td>" +
          "<td>" +
          (item.rent_type === "month" ? "Month" : "Day") +
          "</td>" +
          "<td>" +
          '<span class="badge ' +
          (item.rent_type === "month" ? "bg-primary" : "bg-info") +
          '">' +
          parseFloat(item.duration).toFixed(0) +
          (item.rent_type === "month" ? " Months" : " Days") +
          "</span>" +
          "</td>" +
          "<td>" +
          parseFloat(item.quantity).toFixed(0) +
          "</td>" +
          "<td>" +
          '<span class="badge bg-soft-success">' + (item.total_returned_qty || 0) + '</span> / ' +
          '<span class="badge bg-soft-warning">' + (item.pending_qty !== undefined ? item.pending_qty : item.quantity) + ' pending</span>' +
          "</td>" +
          "<td>" +
          parseFloat(item.amount).toFixed(2) +
          "</td>" +
          "<td>" +
          (parseFloat(item.deposit_one_day || 0) * parseFloat(item.quantity || 1)).toFixed(2) +
          "</td>" +
          "<td>" +
          item.rental_date +
          "</td>" +
          "<td>" +
          (item.return_date || "-") +
          "</td>" +
          "<td>" +
          statusBadge +
          "</td>" +
          "<td>" +
          actionBtns +
          "</td>" +
          "</tr>";
        tbody.append(row);
      });
    }

    // Update totals summary
    updateTotalsSummary();

    // Bind process return button event
    $(".process-return-btn").off("click").on("click", function () {
      var itemId = $(this).data("item-id");
      var index = $(this).data("index");

      if (itemId) {
        // If item is saved, open return modal
        openReturnModal(itemId);
      } else {
        // If item is not saved yet
        swal({
          title: "Error!",
          text: "Please save the rental record first before processing returns",
          type: "warning",
          timer: 3000,
          showConfirmButton: false
        });
      }
    });

    // Bind view returns history button event
    $(".view-returns-btn").off("click").on("click", function () {
      var itemId = $(this).data("item-id");
      viewReturnsHistory(itemId);
    });
  }

  // Check if sub-equipment already added
  function isSubEquipmentAlreadyAdded(subEquipmentId) {
    return rentItems.some(function (item) {
      return (
        item.sub_equipment_id == subEquipmentId && item.status === "rented"
      );
    });
  }

  // Add item to list
  $("#addItemBtn").click(function () {
    var equipmentId = $("#item_equipment_id").val();
    var equipmentDisplay = $("#item_equipment_display").val();
    var subEquipmentId = $("#item_sub_equipment_id").val();
    var subEquipmentDisplay = $("#item_sub_equipment_display").val();
    var rentalDate = $("#item_rental_date").val() || $("#rental_date").val();
    var returnDate = $("#item_return_date").val();

    if (!equipmentId) {
      swal({
        title: "Error!",
        text: "Please select an equipment",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }
    var noSubItems = $("#item_equipment_id").data("no_sub_items") == 1;

    if (!subEquipmentId && !noSubItems) {
      swal({
        title: "Error!",
        text: "Please select a sub equipment (unit code)",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }
    if (!noSubItems && isSubEquipmentAlreadyAdded(subEquipmentId)) {
      swal({
        title: "Error!",
        text: "This sub equipment is already added to the list",
        type: "warning",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    var qty = parseFloat($("#item_qty").val()) || 1;
    var returnedQty = parseFloat($("#item_returned_qty").val()) || 0;

    if (returnedQty > qty) {
      swal({
        title: "Error!",
        text: "Returned Qty cannot be greater than Rented Qty",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    rentItems.push({
      id: null,
      equipment_id: equipmentId,
      equipment_display: equipmentDisplay,
      sub_equipment_id: subEquipmentId,
      sub_equipment_display: subEquipmentDisplay,
      rental_date: rentalDate,
      return_date: returnDate,
      rent_type: $("#item_rent_type").val(),
      duration: $("#item_duration").val(),
      quantity: $("#item_qty").val(),
      returned_qty: $("#item_returned_qty").val() || 0,
      amount: $("#item_amount").val(),
      // Store equipment deposit value for calculation
      deposit_one_day: currentDepositOneDay,
      status: "rented",
      remark: "",
      no_sub_items: noSubItems ? 1 : 0
    });

    // Update calculated deposit
    totalCalculatedDeposit +=
      parseFloat(currentDepositOneDay || 0) *
      parseFloat($("#item_qty").val() || 1);
    $("#calculated_deposit_display").text(totalCalculatedDeposit.toFixed(2));
    $("#custom_deposit").val(totalCalculatedDeposit.toFixed(2));

    // Auto-fill custom deposit if it's empty or matches previous calculation
    // var currentCustom = parseFloat($("#custom_deposit").val()) || 0;
    // if (currentCustom === 0 || currentCustom === (totalCalculatedDeposit - parseFloat(currentDepositOneDay || 0))) {
    //     $("#custom_deposit").val(totalCalculatedDeposit.toFixed(2));
    // }

    updateItemsTable();

    // Clear item inputs
    $("#item_sub_equipment_id").val("");
    $("#item_sub_equipment_display").val("");
    $("#item_return_date").val("");
    $("#item_duration").val("");
    $("#item_qty").val(1);
    $("#item_returned_qty").val(0);
    $("#item_amount").val("");
  });

  // Calculate on input changes
  $("#item_rent_type, #item_duration, #item_qty, #item_rental_date").on(
    "change keyup",
    function () {
      calculateRentDetails();
    },
  );

  // Calculate when return date changes
  $("#item_return_date").on("change keyup", function () {
    calculateDurationFromDates();
  });

  // Remove item from list
  $(document).on("click", ".remove-item-btn", function () {
    var index = $(this).data("index");

    // Subtract deposit
    var removedItem = rentItems[index];
    totalCalculatedDeposit -=
      parseFloat(removedItem.deposit_one_day || 0) *
      parseFloat(removedItem.quantity || 1);
    if (totalCalculatedDeposit < 0) totalCalculatedDeposit = 0;
    $("#calculated_deposit_display").text(totalCalculatedDeposit.toFixed(2));
    $("#custom_deposit").val(totalCalculatedDeposit.toFixed(2));

    rentItems.splice(index, 1);
    updateItemsTable();
  });

  // Update totals summary section
  function updateTotalsSummary() {
    var subTotal = 0;
    rentItems.forEach(function (item) {
      subTotal += parseFloat(item.amount) || 0;
    });

    $("#summary_sub_total").text(subTotal.toFixed(2));
  }

  // Update totals when transport cost or deposit changes
  $("#transport_cost, #custom_deposit").on("change keyup", function () {
    updateTotalsSummary();
  });

  // Mark item as returned (in memory)
  $(document).on("click", ".return-item-btn", function () {
    var index = $(this).data("index");
    swal(
      {
        title: "Mark as Returned?",
        text: "This will mark the item as returned. Changes will be saved when you click 'Update'.",
        type: "info",
        showCancelButton: true,
        confirmButtonText: "Yes, mark it!",
      },
      function (isConfirm) {
        if (isConfirm) {
          rentItems[index].status = "returned";
          rentItems[index].return_date = new Date().toISOString().split("T")[0];
          updateItemsTable();
        }
      },
    );
  });

  // Undo return (in memory)
  $(document).on("click", ".undo-return-item-btn", function () {
    var index = $(this).data("index");
    rentItems[index].status = "rented";
    rentItems[index].return_date = null;
    updateItemsTable();
  });

  // Load Equipment Rent Table when modal opens
  $("#EquipmentRentModal").on("shown.bs.modal", function () {
    loadEquipmentRentTable();
  });

  function loadEquipmentRentTable() {
    if ($.fn.DataTable.isDataTable("#equipmentRentTable")) {
      $("#equipmentRentTable").DataTable().destroy();
    }

    $("#equipmentRentTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/equipment-rent-master.php",
        type: "POST",
        data: function (d) {
          d.filter = true;
        },
        dataSrc: function (json) {
          return json.data;
        },
        error: function (xhr) {
          console.error("Server Error:", xhr.responseText);
        },
      },
      columns: [
        { data: "id", title: "#ID" },
        { data: "bill_number", title: "Bill Number" },
        { data: "customer_name", title: "Customer" },
        { data: "rental_date", title: "Rental Date" },
        { data: "received_date", title: "Received Date" },
        { data: "total_items", title: "Items" },
        {
          data: "outstanding_items",
          title: "Outstanding",
          render: function (data, type, row) {
            return data > 0
              ? '<span class="text-danger fw-bold">' + data + "</span>"
              : '<span class="text-success">0</span>';
          },
        },
        { data: "status_label", title: "Status" },
      ],
      order: [[0, "desc"]],
      pageLength: 100,
    });

    // Row click to load rent details
    $("#equipmentRentTable tbody")
      .off("click")
      .on("click", "tr", function () {
        var data = $("#equipmentRentTable").DataTable().row(this).data();
        if (data) {
          loadRentDetails(data.id);
          $("#EquipmentRentModal").modal("hide");
        }
      });
  }

  // Load rent details including items
  function loadRentDetails(rentId) {
    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      data: { action: "get_rent_details", rent_id: rentId },
      dataType: "JSON",
      success: function (result) {
        if (result.status === "success") {
          var rent = result.rent;
          $("#rent_id").val(rent.id);
          $("#code").val(rent.bill_number);
          $("#customer_id").val(rent.customer_id);
          $("#customer_display").val(rent.customer_name);
          $("#rental_date").val(rent.rental_date);
          var $paymentSelect = $("#payment_type_id");
          $paymentSelect.find("option[data-temp='1']").remove();

          if (rent.payment_type_id) {
            if ($paymentSelect.find("option[value='" + rent.payment_type_id + "']").length === 0) {
              // Saved payment type is not in the active list; show it as selected but hidden in dropdown
              var tempLabel = rent.payment_type_name ? (rent.payment_type_name + " (Inactive)") : "(Inactive)";
              $paymentSelect.append(
                "<option data-temp='1' value='" + rent.payment_type_id + "' selected hidden>" +
                tempLabel +
                "</option>"
              );
            }
            $paymentSelect.val(rent.payment_type_id);
          } else {
            $paymentSelect.val("");
          }
          $("#received_date").val(rent.received_date || "");
          $("#received_date_container").show();
          $("#remark").val(rent.remark || "");
          $("#transport_cost").val(rent.transport_cost || "0.00");
          $("#custom_deposit").val(rent.deposit_total || "0.00");

          // Lock manual edits when loading an existing rent
          $("#transport_cost, #custom_deposit").prop("readonly", true);

          // Reset calculated
          totalCalculatedDeposit = 0;

          // Load items
          rentItems = result.items.map(function (item) {
            // Add to calculated total (multiply by qty)
            totalCalculatedDeposit +=
              parseFloat(item.equipment_deposit || 0) *
              parseFloat(item.quantity || 1);

            return {
              id: item.id,
              equipment_id: item.equipment_id,
              equipment_display:
                (item.equipment_code || "") +
                " - " +
                (item.equipment_name || ""),
              sub_equipment_id: item.sub_equipment_id,
              sub_equipment_display: item.sub_equipment_code || "",
              rental_date: item.rental_date,
              return_date: item.return_date,
              rent_type: item.rent_type,
              duration: item.duration,
              quantity: item.quantity,
              // use aggregated values from returns table
              total_returned_qty: item.total_returned_qty || 0,
              pending_qty:
                item.pending_qty !== undefined
                  ? item.pending_qty
                  : item.quantity,
              amount: item.amount,
              deposit_one_day: item.equipment_deposit, // Store this
              status: item.status,
              remark: item.remark,
              no_sub_items: item.no_sub_items == 1 ? 1 : ((!item.sub_equipment_id || item.sub_equipment_id == 0) ? 1 : 0)
            };
          });

          $("#calculated_deposit_display").text(
            totalCalculatedDeposit.toFixed(2),
          );
          updateItemsTable();

          $("#create").hide();
          $("#update").show();
          $("#return-all").show();
          $("#print").show();
        }
      },
    });
  }

  // Print Invoice
  $("#print").click(function (e) {
    e.preventDefault();
    var billNo = $("#code").val();
    if (billNo) {
      window.open("rent-invoice.php?bill_no=" + billNo, "_blank");
    }
  });

  // Load Customer Table
  $("#CustomerSelectModal").on("shown.bs.modal", function () {
    loadCustomerTable();
  });

  function loadCustomerTable() {
    if ($.fn.DataTable.isDataTable("#customerSelectTable")) {
      $("#customerSelectTable").DataTable().destroy();
    }

    $("#customerSelectTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/equipment-rent-master.php",
        type: "POST",
        data: function (d) {
          d.filter_customers = true;
        },
        dataSrc: function (json) {
          return json.data;
        },
      },
      columns: [
        { data: "id", title: "#ID" },
        { data: "code", title: "Code" },
        {
          data: "name",
          title: "Name",
          render: function (data, type, row) {
            if (row.is_blacklisted == 1) {
              return data + ' <span class="badge bg-danger">Blocked</span>';
            }
            return data;
          }
        },
        { data: "mobile_number", title: "Mobile" },
        { data: "nic", title: "NIC" },
        { data: "address", title: "Address" },
        { data: "outstanding", title: "Outstanding" },
      ],
      order: [[2, "asc"]],
      pageLength: 50,
    });

    $("#customerSelectTable tbody")
      .off("click")
      .on("click", "tr", function () {
        var data = $("#customerSelectTable").DataTable().row(this).data();
        if (data) {
          if (data.is_blacklisted == 1) {
            swal({
              title: "Blocked!",
              text: "This customer is blacklisted and cannot be selected.",
              type: "error",
              timer: 3000,
              showConfirmButton: false
            });
            return;
          }
          $("#customer_id").val(data.id);
          $("#customer_display").val(data.code + " - " + data.name);
          $("#CustomerSelectModal").modal("hide");
        }
      });
  }

  // Load Equipment Table
  $("#EquipmentSelectModal").on("shown.bs.modal", function () {
    loadEquipmentTable();
  });

  function loadEquipmentTable() {
    if ($.fn.DataTable.isDataTable("#equipmentSelectTable")) {
      $("#equipmentSelectTable").DataTable().destroy();
    }

    var equipmentTable = $("#equipmentSelectTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/equipment-rent-master.php",
        type: "POST",
        data: function (d) {
          d.filter_equipment = true;
        },
        dataSrc: function (json) {
          return json.data;
        },
      },
      columns: [
        {
          data: null,
          title: "",
          className: "details-control",
          orderable: false,
          defaultContent:
            '<span class="mdi mdi-plus-circle-outline text-primary" style="font-size:18px; cursor:pointer;"></span>',
          width: "30px",
        },
        { data: "key", title: "#" },
        {
          data: "image_name",
          title: "Image",
          orderable: false,
          render: function (data, type, row) {
            var imgSrc = data ? "uploads/equipment/" + data : "assets/images/no-image.png";
            return `<img src="${imgSrc}" alt="Img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">`;
          },
        },
        { data: "code", title: "Code" },
        {
          data: "item_name",
          title: "Item Name",
          render: function (data, type, row) {
            return `
              <div class="fw-bold">${data}</div>
              <div class="text-muted small">
                <span class="fw-bold text-primary">SN -</span> <span class="text-danger">${row.serial_number || "-"}</span> |
                <span class="fw-bold text-primary">Deposit -</span> <span class="text-danger">Rs. ${row.deposit_one_day || "0.00"}</span> |
                <span class="fw-bold text-primary">Size -</span> <span class="text-danger">${row.size || "-"}</span> |
                <span class="fw-bold text-primary">Category -</span> <span class="text-danger">${row.category_label || "-"}</span>
              </div>
            `;
          },
        },
      ],
      order: [[3, "asc"]],
      pageLength: 50,
    });

    // Function to format expandable row details
    function formatEquipmentDetails(row) {
      var d = row.data();
      var availabilityBadge = '';
      if (d.no_sub_items == 1) {
        var availableStock = (parseFloat(d.total_quantity) || 0) - (parseFloat(d.rented_qty) || 0);
        if (availableStock > 0) {
          availabilityBadge = '<span class="badge bg-success">' + availableStock + ' Available</span>';
        } else {
          availabilityBadge = '<span class="badge bg-danger">Not Available</span>';
        }
      } else {
        if (d.available_sub > 0) {
          availabilityBadge = '<span class="badge bg-success">' + d.available_sub + '/' + d.total_sub + ' Available</span>';
        } else {
          availabilityBadge = '<span class="badge bg-danger">All Rented</span>';
        }
      }

      return `
        <div class="p-3 bg-light">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-sm table-borderless mb-0">
                <tr><td class="fw-bold" style="width: 140px;">Serial Number:</td><td>${d.serial_number || '-'}</td></tr>
                <tr><td class="fw-bold">Size:</td><td>${d.size || '-'}</td></tr>
                <tr><td class="fw-bold">Category:</td><td>${d.category_label || '-'}</td></tr>
                <tr><td class="fw-bold">Availability:</td><td>${availabilityBadge}</td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-sm table-borderless mb-0">
                <tr><td class="fw-bold" style="width: 140px;">Rent (1 Day):</td><td>Rs. ${parseFloat(d.rent_one_day || 0).toFixed(2)}</td></tr>
                <tr><td class="fw-bold">Rent (1 Month):</td><td>Rs. ${parseFloat(d.rent_one_month || 0).toFixed(2)}</td></tr>
                <tr><td class="fw-bold">Deposit:</td><td>Rs. ${parseFloat(d.deposit_one_day || 0).toFixed(2)}</td></tr>
                <tr><td class="fw-bold">Value:</td><td>Rs. ${parseFloat(d.value || 0).toFixed(2)}</td></tr>
              </table>
            </div>
          </div>
        </div>
      `;
    }

    // Handle expand/collapse on + icon click
    $("#equipmentSelectTable tbody").off("click", "td.details-control").on("click", "td.details-control", function (e) {
      e.stopPropagation(); // Prevent row click from firing
      var tr = $(this).closest("tr");
      var row = equipmentTable.row(tr);
      var icon = tr.find("td.details-control span.mdi");

      if (row.child.isShown()) {
        // Close
        row.child.hide();
        tr.removeClass("shown");
        icon.removeClass("mdi-minus-circle-outline").addClass("mdi-plus-circle-outline");
      } else {
        // Open
        row.child(formatEquipmentDetails(row)).show();
        tr.addClass("shown");
        icon.removeClass("mdi-plus-circle-outline").addClass("mdi-minus-circle-outline");
      }
    });

    // Handle row click to select equipment (exclude details-control column)
    $("#equipmentSelectTable tbody")
      .off("click", "tr")
      .on("click", "tr", function (e) {
        // Skip if clicked on expand/collapse icon
        if ($(e.target).closest("td.details-control").length) {
          return;
        }

        var data = equipmentTable.row(this).data();
        if (data) {
          // Check availability
          var isAvailable = false;
          if (data.no_sub_items == 1) {
            var availableStock = (parseFloat(data.total_quantity) || 0) - (parseFloat(data.rented_qty) || 0);
            if (availableStock > 0) isAvailable = true;
          } else {
            if (data.available_sub > 0) isAvailable = true;
          }

          if (!isAvailable) {
            swal({
              title: "Not Available!",
              text: "All units of this equipment are currently rented out.",
              type: "warning",
              showConfirmButton: true,
            });
            return;
          }
          $("#item_equipment_id").val(data.id);
          $("#item_equipment_display").val(data.code + " - " + data.item_name);

          // Set rates
          currentRentOneDay = parseFloat(data.rent_one_day) || 0;
          currentRentOneMonth = parseFloat(data.rent_one_month) || 0;
          currentDepositOneDay = parseFloat(data.deposit_one_day) || 0;

          // Reset calculations
          $("#item_duration").val("");
          $("#item_amount").val("");

          // Clear sub equipment when equipment changes
          $("#item_sub_equipment_id").val("");
          $("#item_sub_equipment_display").val("");

          // Handle No Sub-Items logic
          if (data.no_sub_items == 1) {
            $("#item_qty").prop("readonly", false); // Enable Qty
            $("#item_sub_equipment_display").prop("disabled", true).attr("placeholder", "Not Required");
            $("#btn-select-sub-equipment").prop("disabled", true);
            $("#returned_qty_container").show(); // Show returned qty field
            // Store flag
            $("#item_equipment_id").data("no_sub_items", 1);
          } else {
            $("#item_qty").prop("readonly", true).val(1); // Disable Qty and reset to 1
            $("#item_sub_equipment_display").prop("disabled", false).attr("placeholder", "Select sub equipment");
            $("#btn-select-sub-equipment").prop("disabled", false);
            $("#returned_qty_container").hide(); // Hide returned qty field
            $("#item_returned_qty").val(0);
            $("#item_equipment_id").data("no_sub_items", 0);
          }

          $("#EquipmentSelectModal").modal("hide");
        }
      });
  }

  // Load Sub Equipment Table
  $("#SubEquipmentSelectModal").on("shown.bs.modal", function () {
    loadSubEquipmentTable();
  });

  function loadSubEquipmentTable() {
    var equipmentId = $("#item_equipment_id").val();

    if (!equipmentId) {
      $("#noSubEquipmentMsg").show();
      if ($.fn.DataTable.isDataTable("#subEquipmentSelectTable")) {
        $("#subEquipmentSelectTable").DataTable().destroy();
      }
      $("#subEquipmentSelectTable").hide();
      return;
    }

    $("#noSubEquipmentMsg").hide();
    $("#subEquipmentSelectTable").show();

    if ($.fn.DataTable.isDataTable("#subEquipmentSelectTable")) {
      $("#subEquipmentSelectTable").DataTable().destroy();
    }

    $("#subEquipmentSelectTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/equipment-rent-master.php",
        type: "POST",
        data: function (d) {
          d.filter_sub_equipment = true;
          d.equipment_id = equipmentId;
        },
        dataSrc: function (json) {
          if (json.data.length === 0) {
            $("#noSubEquipmentMsg").show();
          }
          return json.data;
        },
      },
      columns: [
        { data: "key", title: "#" },
        { data: "code", title: "Code" },
        { data: "status_label", title: "Status" },
      ],
      order: [[1, "asc"]],
      pageLength: 50,
    });

    $("#subEquipmentSelectTable tbody")
      .off("click")
      .on("click", "tr", function () {
        var data = $("#subEquipmentSelectTable").DataTable().row(this).data();
        if (data) {
          if (isSubEquipmentAlreadyAdded(data.id)) {
            swal({
              title: "Already Added!",
              text: "This unit is already in your rental list.",
              type: "warning",
              showConfirmButton: true,
            });
            return;
          }
          $("#item_sub_equipment_id").val(data.id);
          $("#item_sub_equipment_display").val(data.code);
          $("#SubEquipmentSelectModal").modal("hide");
        }
      });
  }

  // Create Equipment Rent
  $("#create").click(function (event) {
    event.preventDefault();
    $("#create").prop("disabled", true);

    if (!$("#code").val()) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please enter equipment rent code",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }
    if (!$("#customer_id").val()) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please select a customer",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }
    if (!$("#rental_date").val()) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please enter rental date",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }
    if (rentItems.length === 0) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please add at least one equipment item",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }

    $("#page-preloader").show();

    var formData = new FormData($("#form-data")[0]);
    formData.append("create", true);
    formData.append("items", JSON.stringify(rentItems));
    formData.append("transport_cost", $("#transport_cost").val() || 0);
    formData.append("custom_deposit", $("#custom_deposit").val() || 0);

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "JSON",
      success: function (result) {
        $("#page-preloader").hide();
        $("#create").prop("disabled", false);

        if (result.status === "success") {
          // Open the rent invoice in a new tab
          var billNo = result.bill_number || $("#code").val();

          // Update the UI with the actual bill number used
          $("#code").val(billNo);

          window.open(
            "rent-invoice.php?bill_no=" + encodeURIComponent(billNo),
            "_blank",
          );

          swal({
            title: "Success!",
            text: "Equipment rent created successfully! Invoice opened in new tab.",
            type: "success",
            timer: 2000,
            showConfirmButton: false,
          });
          setTimeout(function () {
            window.location.reload();
          }, 2000);
        } else {
          swal({
            title: "Error!",
            text: result.message || "Something went wrong.",
            type: "error",
            showConfirmButton: true,
          });
        }
      },
      error: function (xhr) {
        $("#page-preloader").hide();
        $("#create").prop("disabled", false);
        console.error("Error:", xhr.responseText);
        swal({
          title: "Error!",
          text: "Failed to create equipment rent.",
          type: "error",
          showConfirmButton: true,
        });
      },
    });
    return false;
  });

  // Update Equipment Rent
  $("#update").click(function (event) {
    event.preventDefault();
    $("#update").prop("disabled", true);

    if (!$("#customer_id").val()) {
      $("#update").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please select a customer",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }
    if (rentItems.length === 0) {
      $("#update").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please add at least one equipment item",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }

    $("#page-preloader").show();

    var formData = new FormData($("#form-data")[0]);
    formData.append("update", true);
    formData.append("items", JSON.stringify(rentItems));
    formData.append("transport_cost", $("#transport_cost").val() || 0);
    formData.append("custom_deposit", $("#custom_deposit").val() || 0);

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "JSON",
      success: function (result) {
        $("#page-preloader").hide();
        $("#update").prop("disabled", false);

        if (result.status === "success") {
          swal({
            title: "Success!",
            text: "Equipment rent updated successfully!",
            type: "success",
            timer: 2000,
            showConfirmButton: false,
          });
          setTimeout(function () {
            window.location.reload();
          }, 2000);
        } else {
          swal({
            title: "Error!",
            text: result.message || "Something went wrong.",
            type: "error",
            showConfirmButton: true,
          });
        }
      },
      error: function (xhr) {
        $("#page-preloader").hide();
        $("#update").prop("disabled", false);
        console.error("Error:", xhr.responseText);
        swal({
          title: "Error!",
          text: "Failed to update equipment rent.",
          type: "error",
          showConfirmButton: true,
        });
      },
    });
    return false;
  });

  // Reset form - New button
  $("#new").click(function (e) {
    e.preventDefault();
    $("#form-data")[0].reset();
    $("#rent_id").val("");
    $("#customer_id").val("");
    $("#customer_display").val("");
    $("#payment_type_id").val("");
    $("#item_equipment_id").val("");
    $("#item_equipment_display").val("");
    $("#item_sub_equipment_id").val("");
    $("#item_sub_equipment_display").val("");
    $("#item_sub_equipment_display").val("");
    $("#transport_cost").val("");
    $("#custom_deposit").val("");
    $("#transport_cost, #custom_deposit").prop("readonly", false); // allow manual input for new rent
    $("#calculated_deposit_display").text("0.00");
    totalCalculatedDeposit = 0;
    rentItems = [];
    updateItemsTable();
    $("#create").show();
    $("#update").hide();
    $("#print").hide();
    $("#return-all").hide();
    $("#received_date_container").hide();

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      data: { action: "get_new_code" },
      dataType: "JSON",
      success: function (result) {
        if (result.status === "success") {
          $("#code").val(result.code);
        }
      },
    });
  });

  // Return All Items
  $("#return-all").click(function (e) {
    e.preventDefault();
    var rentId = $("#rent_id").val();
    if (!rentId) return;

    swal(
      {
        title: "Return All Items?",
        text: "This will mark all items as returned.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, return all!",
      },
      function (isConfirm) {
        if (isConfirm) {
          $.ajax({
            url: "ajax/php/equipment-rent-master.php",
            type: "POST",
            data: { action: "return_all", rent_id: rentId },
            dataType: "JSON",
            success: function (result) {
              if (result.status === "success") {
                swal({
                  title: "Success!",
                  text: result.message || "All items marked as returned.",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false,
                });
                setTimeout(function () {
                  window.location.reload();
                }, 2000);
              } else {
                swal({
                  title: "Error!",
                  text: result.message || "Failed to return all items.",
                  type: "error",
                  showConfirmButton: true,
                });
              }
            },
            error: function () {
              swal({
                title: "Error!",
                text: "Unable to process return all request.",
                type: "error",
                showConfirmButton: true,
              });
            },
          });
        }
      },
    );
  });

  // Delete Equipment Rent
  $(document).on("click", ".delete-equipment-rent", function (e) {
    e.preventDefault();
    $(".delete-equipment-rent").prop("disabled", true);

    var rentId = $("#rent_id").val();
    var rentCode = $("#code").val();

    if (!rentId) {
      $(".delete-equipment-rent").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please select an equipment rent record first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    swal(
      {
        title: "Are you sure?",
        text:
          "Do you want to delete equipment rent '" +
          rentCode +
          "'? This will release all rented equipment.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
      },
      function (isConfirm) {
        if (isConfirm) {
          $("#page-preloader").show();
          $.ajax({
            url: "ajax/php/equipment-rent-master.php",
            type: "POST",
            data: { id: rentId, delete: true },
            dataType: "JSON",
            success: function (response) {
              console.log("Delete response:", response);
              $("#page-preloader").hide();
              $(".delete-equipment-rent").prop("disabled", false);

              if (response && response.status === "success") {
                swal({
                  title: "Deleted!",
                  text: response.message || "Equipment rent has been deleted.",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false,
                });
                setTimeout(function () {
                  window.location.reload();
                }, 2000);
              } else {
                swal({
                  title: "Error!",
                  text: (response && response.message) || "Something went wrong.",
                  type: "error",
                  showConfirmButton: true,
                });
              }
            },
            error: function (xhr, status, error) {
              console.error("Delete error:", xhr.responseText);
              $("#page-preloader").hide();
              $(".delete-equipment-rent").prop("disabled", false);
              swal({
                title: "Error!",
                text: "Unable to delete equipment rent. Check console for details.",
                type: "error",
                showConfirmButton: true,
              });
            },
          });
        } else {
          $(".delete-equipment-rent").prop("disabled", false);
        }
      },
    );
  });

  // Sync item rental date with master rental date
  $("#rental_date").on("change", function () {
    $("#item_rental_date").val($(this).val());
  });

  // Initialize button visibility on page load
  $("#update").hide();
  $("#return-all").hide();
  $("#print").hide();
});
