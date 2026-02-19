jQuery(document).ready(function () {
  // Store rental items in memory
  var rentItems = [];
  var currentRentOneDay = 0;
  var currentRentOneMonth = 0;
  var currentDepositOneDay = 0;
  var currentAllowManualAmount = false;
  var totalCalculatedDeposit = 0;
  var currentEquipmentId = null;
  var currentIsFixedRate = false;
  var manualAmountEditEnabled = false;
  var isEditingExistingRent = false;
  var rentalStartDateUserOverride = false;

  // Calculate amount and return date
  function calculateRentDetails() {
    var rentType = $("#item_rent_type").val();
    var duration = parseFloat($("#item_duration").val()) || 0;
    var qty = parseFloat($("#item_qty").val()) || 1;
    var rentalDate = $("#rental_start_date").val();

    if (!rentalDate || duration <= 0) {
      $("#item_amount").val(formatAmount(0));
      return;
    }

    var manualEnabled = !$("#item_amount").prop("readonly");
    if (manualEnabled && $("#item_amount").data("manual-edited")) {
      return;
    }

    var amount = 0;
    var returnDate = new Date(rentalDate);

    // For fixed-rate items, use flat rate (no duration multiplication)
    if (currentIsFixedRate) {
      if (rentType === "day") {
        amount = currentRentOneDay * qty;
        returnDate.setDate(returnDate.getDate() + 1);
        $("#duration_label").text("Days");
      } else {
        amount = currentRentOneMonth * qty;
        returnDate.setMonth(returnDate.getMonth() + 1);
        $("#duration_label").text("Months");
      }
    } else {
      // Normal items: multiply by duration
      if (rentType === "day") {
        amount = currentRentOneDay * qty;
        returnDate.setDate(returnDate.getDate() + duration);
        $("#duration_label").text("Days");
      } else {
        amount = currentRentOneMonth * qty;
        returnDate.setMonth(returnDate.getMonth() + duration);
        $("#duration_label").text("Months");
      }
    }

    // Format amount
    $("#item_amount").data("manual-edited", false).val(formatAmount(amount));

    // Format return date YYYY-MM-DD
    var yyyy = returnDate.getFullYear();
    var mm = String(returnDate.getMonth() + 1).padStart(2, "0");
    var dd = String(returnDate.getDate()).padStart(2, "0");
    $("#item_return_date").val(yyyy + "-" + mm + "-" + dd);
  }

  // Calculate duration from return date
  function calculateDurationFromDates() {
    var rentType = $("#item_rent_type").val();
    var rentalDateStr = $("#rental_start_date").val();
    var returnDateStr = $("#item_return_date").val();

    if (!rentalDateStr || !returnDateStr) return;

    var rentalDate = new Date(rentalDateStr);
    var returnDate = new Date(returnDateStr);
    var duration = 0;
    var timeDiff = returnDate - rentalDate;

    if (timeDiff <= 0) {
      $("#item_duration").val(0);
      $("#item_amount").val(formatAmount(0));
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

    // Recalculate Amount only if not manually edited
    var manualEnabled = !$("#item_amount").prop("readonly");
    if (!(manualEnabled && $("#item_amount").data("manual-edited"))) {
      var amount = 0;
      var qty = parseFloat($("#item_qty").val()) || 1;

      // For fixed-rate items, always use flat rate
      if (currentIsFixedRate) {
        amount =
          rentType === "day"
            ? currentRentOneDay * qty
            : currentRentOneMonth * qty;
      } else {
        // Normal items: use stored rate (already calculated per duration in the system)
        amount =
          rentType === "day"
            ? currentRentOneDay * qty
            : currentRentOneMonth * qty;
      }
      $("#item_amount").data("manual-edited", false).val(formatAmount(amount));
    }
  }

  function getDateOnly(dateString) {
    if (!dateString) {
      return null;
    }
    return String(dateString).split(" ")[0];
  }

  function getEarliestItemRentalDate() {
    var earliest = null;
    rentItems.forEach(function (item) {
      if (!item || !item.rental_date) {
        return;
      }
      var datePortion = getDateOnly(item.rental_date);
      if (!datePortion) {
        return;
      }
      if (!earliest || datePortion < earliest) {
        earliest = datePortion;
      }
    });
    return earliest;
  }

  function syncRentalStartDateFieldFromItems(forceSync) {
    if (!isEditingExistingRent) {
      return;
    }
    if (rentalStartDateUserOverride && !forceSync) {
      return;
    }
    var earliest = getEarliestItemRentalDate();
    if (earliest) {
      $("#rental_start_date").val(earliest);
    }
  }

  function applyRentalDateDefaults(forceAll) {
    var fallbackDate = $("#rental_start_date").val();
    if (!fallbackDate) {
      return;
    }
    rentItems.forEach(function (item) {
      if (!item) {
        return;
      }
      if (forceAll || !item.rental_date) {
        item.rental_date = fallbackDate;
      }
    });
  }

  function isValidDateString(dateStr) {
    if (!dateStr) {
      return false;
    }
    // Enforce YYYY-MM-DD
    var match = /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/.exec(dateStr);
    if (!match) {
      return false;
    }
    var year = parseInt(match[1], 10);
    var month = parseInt(match[2], 10) - 1; // JS months are 0-based
    var day = parseInt(match[3], 10);
    var d = new Date(year, month, day);
    // Validate reconstructed date matches input (handles invalid dates like 2024-02-30)
    return (
      d.getFullYear() === year &&
      d.getMonth() === month &&
      d.getDate() === day
    );
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
        var statusBadge = '<span class="badge bg-soft-warning">Rented</span>';
        if (item.status === "returned") {
          statusBadge = '<span class="badge bg-soft-info">Returned</span>';
        } else if (item.status === "cancelled") {
          statusBadge = '<span class="badge bg-danger">Cancelled</span>';
        }

        var actionBtns = "";
        if (
          item.status === "rented" ||
          (item.pending_qty && item.pending_qty > 0)
        ) {
          actionBtns =
            '<button class="btn btn-sm btn-success process-return-btn me-1" data-item-id="' +
            (item.id || "") +
            '" data-index="' +
            index +
            '" title="Process Return"><i class="uil uil-redo"></i></button>';
        }
        if (
          item.id &&
          (item.total_returned_qty > 0 || item.status === "returned")
        ) {
          actionBtns +=
            '<button class="btn btn-sm btn-info view-returns-btn me-1" data-item-id="' +
            item.id +
            '" title="View Returns History"><i class="uil uil-history"></i></button>';
          actionBtns +=
            '<button class="btn btn-sm btn-outline-danger cancel-item-return-btn me-1" data-item-id="' +
            item.id +
            '" data-index="' +
            index +
            '" title="Cancel Return"><i class="uil uil-times-circle"></i></button>';
        }
        // Allow deletion for brand-new items or saved items that have no returns yet
        var canRemoveSavedItem =
          !!item.id &&
          parseFloat(item.total_returned_qty || 0) === 0 &&
          item.status !== "returned" &&
          item.status !== "cancelled";
        if (!item.id || canRemoveSavedItem) {
          actionBtns +=
            '<button class="btn btn-sm btn-danger remove-item-btn" data-index="' +
            index +
            '" title="Remove"><i class="uil uil-trash"></i></button>';
        }

        var usedDays = parseInt(item.latest_used_days, 10);
        var hasReturn = !!item.latest_return_date;
        var returnDateTime = hasReturn
          ? item.latest_return_time
            ? item.latest_return_date + " " + item.latest_return_time
            : item.latest_return_date
          : null;

        // Calculate day count display similar to return modal
        var dayCountDisplay = "";
        if (hasReturn && usedDays) {
          // Check if extra day was actually charged (based on the after_9am_extra_day flag from the return record)
          var hasExtraDay = !!(
            item.latest_after_9am_flag &&
            parseInt(item.latest_after_9am_flag, 10) === 1
          );

          dayCountDisplay =
            '<br><small class="text-danger">' +
            usedDays +
            " day" +
            (usedDays > 1 ? "s" : "");
          if (hasExtraDay) {
            dayCountDisplay += " +1 Extra day";
          }
          dayCountDisplay += "</small>";
        }

        var returnDisplay = hasReturn
          ? '<div class="text-danger fw-semibold">' +
          returnDateTime +
          dayCountDisplay +
          "</div>"
          : "-";

        var rowClass =
          item.status === "cancelled" ? ' class="table-danger"' : "";
        var row =
          "<tr" +
          rowClass +
          ">" +
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
          (item.department_name || "-") +
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
          '<span class="badge bg-soft-success">' +
          (item.total_returned_qty || 0) +
          "</span> / " +
          '<span class="badge bg-soft-warning">' +
          (item.pending_qty !== undefined ? item.pending_qty : item.quantity) +
          " pending</span>" +
          "</td>" +
          "<td>" +
          parseFloat(item.amount).toFixed(2) +
          "</td>" +
          "<td>" +
          (
            parseFloat(item.deposit_one_day || 0) *
            parseFloat(item.quantity || 1)
          ).toFixed(2) +
          "</td>" +
          "<td>" +
          returnDisplay +
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
    $(".process-return-btn")
      .off("click")
      .on("click", function () {
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
            showConfirmButton: false,
          });
        }
      });

    // Bind view returns history button event
    $(".view-returns-btn")
      .off("click")
      .on("click", function () {
        var itemId = $(this).data("item-id");
        viewReturnsHistory(itemId);
      });

    // Bind cancel item return button event
    $(".cancel-item-return-btn")
      .off("click")
      .on("click", function () {
        var itemId = $(this).data("item-id");
        var index = $(this).data("index");
        var item = rentItems[index];
        var itemLabel = item
          ? item.equipment_display +
          (item.sub_equipment_display
            ? " (" + item.sub_equipment_display + ")"
            : "")
          : "this item";

        swal(
          {
            title: "Cancel Item Return?",
            text:
              "This will cancel all returns for '" +
              itemLabel +
              "' and set it back to rented status. Are you sure?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, cancel return!",
            cancelButtonText: "No, keep it",
          },
          function (isConfirm) {
            if (isConfirm) {
              $("#page-preloader").show();
              $.ajax({
                url: "ajax/php/equipment-rent-master.php",
                type: "POST",
                data: {
                  action: "cancel_item_return",
                  rent_item_id: itemId,
                },
                dataType: "JSON",
                success: function (result) {
                  $("#page-preloader").hide();
                  if (result.status === "success") {
                    swal({
                      title: "Success!",
                      text: result.message || "Item return cancelled.",
                      type: "success",
                      timer: 2000,
                      showConfirmButton: false,
                    });
                    setTimeout(function () {
                      var currentRentId = $("#rent_id").val();
                      if (currentRentId) {
                        loadRentDetails(currentRentId);
                      } else {
                        window.location.reload();
                      }
                    }, 2000);
                  } else {
                    swal({
                      title: "Error!",
                      text: result.message || "Failed to cancel item return.",
                      type: "error",
                      showConfirmButton: true,
                    });
                  }
                },
                error: function (xhr) {
                  $("#page-preloader").hide();
                  console.error("Cancel item return error:", xhr.responseText);
                  swal({
                    title: "Error!",
                    text: "Unable to cancel item return.",
                    type: "error",
                    showConfirmButton: true,
                  });
                },
              });
            }
          },
        );
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
    var rentalDate = $("#rental_start_date").val();
    var returnDate = $("#item_return_date").val();
    var durationVal = parseFloat($("#item_duration").val()) || 0;

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
    var $deptSelect = $("#item_department_id");
    var deptId = $deptSelect.val();
    var availableQty =
      parseFloat($deptSelect.find("option:selected").data("available")) || 0;

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

    // Department selection is required when departments are loaded
    if (!deptId) {
      swal({
        title: "Error!",
        text: "Please select a department",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    // Validate requested quantity against selected department availability
    if (qty > availableQty) {
      swal({
        title: "Quantity Exceeds Availability",
        text:
          "You requested " +
          qty +
          " but only " +
          availableQty +
          " available in this department.",
        type: "error",
        timer: 2500,
        showConfirmButton: false,
      });
      return;
    }

    if (!returnDate) {
      swal({
        title: "Error!",
        text: "Please select a return date",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    if (durationVal <= 0) {
      swal({
        title: "Error!",
        text: "Please enter a valid duration",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    if (qty <= 0) {
      swal({
        title: "Error!",
        text: "Quantity must be at least 1",
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
      is_fixed_rate: currentIsFixedRate ? 1 : 0,
      department_id: $("#item_department_id").val() || null,
      department_name:
        $("#item_department_id option:selected").text().split(" (Avail")[0] ||
        "",
      status: "rented",
      remark: "",
      no_sub_items: noSubItems ? 1 : 0,
    });

    // Update calculated deposit
    totalCalculatedDeposit +=
      parseFloat(currentDepositOneDay || 0) *
      parseFloat($("#item_qty").val() || 1);
    $("#calculated_deposit_display").text(formatAmount(totalCalculatedDeposit));
    if (!isEditingExistingRent) {
      $("#custom_deposit").val(formatAmount(totalCalculatedDeposit));
    }

    // Auto-fill custom deposit if it's empty or matches previous calculation
    // var currentCustom = parseFloat($("#custom_deposit").val()) || 0;
    // if (currentCustom === 0 || currentCustom === (totalCalculatedDeposit - parseFloat(currentDepositOneDay || 0))) {
    //     $("#custom_deposit").val(totalCalculatedDeposit.toFixed(2));
    // }

    updateItemsTable();

    // Reset amount edit UI back to default after adding an item
    resetAmountEditUI(true);

    // Clear item inputs
    $("#item_sub_equipment_id").val("");
    $("#item_sub_equipment_display").val("");
    $("#item_return_date").val("");
    $("#item_duration").val("");
    $("#item_qty").val(1);
    $("#item_returned_qty").val(0);
    $("#item_amount").val("");
    // Re-select the first department (keep dropdown enabled)
    var $deptSelect = $("#item_department_id");
    if ($deptSelect.find("option").length > 1) {
      $deptSelect.val($deptSelect.find("option:not([value=''])").first().val());
    }
  });

  // Track manual edits on amount when allowed
  $("#item_amount").on("input", function () {
    if (!$(this).prop("readonly")) {
      $(this).data("manual-edited", true);
    }
  });

  // Helper: bind the red + button to enable manual amount editing
  function bindEnableAmountEditButton() {
    $("#btn-enable-amount-edit")
      .off("click")
      .on("click", function () {
        var equipmentId = $("#item_equipment_id").val();
        if (!equipmentId) {
          swal({
            title: "Error!",
            text: "Please select an equipment first",
            type: "error",
            timer: 2000,
            showConfirmButton: false,
          });
          return;
        }

        // Remember original amount to allow cancel
        $("#item_amount").data("original-amount", $("#item_amount").val());

        // Enable manual editing
        $("#item_amount").prop("readonly", false).removeAttr("readonly").focus();
        manualAmountEditEnabled = true;
        $("#item_amount").data("manual-edited", true);

        // Change button to save icon
        $(this)
          .html('<i class="uil uil-save"></i>')
          .removeClass("btn-danger")
          .addClass("btn-success");
        $(this).attr("title", "Save amount to equipment");
        $(this).attr("id", "btn-save-amount-edit");

        // Rebind the click event for save
        $("#btn-save-amount-edit")
          .off("click")
          .on("click", function () {
            saveEquipmentAmount();
          });
      });
  }

  // Helper: revert the save (green) button back to red without saving
  function resetAmountEditUI(restoreOriginal) {
    var $amount = $("#item_amount");
    if (restoreOriginal) {
      var originalAmount = $amount.data("original-amount");
      if (typeof originalAmount !== "undefined") {
        $amount.val(originalAmount);
      }
    }
    $amount
      .prop("readonly", true)
      .attr("readonly", "readonly")
      .data("manual-edited", false)
      .removeData("original-amount");

    manualAmountEditEnabled = false;

    var $btn = $("#btn-save-amount-edit").length
      ? $("#btn-save-amount-edit")
      : $("#btn-enable-amount-edit");

    $btn
      .html('<i class="uil uil-plus"></i>')
      .removeClass("btn-success")
      .addClass("btn-danger")
      .attr("title", "Enable manual amount editing")
      .attr("id", "btn-enable-amount-edit");

    bindEnableAmountEditButton();
  }

  // Function to save changed amount to equipment table
  function saveEquipmentAmount() {
    var equipmentId = $("#item_equipment_id").val();
    var rentType = $("#item_rent_type").val();
    var newAmount = parseFloat($("#item_amount").val()) || 0;

    if (!equipmentId || newAmount <= 0) {
      swal({
        title: "Error!",
        text: "Please enter a valid amount",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    swal(
      {
        title: "Save Amount?",
        text:
          "This will update the " +
          (rentType === "day" ? "daily" : "monthly") +
          " rent amount for this equipment. Continue?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, save it!",
        cancelButtonText: "No, cancel",
      },
      function (isConfirm) {
        if (isConfirm) {
          $.ajax({
            url: "ajax/php/equipment-rent-master.php",
            type: "POST",
            data: {
              action: "update_equipment_amount",
              equipment_id: equipmentId,
              rent_type: rentType,
              amount: newAmount,
            },
            dataType: "JSON",
            success: function (result) {
              if (result.status === "success") {
                // Update the current rent values
                if (rentType === "day") {
                  currentRentOneDay = newAmount;
                } else {
                  currentRentOneMonth = newAmount;
                }

                swal({
                  title: "Success!",
                  text: "Equipment amount updated successfully",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false,
                });

                // Reset UI after save
                resetAmountEditUI(false);
              } else {
                swal({
                  title: "Error!",
                  text: result.message || "Failed to update equipment amount",
                  type: "error",
                  timer: 3000,
                  showConfirmButton: false,
                });
              }
            },
            error: function () {
              swal({
                title: "Error!",
                text: "Failed to update equipment amount",
                type: "error",
                timer: 3000,
                showConfirmButton: false,
              });
            },
          });
        } else {
          // Cancelled - revert button and amount
          resetAmountEditUI(true);
        }
      },
    );

    if (isEditingExistingRent) {
      syncRentalStartDateFieldFromItems(false);
    }
  }

  // Calculate on input changes
  $("#item_rent_type, #item_duration, #item_qty, #rental_start_date").on(
    "change keyup",
    function () {
      calculateRentDetails();
    },
  );

  // Calculate when return date changes
  $("#item_return_date").on("change keyup", function () {
    calculateDurationFromDates();
  });

  $("#rental_start_date").on("change", function () {
    var newDate = $(this).val();
    rentalStartDateUserOverride = true;
    if (!newDate) {
      return;
    }
    rentItems.forEach(function (item) {
      if (!isEditingExistingRent || !item.id) {
        item.rental_date = newDate;
      }
    });
  });

  // Initial bind for amount edit toggle
  bindEnableAmountEditButton();

  // Remove item from list
  $(document).on("click", ".remove-item-btn", function () {
    var index = $(this).data("index");

    // Subtract deposit
    var removedItem = rentItems[index];
    totalCalculatedDeposit -=
      parseFloat(removedItem.deposit_one_day || 0) *
      parseFloat(removedItem.quantity || 1);
    if (totalCalculatedDeposit < 0) totalCalculatedDeposit = 0;
    $("#calculated_deposit_display").text(formatAmount(totalCalculatedDeposit));
    if (!isEditingExistingRent) {
      $("#custom_deposit").val(formatAmount(totalCalculatedDeposit));
    }

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

  function formatMoneyLive(raw) {
    if (raw === null || raw === undefined) return "";
    var str = String(raw);
    // Preserve presence of decimal point to allow typing
    var hasDot = str.indexOf(".") !== -1;
    var sign = "";
    if (str[0] === "-") {
      sign = "-";
      str = str.slice(1);
    }
    // Remove commas and non-digits except dot
    str = str.replace(/,/g, "");
    var parts = str.split(".");
    var intPart = (parts[0] || "0").replace(/\D/g, "");
    var decPart = parts[1] ? parts[1].replace(/\D/g, "") : "";
    // Limit decimals to 2 while typing
    if (decPart.length > 2) decPart = decPart.slice(0, 2);
    // Trim leading zeros but keep single zero
    intPart = intPart.replace(/^0+(?=\d)/, "");
    if (!intPart) intPart = "0";
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    if (hasDot) return sign + intPart + "." + decPart;
    return sign + intPart;
  }

  // Live format while typing; finalize on blur
  $("#transport_cost, #custom_deposit").on("input", function () {
    var formatted = formatMoneyLive($(this).val());
    $(this).val(formatted);
  });

  $("#transport_cost, #custom_deposit").on("blur", function () {
    var val = parseAmount($(this).val());
    $(this).val(formatAmount(val));
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

  // Load Equipment Rent list when modal opens (limit 5, search by bill/customer)
  var rentListSearchTimer = null;

  $("#EquipmentRentModal").on("shown.bs.modal", function () {
    loadEquipmentRentList("");
  });

  $("#equipmentRentSearchInput").on("input", function () {
    var term = $(this).val();
    clearTimeout(rentListSearchTimer);
    rentListSearchTimer = setTimeout(function () {
      loadEquipmentRentList(term);
    }, 250);
  });

  function loadEquipmentRentList(searchTerm) {
    var $tbody = $("#equipmentRentTableBody");
    $tbody.html(
      '<tr><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr>',
    );

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      dataType: "json",
      data: {
        filter: true,
        length: 5,
        start: 0,
        pending_only: false,
        search: { value: searchTerm || "" },
      },
      success: function (res) {
        var rows = res && res.data ? res.data : [];
        console.log("Equipment rent list response:", rows);
        $tbody.empty();

        if (!rows.length) {
          $tbody.html(
            '<tr><td colspan="7" class="text-center text-muted py-3">No records found</td></tr>',
          );
          return;
        }

        rows.forEach(function (row) {
          console.log(
            "Processing row - Bill:",
            row.bill_number,
            "Status:",
            row.status,
            "IsCancelled:",
            row.is_cancelled,
          );
          var isCancelled = row.status === "cancelled" || row.is_cancelled == 1;
          var statusLabel =
            row.status_label ||
            (isCancelled
              ? "<span class='badge bg-danger'>Cancelled</span>"
              : row.status || "");
          var html =
            "<tr class='rent-row" +
            (isCancelled ? " table-danger" : "") +
            "' data-id='" +
            (row.id || "") +
            "' data-status='" +
            (row.status || "") +
            "'>" +
            "<td>" +
            (row.id || "") +
            "</td>" +
            "<td>" +
            (row.bill_number || "") +
            "</td>" +
            "<td>" +
            (row.customer_name || "") +
            "</td>" +
            "<td>" +
            (row.rental_date || "") +
            "</td>" +
            "<td>" +
            (row.received_date || "") +
            "</td>" +
            "<td>" +
            (row.total_items || 0) +
            "</td>" +
            "<td>" +
            statusLabel +
            "</td>" +
            "</tr>";
          $tbody.append(html);
        });

        $("#equipmentRentTable tbody .rent-row")
          .off("click")
          .on("click", function () {
            var id = $(this).data("id");
            if (id) {
              loadRentDetails(id);
              $("#EquipmentRentModal").modal("hide");
            }
          });
      },
      error: function (xhr) {
        console.error("Server Error:", xhr.responseText);
        $tbody.html(
          '<tr><td colspan="7" class="text-center text-danger py-3">Failed to load records</td></tr>',
        );
      },
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
          isEditingExistingRent = true;
          rentalStartDateUserOverride = false;
          var rent = result.rent;
          $("#rent_id").val(rent.id);
          $("#code").val(rent.bill_number);
          $("#customer_id").val(rent.customer_id);
          $("#customer_display").val(rent.customer_name);
          $("#rental_date").val(rent.rental_date);
          $("#rental_start_date").val(
            rent.rental_start_date || rent.rental_date,
          );
          var $paymentSelect = $("#payment_type_id");
          $paymentSelect.find("option[data-temp='1']").remove();

          if (rent.payment_type_id) {
            if (
              $paymentSelect.find(
                "option[value='" + rent.payment_type_id + "']",
              ).length === 0
            ) {
              // Saved payment type is not in the active list; show it as selected but hidden in dropdown
              var tempLabel = rent.payment_type_name
                ? rent.payment_type_name + " (Inactive)"
                : "(Inactive)";
              $paymentSelect.append(
                "<option data-temp='1' value='" +
                rent.payment_type_id +
                "' selected hidden>" +
                tempLabel +
                "</option>",
              );
            }
            $paymentSelect.val(rent.payment_type_id);
          } else {
            $paymentSelect.val("");
          }
          $("#received_date").val(rent.received_date || "");
          // System-controlled received date (read-only)
          $("#received_date").prop("readonly", true).addClass("bg-light");
          $("#received_date_container").show();
          $("#remark").val(rent.remark || "");
          $("#workplace_address").val(rent.workplace_address || "");
          $("#transport_cost").val(formatAmount(rent.transport_cost || 0));
          $("#custom_deposit").val(formatAmount(rent.deposit_total || 0));
          // Show manage deposits button for existing rents
          $("#btn-manage-deposits").show();
          // Render deposit payments if present
          if (result.deposit_payments) {
            renderDepositPayments(
              result.deposit_payments,
              parseFloat(rent.deposit_total || 0),
            );
          }
          var refundBalance = parseFloat(rent.refund_balance || 0);
          $("#customer_refund_balance").text(refundBalance.toFixed(2));
          var $refundBadge = $("#customer_refund_badge");
          $refundBadge.removeClass("badge bg-danger bg-success").text("");
          if (refundBalance < 0) {
            $refundBadge.addClass("badge bg-success").text("Customer Pay");
          } else if (refundBalance > 0) {
            $refundBadge.addClass("badge bg-danger").text("Refund");
          }

          // Customer Paid and Outstanding
          var totalCustomerPaid = parseFloat(rent.total_customer_paid || 0);
          $("#customer_paid_total").text(totalCustomerPaid.toFixed(2));
          var rentOutstanding = parseFloat(rent.rent_outstanding || 0);
          $("#customer_rent_outstanding").text(rentOutstanding.toFixed(2));
          if (rentOutstanding > 0) {
            $("#customer_rent_outstanding")
              .removeClass("text-success")
              .addClass("text-danger");
          } else {
            $("#customer_rent_outstanding")
              .removeClass("text-danger")
              .addClass("text-success");
          }

          // Company Refund Balance (partial refund outstanding)
          var companyOutstanding = parseFloat(
            rent.total_company_outstanding || 0,
          );
          if (companyOutstanding > 0) {
            $("#company_refund_balance_display").text(
              companyOutstanding.toFixed(2),
            );
            $("#company_refund_row").show();
          } else {
            $("#company_refund_balance_display").text("0.00");
            $("#company_refund_row").hide();
          }

          // Render return remarks list (from return-all/bulk returns)
          var remarks = result.return_remarks || [];
          var $remarksCard = $("#returnRemarksCard");
          var $remarksList = $("#returnRemarksList");
          var $remarksEmpty = $("#returnRemarksEmpty");
          var $remarksCount = $("#returnRemarksCount");

          if (remarks.length > 0) {
            $remarksList.empty();
            remarks.forEach(function (text) {
              var safeText = $("<div>").text(text).html();
              $remarksList.append("<li>" + safeText + "</li>");
            });
            $remarksCount.text(remarks.length);
            $remarksEmpty.hide();
            $remarksCard.show();
          } else {
            $remarksList.empty();
            $remarksCount.text("0");
            $remarksEmpty.show();
            $remarksCard.hide();
          }

          // Lock manual edits when loading an existing rent; re-enable for active bills
          var canEditFinancials = true;
          var statusText = String(rent.status || "")
            .toLowerCase()
            .trim();
          var isCancelled = ["cancelled", "canceled"].includes(statusText);
          var isReturned = statusText === "returned";
          if (isCancelled || isReturned) {
            canEditFinancials = false;
          }
          $("#transport_cost, #custom_deposit").prop(
            "readonly",
            !canEditFinancials,
          );

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
              // latest return info
              latest_return_date: item.latest_return_date || null,
              latest_return_time: item.latest_return_time || null,
              latest_after_9am_flag: item.latest_after_9am_flag || null,
              latest_used_days: item.latest_used_days || null,
              status: item.status,
              remark: item.remark,
              department_id: item.department_id || null,
              department_name: item.department_name || "",
              is_fixed_rate: item.is_fixed_rate == 1 ? 1 : 0,
              no_sub_items:
                item.no_sub_items == 1
                  ? 1
                  : !item.sub_equipment_id || item.sub_equipment_id == 0
                    ? 1
                    : 0,
            };
          });

          $("#calculated_deposit_display").text(
            formatAmount(totalCalculatedDeposit),
          );
          updateItemsTable();

          // Hide Return All if any item already has a returned quantity
          var hasAnyReturnedQty = rentItems.some(function (it) {
            return parseFloat(it.total_returned_qty || 0) > 0;
          });

          // Check if all items are fully returned
          var allFullyReturned = rentItems.every(function (it) {
            return it.status === "returned";
          });

          // Load cheque details
          $("#cheque_number").val(rent.cheque_number || "");
          $("#cheque_date").val(rent.cheque_date || "");
          $("#cheque_branch_id").val(rent.cheque_branch_id || "");
          $("#cheque_branch_display").val(rent.cheque_branch_display || "");

          // Load bank transfer details
          $("#transfer_branch_id").val(rent.transfer_branch_id || "");
          $("#transfer_branch_display").val(rent.transfer_branch_display || "");
          $("#bank_account_number").val(rent.bank_account_number || "");
          $("#bank_reference").val(rent.bank_reference || "");

          // Show/hide payment detail sections based on loaded payment type
          togglePaymentDetails();

          $("#create").hide();
          $("#cancel-bill-alert").remove();

          var isRented = ["rented", "rent", "active"].includes(statusText);
          var hasItems = rentItems.length > 0;
          // Show cancel-return if bill has items and is not cancelled (regardless of status text)
          var shouldShowCancelReturn = hasItems && !isCancelled;
          if (!isRented) {
            $("#update").hide();
            $("#return-all").hide();
            $("#cancel-bill").hide();
            $("#print").show();
          } else {
            $("#update").show();
            $("#return-all").toggle(!hasAnyReturnedQty);
            $("#print").show();
            $("#cancel-bill").toggle(isRented);
          }

          // Show cancel return for any non-cancelled bill that has items, even if status is 'returned'
          $("#cancel-return").toggle(shouldShowCancelReturn);
        }
      },
    });
  }

  // Make loadRentDetails globally accessible for return handler
  window.loadRentDetails = loadRentDetails;

  // Print Invoice
  $("#print").click(function (e) {
    e.preventDefault();
    var billNo = $("#code").val();
    if (billNo) {
      window.open("rent-invoice.php?bill_no=" + billNo, "_blank");
    }
  });

  // Show All Bills (returned bills only)
  $("#show-all-bills").click(function (e) {
    e.preventDefault();
    $("#ReturnedBillsModal").modal("show");
  });

  // Load Returned Bills list when modal opens (limit 5, search by bill/customer)
  var returnedSearchTimer = null;

  $("#ReturnedBillsModal").on("shown.bs.modal", function () {
    loadReturnedBillsList("");
  });

  $("#returnedBillsSearchInput").on("input", function () {
    var term = $(this).val();
    clearTimeout(returnedSearchTimer);
    returnedSearchTimer = setTimeout(function () {
      loadReturnedBillsList(term);
    }, 250);
  });

  function loadReturnedBillsList(searchTerm) {
    var $tbody = $("#returnedBillsTableBody");
    $tbody.html(
      '<tr><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr>',
    );

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      dataType: "json",
      data: {
        filter: true,
        length: 5,
        start: 0,
        returned_only: true,
        search: { value: searchTerm || "" },
      },
      success: function (res) {
        var rows = res && res.data ? res.data : [];
        console.log("Returned bills list response:", rows);
        $tbody.empty();

        if (!rows.length) {
          $tbody.html(
            '<tr><td colspan="7" class="text-center text-muted py-3">No records found</td></tr>',
          );
          return;
        }

        rows.forEach(function (row) {
          console.log(
            "Processing returned row - Bill:",
            row.bill_number,
            "Status:",
            row.status,
            "IsCancelled:",
            row.is_cancelled,
          );
          var isCancelled = row.status === "cancelled" || row.is_cancelled == 1;
          var statusLabel =
            row.status_label ||
            (isCancelled
              ? "<span class='badge bg-danger'>Cancelled</span>"
              : row.status || "");
          var html =
            "<tr class='returned-row" +
            (isCancelled ? " table-danger" : "") +
            "' data-id='" +
            (row.id || "") +
            "' data-status='" +
            (row.status || "") +
            "'>" +
            "<td>" +
            (row.id || "") +
            "</td>" +
            "<td>" +
            (row.bill_number || "") +
            "</td>" +
            "<td>" +
            (row.customer_name || "") +
            "</td>" +
            "<td>" +
            (row.rental_date || "") +
            "</td>" +
            "<td>" +
            (row.received_date || "") +
            "</td>" +
            "<td>" +
            (row.total_items || 0) +
            "</td>" +
            "<td>" +
            statusLabel +
            "</td>" +
            "</tr>";
          $tbody.append(html);
        });

        $("#returnedBillsTable tbody .returned-row")
          .off("click")
          .on("click", function () {
            var id = $(this).data("id");
            if (id) {
              loadRentDetails(id);
              $("#ReturnedBillsModal").modal("hide");
            }
          });
      },
      error: function (xhr) {
        console.error("Server Error:", xhr.responseText);
        $tbody.html(
          '<tr><td colspan="7" class="text-center text-danger py-3">Failed to load records</td></tr>',
        );
      },
    });
  }

  // Load Customer Table (simple table with search)
  $("#CustomerSelectModal").on("shown.bs.modal", function () {
    $("#customerSearchInput").val("");
    loadCustomerTable("");
  });

  $(document).on("keyup", "#customerSearchInput", function () {
    var term = $(this).val();
    loadCustomerTable(term);
  });

  function loadCustomerTable(searchTerm) {
    var $tbody = $("#customerSelectTable tbody");
    var $status = $("#customerTableStatus");

    $status.text("Loading...");
    $tbody.html(
      "<tr><td colspan='7' class='text-center text-muted py-3'>Loading...</td></tr>",
    );

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "search_customers_simple",
        search: searchTerm || "",
      },
      success: function (res) {
        if (!res || res.status !== "success") {
          $tbody.html(
            "<tr><td colspan='7' class='text-center text-danger py-3'>Failed to load customers</td></tr>",
          );
          $status.text("");
          return;
        }

        var rows = res.data || [];
        if (rows.length === 0) {
          $tbody.html(
            "<tr><td colspan='7' class='text-center text-muted py-3'>No customers found</td></tr>",
          );
          $status.text("0 customers");
          return;
        }

        var html = "";
        rows.forEach(function (row) {
          var nameCell = row.name;
          if (parseInt(row.is_blacklisted) === 1) {
            nameCell += ' <span class="badge bg-danger ms-1">Blocked</span>';
          }
          html +=
            "<tr data-id='" +
            row.id +
            "' data-name='" +
            row.name +
            "' data-code='" +
            row.code +
            "' data-blacklist='" +
            (row.is_blacklisted || 0) +
            "'>" +
            "<td>" +
            (row.id || "") +
            "</td>" +
            "<td>" +
            (row.code || "") +
            "</td>" +
            "<td>" +
            nameCell +
            "</td>" +
            "<td>" +
            (row.mobile_number || "") +
            "</td>" +
            "<td>" +
            (row.nic || "") +
            "</td>" +
            "<td>" +
            (row.address || "") +
            "</td>" +
            "<td>" +
            (row.outstanding || "0.00") +
            "</td>" +
            "</tr>";
        });

        $tbody.html(html);
        $status.text(rows.length + " customers");
      },
      error: function () {
        $tbody.html(
          "<tr><td colspan='7' class='text-center text-danger py-3'>Failed to load customers</td></tr>",
        );
        $status.text("");
      },
    });
  }

  $("#customerSelectTable tbody").on("click", "tr", function () {
    var $row = $(this);
    var isBlacklisted = parseInt($row.data("blacklist")) === 1;
    if (isBlacklisted) {
      swal({
        title: "Blocked!",
        text: "This customer is blacklisted and cannot be selected.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    var id = $row.data("id");
    var code = $row.data("code");
    var name = $row.data("name");
    var outstanding = $row.find("td").eq(6).text() || "0.00";
    if (id) {
      $("#customer_id").val(id);
      $("#customer_display").val(code + " - " + name);
      $("#customerOutstandingValue").text(outstanding);
      $("#customerOutstandingAlert").show();
      $("#CustomerSelectModal").modal("hide");
    }
  });

  // Hide outstanding alert when clearing customer
  $("#customer_display").on("input", function () {
    if (!$(this).val()) {
      $("#customerOutstandingAlert").hide();
      $("#customerOutstandingValue").text("0.00");
    }
  });

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
            var imgSrc = data
              ? "uploads/equipment/" + data
              : "assets/images/no-image.png";
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
      var availabilityBadge = "";
      if (d.no_sub_items == 1) {
        var availableStock =
          (parseFloat(d.total_quantity) || 0) - (parseFloat(d.rented_qty) || 0);
        if (availableStock > 0) {
          availabilityBadge =
            '<span class="badge bg-success">' +
            availableStock +
            " Available</span>";
        } else {
          availabilityBadge =
            '<span class="badge bg-danger">Not Available</span>';
        }
      } else {
        if (d.available_sub > 0) {
          availabilityBadge =
            '<span class="badge bg-success">' +
            d.available_sub +
            "/" +
            d.total_sub +
            " Available</span>";
        } else {
          availabilityBadge = '<span class="badge bg-danger">All Rented</span>';
        }
      }

      return `
        <div class="p-3 bg-light">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-sm table-borderless mb-0">
                <tr><td class="fw-bold" style="width: 140px;">Serial Number:</td><td>${d.serial_number || "-"}</td></tr>
                <tr><td class="fw-bold">Size:</td><td>${d.size || "-"}</td></tr>
                <tr><td class="fw-bold">Category:</td><td>${d.category_label || "-"}</td></tr>
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
    $("#equipmentSelectTable tbody")
      .off("click", "td.details-control")
      .on("click", "td.details-control", function (e) {
        e.stopPropagation(); // Prevent row click from firing
        var tr = $(this).closest("tr");
        var row = equipmentTable.row(tr);
        var icon = tr.find("td.details-control span.mdi");

        if (row.child.isShown()) {
          // Close
          row.child.hide();
          tr.removeClass("shown");
          icon
            .removeClass("mdi-minus-circle-outline")
            .addClass("mdi-plus-circle-outline");
        } else {
          // Open
          row.child(formatEquipmentDetails(row)).show();
          tr.addClass("shown");
          icon
            .removeClass("mdi-plus-circle-outline")
            .addClass("mdi-minus-circle-outline");
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

          if (parseFloat(data.global_available_qty) > 0) {
            isAvailable = true;
          }

          // if (!isAvailable) {
          //   swal({
          //     title: "Not Available!",
          //     text: "All units of this equipment are currently rented out.",
          //     type: "warning",
          //     showConfirmButton: true,
          //   });
          //   return;
          // }

          $("#item_equipment_id").val(data.id);
          $("#item_equipment_display").val(data.code + " - " + data.item_name);

          // Set rates
          currentRentOneDay = parseFloat(data.rent_one_day) || 0;
          currentRentOneMonth = parseFloat(data.rent_one_month) || 0;
          currentDepositOneDay = parseFloat(data.deposit_one_day) || 0;
          currentAllowManualAmount = data.change_value == 1;
          currentIsFixedRate = data.is_fixed_rate == 1;
          currentEquipmentId = data.id;

          // Amount field editability (force remove attribute when allowed)
          if (currentAllowManualAmount) {
            $("#item_amount").prop("readonly", false).removeAttr("readonly");
          } else {
            $("#item_amount")
              .prop("readonly", true)
              .attr("readonly", "readonly");
          }
          $("#item_amount").data("manual-edited", false);
          manualAmountEditEnabled = false;

          // Show/hide the red + button
          $("#btn-enable-amount-edit").show();
          $("#btn-enable-amount-edit")
            .html('<i class="uil uil-plus"></i>')
            .removeClass("btn-success")
            .addClass("btn-danger");
          $("#btn-enable-amount-edit").attr(
            "title",
            "Enable manual amount editing",
          );

          // Reset calculations
          $("#item_duration").val("");
          $("#item_amount").val("");

          // Auto-fill amount if equipment is selected (will be updated when sub-equipment is selected)
          calculateRentDetails();
          loadDepartments(data.id);

          // Clear sub equipment when equipment changes
          $("#item_sub_equipment_id").val("");
          $("#item_sub_equipment_display").val("");

          // Handle No Sub-Items logic
          if (data.no_sub_items == 1) {
            $("#item_qty").prop("readonly", false); // Enable Qty
            $("#item_sub_equipment_display")
              .prop("disabled", true)
              .attr("placeholder", "Not Required");
            $("#btn-select-sub-equipment").prop("disabled", true);
            $("#returned_qty_container").show(); // Show returned qty field
            // Store flag
            $("#item_equipment_id").data("no_sub_items", 1);
          } else {
            $("#item_qty").prop("readonly", true).val(1); // Disable Qty and reset to 1
            $("#item_sub_equipment_display")
              .prop("disabled", false)
              .attr("placeholder", "Select sub equipment");
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
          d.department_id = $("#item_department_id").val();
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

          // Auto-fill amount and department when sub-equipment is selected
          calculateRentDetails();
          loadDepartments($("#item_equipment_id").val(), data.id);
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
    if (!isValidDateString($("#rental_start_date").val())) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Rental date must be a valid date in YYYY-MM-DD format",
        type: "error",
        timer: 2500,
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
        text: "Please enter issue date",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }
    if (!isValidDateString($("#rental_date").val())) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Issue date must be a valid date in YYYY-MM-DD format",
        type: "error",
        timer: 2500,
        showConfirmButton: false,
      });
      return false;
    }
    if (!$("#rental_start_date").val()) {
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

    // Ensure all items use the current Rental Date field value
    var currentRentalStartDate = $("#rental_start_date").val();
    if (currentRentalStartDate) {
      rentItems.forEach(function (item) {
        item.rental_date = currentRentalStartDate;
      });
    }

    applyRentalDateDefaults(true);

    var formData = new FormData($("#form-data")[0]);
    formData.append("create", true);
    formData.append("items", JSON.stringify(rentItems));
    // Received date is system-controlled; do not send manual value
    formData.delete("received_date");
    formData.append("transport_cost", parseAmount($("#transport_cost").val()) || 0);
    formData.append("custom_deposit", parseAmount($("#custom_deposit").val()) || 0);

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
        } else if (result.status === "duplicate") {
          swal({
            title: "Duplicate Bill Number!",
            text: result.message || "Bill number already exists.",
            type: "warning",
            showConfirmButton: true,
          });
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

    // Ensure all items use the current Rental Date field value
    var currentRentalStartDate = $("#rental_start_date").val();
    if (currentRentalStartDate) {
      rentItems.forEach(function (item) {
        item.rental_date = currentRentalStartDate;
      });
    }

    var formData = new FormData($("#form-data")[0]);
    formData.append("update", true);
    formData.append("items", JSON.stringify(rentItems));
    // Received date is system-controlled; do not send manual value
    formData.delete("received_date");
    formData.append("transport_cost", parseAmount($("#transport_cost").val()) || 0);
    formData.append("custom_deposit", parseAmount($("#custom_deposit").val()) || 0);

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
        } else if (result.status === "duplicate") {
          swal({
            title: "Duplicate Bill Number!",
            text: result.message || "Bill number already exists.",
            type: "warning",
            showConfirmButton: true,
          });
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
    isEditingExistingRent = false;
    rentalStartDateUserOverride = false;
    $("#form-data")[0].reset();
    $("#rent_id").val("");
    $("#customer_id").val("");
    $("#customer_display").val("");
    $("#workplace_address").val("");
    $("#payment_type_id").val("");
    $("#item_equipment_id").val("");
    $("#item_equipment_display").val("");
    $("#item_sub_equipment_id").val("");
    $("#item_sub_equipment_display").val("");
    $("#item_sub_equipment_display").val("");
    $("#transport_cost").val("");
    $("#custom_deposit").val("");
    $("#transport_cost, #custom_deposit").prop("readonly", false); // allow manual input for new rent
    $("#btn-manage-deposits").hide();
    $("#depositPaymentsTableBody").html(
      '<tr><td colspan="5" class="text-center text-muted py-3">No deposit payments recorded yet.</td></tr>',
    );
    $("#deposit_modal_total").text("0.00");
    $("#calculated_deposit_display").text(formatAmount(0));
    $("#customer_refund_balance").text(formatAmount(0));
    $("#company_refund_balance_display").text(formatAmount(0));
    $("#company_refund_row").hide();
    $("#customer_refund_badge")
      .removeClass("badge bg-danger bg-success")
      .text("");
    $("#customer_paid_total").text("0.00");
    $("#customer_rent_outstanding").text("0.00");
    totalCalculatedDeposit = 0;
    rentItems = [];
    updateItemsTable();
    $("#create").show();
    $("#update").show();
    $("#print").hide();
    $("#return-all").hide();
    $("#cancel-return").hide();
    $("#cancel-bill").hide();
    $("#cancel-bill-alert").remove();
    $("#received_date_container").hide();
    $("#received_date").prop("readonly", true).val("").removeClass("bg-light");

    // Clear cheque and bank transfer fields
    $("#cheque_number").val("");
    $("#cheque_date").val("");
    $("#cheque_branch_id").val("");
    $("#cheque_branch_display").val("");
    $("#transfer_branch_id").val("");
    $("#transfer_branch_display").val("");
    $("#bank_account_number").val("");
    $("#bank_reference").val("");
    $("#cheque_details_section").hide();
    $("#bank_transfer_details_section").hide();

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

  // Return All Items - Open Modal
  $("#return-all").click(function (e) {
    e.preventDefault();
    var rentId = $("#rent_id").val();
    if (!rentId) return;

    // Initialize return all modal with current date/time
    var now = new Date();
    var yyyy = now.getFullYear();
    var mm = String(now.getMonth() + 1).padStart(2, "0");
    var dd = String(now.getDate()).padStart(2, "0");
    var hh = String(now.getHours()).padStart(2, "0");
    var min = String(now.getMinutes()).padStart(2, "0");

    $("#return_all_date").val(yyyy + "-" + mm + "-" + dd);
    $("#return_all_time").val(hh + ":" + min);
    $("#return_all_after_9am").prop("checked", false);
    $("#return_all_rental_override").val("");
    $("#return_all_extra_charge").val("");
    $("#return_all_remark").val("");
    $("#returnAllPreview").hide();

    // Remove readonly attribute to make the field editable
    $("#return_all_date").prop("readonly", false).removeAttr("readonly");

    // Show the modal
    $("#returnAllModal").modal("show");

    // Initialize/reinitialize date picker for return_all_date with editable configuration
    if ($.fn.datepicker) {
      $("#return_all_date")
        .datepicker("destroy")
        .datepicker({
          dateFormat: "yy-mm-dd",
          changeMonth: true,
          changeYear: true,
          autoclose: true,
          todayHighlight: true,
          onClose: function () {
            // Ensure field remains editable after closing datepicker
            $(this).prop("readonly", false).removeAttr("readonly");
          },
        });
      // Ensure field remains editable after datepicker init
      $("#return_all_date").prop("readonly", false).removeAttr("readonly");
    }

    // Force remove readonly after modal is fully shown
    setTimeout(function () {
      $("#return_all_date").prop("readonly", false).removeAttr("readonly");
    }, 100);
  });

  // Ensure return_all_date remains editable when modal is shown
  $("#returnAllModal").on("shown.bs.modal", function () {
    $("#return_all_date").prop("readonly", false).removeAttr("readonly");
  });

  // Calculate return all preview when inputs change (with server-side preview)
  var returnAllPreviewTimer = null;
  $(
    "#return_all_date, #return_all_time, #return_all_after_9am, #return_all_rental_override, #return_all_extra_charge, #return_all_repair_cost",
  ).on("change input", function () {
    // Debounce to avoid rapid-fire AJAX calls from datepicker events
    clearTimeout(returnAllPreviewTimer);
    returnAllPreviewTimer = setTimeout(function () {
      fetchReturnAllPreview();
    }, 300);
  });

  function fetchReturnAllPreview() {
    var rentId = $("#rent_id").val();
    var returnDate = $("#return_all_date").val();
    var returnTime = $("#return_all_time").val();
    var after9am = $("#return_all_after_9am").is(":checked") ? 1 : 0;
    var rentalOverrideInput = $("#return_all_rental_override").val();
    var rentalOverride =
      rentalOverrideInput === "" ? null : parseFloat(rentalOverrideInput);
    var extraChargeInput = $("#return_all_extra_charge").val();
    var extraCharge =
      extraChargeInput === "" ? 0 : parseFloat(extraChargeInput);
    var repairCostInput = $("#return_all_repair_cost").val();
    var repairCost = repairCostInput === "" ? 0 : parseFloat(repairCostInput);

    if (!rentId || !returnDate || !returnTime) {
      $("#returnAllPreview").hide();
      return;
    }

    // Calculate day count text (client-side)
    var rentalStart = $("#rental_start_date").val();
    var dayCountText = "-";
    if (rentalStart) {
      var returnDateOnly = new Date(returnDate + " 00:00");
      var rentalStartDate = new Date(rentalStart + " 00:00");
      if (!isNaN(returnDateOnly) && !isNaN(rentalStartDate)) {
        var msDiff = returnDateOnly - rentalStartDate;
        var baseDays =
          msDiff >= 0
            ? Math.max(1, Math.ceil(msDiff / (1000 * 60 * 60 * 24)))
            : 0;
        var totalDays = baseDays + (after9am ? 1 : 0);
        dayCountText = totalDays + " day" + (totalDays === 1 ? "" : "s");
      }
    }

    // Call preview-only API to get settlement totals
    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      data: {
        action: "return_all",
        preview_only: 1,
        rent_id: rentId,
        return_date: returnDate,
        return_time: returnTime,
        after_9am_extra_day: after9am,
        rental_override: rentalOverride,
        extra_charge_amount: extraCharge,
        repair_cost: repairCost,
      },
      dataType: "json",
      success: function (res) {
        var calc = res && res.calculation ? res.calculation : {};

        var previewHtml =
          "<p><strong>Return Date:</strong> " +
          returnDate +
          " " +
          returnTime +
          "</p>";
        previewHtml +=
          "<p><strong>Day Count:</strong> " + dayCountText + "</p>";
        previewHtml +=
          "<p><strong>After 9:00 AM:</strong> " +
          (after9am ? "Yes (extra day will be counted)" : "No") +
          "</p>";

        // Settlement summary
        var settlement = [];
        var rentalLabel =
          rentalOverride !== null && !isNaN(rentalOverride)
            ? "Rental (override)"
            : "Rental";
        var rentalValue =
          rentalOverride !== null && !isNaN(rentalOverride)
            ? rentalOverride
            : Number(calc.rental_amount || 0);
        settlement.push(
          rentalLabel + ": Rs. " + Number(rentalValue || 0).toFixed(2),
        );
        settlement.push(
          "Extra Day: Rs. " + Number(calc.extra_day_amount || 0).toFixed(2),
        );
        settlement.push(
          "Damage: Rs. " + Number(calc.damage_amount || 0).toFixed(2),
        );
        settlement.push(
          "Penalty: Rs. " + Number(calc.penalty_amount || 0).toFixed(2),
        );
        if (Number(calc.extra_charge_amount || 0) > 0) {
          settlement.push(
            "Extra Charge: Rs. " + Number(calc.extra_charge_amount).toFixed(2),
          );
        }
        if (Number(calc.repair_cost || 0) > 0) {
          settlement.push(
            "Repair Cost: Rs. " + Number(calc.repair_cost).toFixed(2),
          );
        }
        settlement.push(
          "Net: Rs. " + Number(calc.settle_amount || 0).toFixed(2),
        );
        if (Number(calc.refund_amount || 0) > 0) {
          settlement.push(
            "Refund: Rs. " + Number(calc.refund_amount).toFixed(2),
          );
        } else if (Number(calc.additional_payment || 0) > 0) {
          settlement.push(
            "Customer Pays: Rs. " + Number(calc.additional_payment).toFixed(2),
          );
        }

        previewHtml +=
          "<hr><p><strong>Settlement Preview:</strong><br>" +
          settlement.join("<br>") +
          "</p>";

        // If company owes refund, add partial refund checkbox and input
        var refundAmount = Number(calc.refund_amount || 0);
        if (refundAmount > 0) {
          previewHtml += '<div class="mt-2">';
          previewHtml += '<div class="form-check mb-2">';
          previewHtml +=
            '<input class="form-check-input" type="checkbox" id="return_all_partial_refund" value="1">';
          previewHtml +=
            '<label class="form-check-label fw-bold" for="return_all_partial_refund">';
          previewHtml += "Partial Refund - අර්ධ ආපසු ගෙවීම";
          previewHtml += "</label>";
          previewHtml += "</div>";
          previewHtml +=
            '<div id="partial_refund_section" style="display:none;">';
          previewHtml +=
            '<label class="fw-bold">Company Refund Paid - සමාගම ගෙවූ ආපසු මුදල:</label> ';
          previewHtml +=
            '<input type="number" id="return_all_company_refund_paid" class="form-control form-control-sm d-inline-block" ';
          previewHtml +=
            'style="width:160px; text-align:right;" step="0.01" min="0" ';
          previewHtml +=
            'max="' +
            refundAmount.toFixed(2) +
            '" value="' +
            refundAmount.toFixed(2) +
            '">';
          previewHtml +=
            '<div class="mt-1"><strong>Company Outstanding - සමාගම් හිඟ: </strong>';
          previewHtml +=
            '<span id="return_all_company_outstanding_display" class="text-success fw-bold">Rs. 0.00</span></div>';
          previewHtml += "</div>";
          previewHtml += "</div>";
        }

        // If customer owes, add Customer Paid input and Outstanding display
        var additionalPayment = Number(calc.additional_payment || 0);
        if (additionalPayment > 0) {
          previewHtml += '<div class="mt-2">';
          previewHtml +=
            '<label class="fw-bold">Customer Paid Amount:</label> ';
          previewHtml +=
            '<input type="number" id="return_all_customer_paid" class="form-control form-control-sm d-inline-block" ';
          previewHtml +=
            'style="width:140px; text-align:right;" step="0.01" min="0" ';
          previewHtml +=
            'max="' +
            additionalPayment.toFixed(2) +
            '" value="' +
            additionalPayment.toFixed(2) +
            '">';
          previewHtml += '<div class="mt-1"><strong>Outstanding: </strong>';
          previewHtml +=
            '<span id="return_all_outstanding_display" class="text-warning fw-bold">Rs. 0.00</span></div>';
          previewHtml += "</div>";
        }

        $("#returnAllPreviewContent").html(previewHtml);
        $("#returnAllPreview").show();

        // Bind live outstanding calculation for return all
        if (additionalPayment > 0) {
          $("#return_all_customer_paid")
            .on("input", function () {
              var paid = parseFloat($(this).val()) || 0;
              var outstanding = Math.max(0, additionalPayment - paid);
              $("#return_all_outstanding_display").text(
                "Rs. " + outstanding.toFixed(2),
              );
              if (outstanding > 0) {
                $("#return_all_outstanding_display")
                  .removeClass("text-success")
                  .addClass("text-warning");
              } else {
                $("#return_all_outstanding_display")
                  .removeClass("text-warning")
                  .addClass("text-success");
              }
            })
            .trigger("input");
        }

        // Bind partial refund checkbox and live outstanding calculation
        if (refundAmount > 0) {
          $("#return_all_partial_refund").on("change", function () {
            if ($(this).is(":checked")) {
              $("#partial_refund_section").show();
              $("#return_all_company_refund_paid").trigger("input");
            } else {
              $("#partial_refund_section").hide();
              $("#return_all_company_refund_paid").val(refundAmount.toFixed(2));
              $("#return_all_company_outstanding_display")
                .text("Rs. 0.00")
                .removeClass("text-warning")
                .addClass("text-success");
            }
          });

          $("#return_all_company_refund_paid").on("input", function () {
            var paid = parseFloat($(this).val()) || 0;
            var outstanding = Math.max(0, refundAmount - paid);
            $("#return_all_company_outstanding_display").text(
              "Rs. " + outstanding.toFixed(2),
            );
            if (outstanding > 0) {
              $("#return_all_company_outstanding_display")
                .removeClass("text-success")
                .addClass("text-warning");
            } else {
              $("#return_all_company_outstanding_display")
                .removeClass("text-warning")
                .addClass("text-success");
            }
          });
        }
      },
      error: function () {
        // Fallback to basic preview
        var fallbackHtml =
          "<p><strong>Return Date:</strong> " +
          returnDate +
          " " +
          returnTime +
          "</p>";
        fallbackHtml +=
          "<p><strong>Day Count:</strong> " + dayCountText + "</p>";
        fallbackHtml +=
          "<p><strong>After 9:00 AM:</strong> " +
          (after9am ? "Yes (extra day will be counted)" : "No") +
          "</p>";
        $("#returnAllPreviewContent").html(fallbackHtml);
        $("#returnAllPreview").show();
      },
    });
  }

  // Confirm Return All Items
  $("#confirmReturnAllBtn").click(function () {
    var rentId = $("#rent_id").val();
    var billNo = $("#code").val();
    var returnDate = $("#return_all_date").val();
    var returnTime = $("#return_all_time").val();
    var after9amExtraDay = $("#return_all_after_9am").is(":checked") ? 1 : 0;
    var rentalOverrideInput = $("#return_all_rental_override").val();
    var rentalOverride =
      rentalOverrideInput === "" ? null : parseFloat(rentalOverrideInput);

    if (!rentId) {
      swal({
        title: "Error!",
        text: "No rent record selected.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    if (!returnDate || !returnTime) {
      swal({
        title: "Error!",
        text: "Please enter return date and time.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    // Disable button to prevent double submission
    $("#confirmReturnAllBtn").prop("disabled", true);

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      data: {
        action: "return_all",
        rent_id: rentId,
        return_date: returnDate,
        return_time: returnTime,
        after_9am_extra_day: after9amExtraDay,
        rental_override: rentalOverride,
        extra_charge_amount:
          parseFloat($("#return_all_extra_charge").val()) || 0,
        repair_cost: parseFloat($("#return_all_repair_cost").val()) || 0,
        customer_paid: parseFloat($("#return_all_customer_paid").val()) || 0,
        company_refund_paid:
          parseFloat($("#return_all_company_refund_paid").val()) || 0,
        return_remark: $("#return_all_remark").val() || "",
      },
      dataType: "JSON",
      success: function (result) {
        if (result.status === "success") {
          $("#returnAllModal").modal("hide");

          // Build settlement summary if backend sent calculation
          var calc = result.calculation || {};
          var settlementText =
            result.message || "All items marked as returned.";
          var summaryLines = [];
          if (
            calc &&
            (calc.refund_amount ||
              calc.additional_payment ||
              calc.rental_amount ||
              calc.extra_charge_amount)
          ) {
            summaryLines.push(
              "Rental: Rs. " + Number(calc.rental_amount || 0).toFixed(2),
            );
            summaryLines.push(
              "Extra Day: Rs. " + Number(calc.extra_day_amount || 0).toFixed(2),
            );
            summaryLines.push(
              "Damage: Rs. " + Number(calc.damage_amount || 0).toFixed(2),
            );
            summaryLines.push(
              "Penalty: Rs. " + Number(calc.penalty_amount || 0).toFixed(2),
            );
            if (Number(calc.extra_charge_amount || 0) > 0) {
              summaryLines.push(
                "Extra Charge: Rs. " +
                Number(calc.extra_charge_amount).toFixed(2),
              );
            }
            if (Number(calc.repair_cost || 0) > 0) {
              summaryLines.push(
                "Repair Cost: Rs. " + Number(calc.repair_cost).toFixed(2),
              );
            }
            summaryLines.push(
              "Net: Rs. " + Number(calc.settle_amount || 0).toFixed(2),
            );

            if (Number(calc.refund_amount || 0) > 0) {
              summaryLines.push(
                "Refund: Rs. " + Number(calc.refund_amount).toFixed(2),
              );
            } else if (Number(calc.additional_payment || 0) > 0) {
              summaryLines.push(
                "Customer Pays: Rs. " +
                Number(calc.additional_payment).toFixed(2),
              );
            }
          }

          swal({
            title: "Success!",
            text:
              settlementText +
              (summaryLines.length ? "\n\n" + summaryLines.join("\n") : ""),
            type: "success",
            timer: 2500,
            showConfirmButton: false,
          });

          setTimeout(function () {
            if (billNo) {
              setTimeout(function () {
                window.location.reload();
              }, 100);
              window.open("rent-invoice.php?bill_no=" + billNo, "_blank");
            } else {
              window.location.reload();
            }
          }, 1500);
        } else {
          $("#confirmReturnAllBtn").prop("disabled", false);
          swal({
            title: "Error!",
            text: result.message || "Failed to return all items.",
            type: "error",
            showConfirmButton: true,
          });
        }
      },
      error: function () {
        $("#confirmReturnAllBtn").prop("disabled", false);
        swal({
          title: "Error!",
          text: "Unable to process return all request.",
          type: "error",
          showConfirmButton: true,
        });
      },
    });
  });

  // Cancel Return - reverse all returns for a returned bill
  $("#cancel-return").click(function (e) {
    e.preventDefault();
    var rentId = $("#rent_id").val();
    var rentCode = $("#code").val();

    if (!rentId) {
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
        title: "Cancel Return?",
        text:
          "This will cancel all returns for bill '" +
          rentCode +
          "' and set the bill back to active (rented) status. All return records will be deleted. Are you sure?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, cancel return!",
        cancelButtonText: "No, keep it",
      },
      function (isConfirm) {
        if (isConfirm) {
          $("#page-preloader").show();
          $.ajax({
            url: "ajax/php/equipment-rent-master.php",
            type: "POST",
            data: {
              action: "cancel_return",
              rent_id: rentId,
            },
            dataType: "JSON",
            success: function (result) {
              $("#page-preloader").hide();

              if (result.status === "success") {
                swal({
                  title: "Success!",
                  text: result.message || "Return cancelled successfully.",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false,
                });
                setTimeout(function () {
                  loadRentDetails(rentId);
                }, 2000);
              } else {
                swal({
                  title: "Error!",
                  text: result.message || "Failed to cancel return.",
                  type: "error",
                  showConfirmButton: true,
                });
              }
            },
            error: function (xhr) {
              $("#page-preloader").hide();
              console.error("Cancel return error:", xhr.responseText);
              swal({
                title: "Error!",
                text: "Unable to cancel return. Check console for details.",
                type: "error",
                showConfirmButton: true,
              });
            },
          });
        }
      },
    );
  });

  // Cancel Bill - open modal to capture amount and date, then cancel
  var cancelBillModalInstance = null;
  if (
    typeof bootstrap !== "undefined" &&
    document.getElementById("cancelBillModal")
  ) {
    cancelBillModalInstance = new bootstrap.Modal(
      document.getElementById("cancelBillModal"),
    );
  }

  $("#cancel-bill").click(function (e) {
    e.preventDefault();
    var rentId = $("#rent_id").val();
    if (!rentId) {
      swal({
        title: "Error!",
        text: "Please select an equipment rent record first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    // reset modal fields
    var today = new Date().toISOString().slice(0, 10);
    $("#cancel_amount").val(0);
    $("#cancel_date").val($("#cancel_date").val() || today);

    if (cancelBillModalInstance) {
      cancelBillModalInstance.show();
    } else {
      $("#cancelBillModal").modal("show");
    }
  });

  $("#confirm-cancel-bill").click(function () {
    var rentId = $("#rent_id").val();
    var rentCode = $("#code").val();
    var cancelAmount = $("#cancel_amount").val() || 0;
    var cancelDate =
      $("#cancel_date").val() || new Date().toISOString().slice(0, 10);

    if (!rentId) {
      swal({
        title: "Error!",
        text: "Please select an equipment rent record first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    $("#page-preloader").show();
    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      data: {
        action: "cancel_bill",
        rent_id: rentId,
        cancel_amount: cancelAmount,
        cancel_date: cancelDate,
      },
      dataType: "json",
      success: function (result) {
        $("#page-preloader").hide();
        if (cancelBillModalInstance) {
          cancelBillModalInstance.hide();
        } else {
          $("#cancelBillModal").modal("hide");
        }

        if (result && result.status === "success") {
          swal({
            title: "Cancelled!",
            text: result.message || "Bill cancelled successfully.",
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
            text: (result && result.message) || "Failed to cancel bill.",
            type: "error",
            showConfirmButton: true,
          });
        }
      },
      error: function (xhr, status, error) {
        $("#page-preloader").hide();
        if (cancelBillModalInstance) {
          cancelBillModalInstance.hide();
        } else {
          $("#cancelBillModal").modal("hide");
        }

        var errorMsg = "Unable to cancel bill.";
        try {
          var errData = JSON.parse(xhr.responseText);
          if (errData && errData.message) {
            errorMsg = errData.message;
          }
        } catch (e) {
          if (xhr && xhr.responseText) {
            errorMsg = "Server error: " + xhr.responseText.substring(0, 100);
          }
        }

        swal({
          title: "Error!",
          text: errorMsg,
          type: "error",
          showConfirmButton: true,
        });
      },
    });
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
                  text:
                    (response && response.message) || "Something went wrong.",
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

  // Sync rental_start_date with issue date when issue date changes (if rental_start_date hasn't been manually set)
  $("#rental_date").on("change", function () {
    // No per-item rental date to sync anymore
  });

  // Payment method change - show/hide cheque and bank transfer details
  function togglePaymentDetails() {
    var selectedText = $("#payment_type_id option:selected")
      .text()
      .trim()
      .toLowerCase();

    // Hide all payment detail sections first
    $("#cheque_details_section").hide();
    $("#bank_transfer_details_section").hide();

    if (selectedText === "cheque") {
      $("#cheque_details_section").slideDown(200);
    } else if (selectedText === "bank transfer") {
      $("#bank_transfer_details_section").slideDown(200);
    }
  }

  $("#payment_type_id").on("change", function () {
    togglePaymentDetails();

    // Clear fields when switching payment methods
    var selectedText = $(this)
      .find("option:selected")
      .text()
      .trim()
      .toLowerCase();
    if (selectedText !== "cheque") {
      $("#cheque_number").val("");
      $("#cheque_date").val("");
      $("#cheque_branch_id").val("");
      $("#cheque_branch_display").val("");
    }
    if (selectedText !== "bank transfer") {
      $("#transfer_branch_id").val("");
      $("#transfer_branch_display").val("");
      $("#bank_account_number").val("");
      $("#bank_reference").val("");
    }
  });

  // Cheque Branch Select Modal - row click
  $(document).on("click", ".select-cheque-branch", function () {
    var branchId = $(this).data("id");
    var bankName = $(this).find("td:eq(1)").text();
    var branchName = $(this).find("td:eq(2)").text();
    $("#cheque_branch_id").val(branchId);
    $("#cheque_branch_display").val(bankName + " | " + branchName);
    $("#ChequeBranchSelectModal").modal("hide");
  });

  // Transfer Branch Select Modal - row click
  $(document).on("click", ".select-transfer-branch", function () {
    var branchId = $(this).data("id");
    var bankName = $(this).find("td:eq(1)").text();
    var branchName = $(this).find("td:eq(2)").text();
    $("#transfer_branch_id").val(branchId);
    $("#transfer_branch_display").val(bankName + " | " + branchName);
    $("#TransferBranchSelectModal").modal("hide");
  });

  // Initialize DataTables for branch modals when shown
  $("#ChequeBranchSelectModal").on("shown.bs.modal", function () {
    if (!$.fn.DataTable.isDataTable("#chequeBranchTable")) {
      $("#chequeBranchTable").DataTable({
        pageLength: 10,
        order: [[1, "asc"]],
      });
    }
  });
  $("#TransferBranchSelectModal").on("shown.bs.modal", function () {
    if (!$.fn.DataTable.isDataTable("#transferBranchTable")) {
      $("#transferBranchTable").DataTable({
        pageLength: 10,
        order: [[1, "asc"]],
      });
    }
  });

  // Initialize button visibility on page load
  $("#update").hide();
  $("#return-all").hide();
  $("#print").hide();
  $("#cancel-return").hide();
  $("#cancel-bill").hide();
  // --- New Logic for Department Selection ---

  // Check availability when Department or Equipment changes
  // --- New Logic for Department Selection (Refactored) ---

  function loadDepartments(equipmentId, subEquipmentId = "") {
    var $deptSelect = $("#item_department_id");
    $deptSelect
      .html('<option value="">Loading...</option>')
      .prop("disabled", true);

    if (!equipmentId) {
      $deptSelect
        .html('<option value="">- Select -</option>')
        .prop("disabled", true);
      return;
    }

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      dataType: "JSON",
      data: {
        action: "get_item_departments",
        equipment_id: equipmentId,
        sub_equipment_id: subEquipmentId,
      },
      success: function (res) {
        if (res.status === "success") {
          var depts = res.departments;
          var options =
            '<option value="" data-available="0">- Select -</option>';

          depts.forEach(function (d) {
            var label = d.name + " (Avail: " + d.available_qty + ")";
            var selected = d.is_selected ? "selected" : "";
            options +=
              '<option value="' +
              d.id +
              '" data-available="' +
              d.available_qty +
              '" ' +
              selected +
              ">" +
              label +
              "</option>";
          });

          $deptSelect.html(options).prop("disabled", false);

          // Auto-select the first department by default
          if (depts.length >= 1) {
            $deptSelect.val(depts[0].id);
          }
        } else {
          $deptSelect.html('<option value="">Error</option>');
        }
      },
      error: function () {
        $deptSelect.html('<option value="">Error</option>');
      },
    });
  }

  // ==============================================
  // Deposit Payments Management
  // ==============================================

  function renderDepositPayments(payments, total) {
    var $tbody = $("#depositPaymentsTableBody");
    $tbody.empty();
    $("#deposit_modal_total").text(parseFloat(total || 0).toFixed(2));

    if (!payments || payments.length === 0) {
      $tbody.html(
        '<tr><td colspan="5" class="text-center text-muted py-3">No deposit payments recorded yet.</td></tr>',
      );
      return;
    }

    payments.forEach(function (p, i) {
      var html =
        "<tr>" +
        "<td>" +
        (i + 1) +
        "</td>" +
        '<td class="text-end fw-bold">' +
        parseFloat(p.amount).toFixed(2) +
        "</td>" +
        "<td>" +
        (p.payment_date || "") +
        "</td>" +
        "<td>" +
        (p.remark || "-") +
        "</td>" +
        '<td class="text-center">' +
        '<button type="button" class="btn btn-sm btn-outline-danger delete-deposit-payment" data-id="' +
        p.id +
        '" title="Delete">' +
        '<i class="uil uil-trash-alt"></i>' +
        "</button>" +
        "</td>" +
        "</tr>";
      $tbody.append(html);
    });
  }

  // Add deposit payment
  $("#btn-add-deposit-payment").click(function () {
    var rentId = $("#rent_id").val();
    if (!rentId) {
      swal({
        title: "Error!",
        text: "Please save the bill first before adding deposit payments.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    var amount = parseFloat($("#deposit_pay_amount").val()) || 0;
    var paymentDate = $("#deposit_pay_date").val();
    var remark = $("#deposit_pay_remark").val();

    if (amount <= 0) {
      swal({
        title: "Error!",
        text: "Please enter a valid amount.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }
    if (!paymentDate) {
      swal({
        title: "Error!",
        text: "Please select a payment date.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    $.ajax({
      url: "ajax/php/equipment-rent-master.php",
      type: "POST",
      dataType: "JSON",
      data: {
        action: "add_deposit_payment",
        rent_id: rentId,
        amount: amount,
        payment_date: paymentDate,
        remark: remark,
      },
      success: function (result) {
        if (result.status === "success") {
          swal({
            title: "Success!",
            text: result.message,
            type: "success",
            timer: 1500,
            showConfirmButton: false,
          });
          renderDepositPayments(result.payments, result.deposit_total);
          $("#custom_deposit").val(formatAmount(parseFloat(result.deposit_total || 0)));
          // Clear form
          $("#deposit_pay_amount").val("");
          $("#deposit_pay_remark").val("");
          // Refresh the rent details to update refund balance etc.
          loadRentDetails(rentId);
        } else {
          swal({
            title: "Error!",
            text: result.message || "Failed to add deposit payment.",
            type: "error",
            showConfirmButton: true,
          });
        }
      },
      error: function () {
        swal({
          title: "Error!",
          text: "Failed to add deposit payment.",
          type: "error",
          showConfirmButton: true,
        });
      },
    });
  });

  // Delete deposit payment
  $(document).on("click", ".delete-deposit-payment", function () {
    var paymentId = $(this).data("id");
    var rentId = $("#rent_id").val();

    swal(
      {
        title: "Delete Deposit Payment?",
        text: "This will remove this deposit payment record.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel",
      },
      function (isConfirm) {
        if (isConfirm) {
          $.ajax({
            url: "ajax/php/equipment-rent-master.php",
            type: "POST",
            dataType: "JSON",
            data: {
              action: "delete_deposit_payment",
              payment_id: paymentId,
            },
            success: function (result) {
              if (result.status === "success") {
                swal({
                  title: "Deleted!",
                  text: result.message,
                  type: "success",
                  timer: 1500,
                  showConfirmButton: false,
                });
                renderDepositPayments(result.payments, result.deposit_total);
                $("#custom_deposit").val(
                  formatAmount(parseFloat(result.deposit_total || 0)),
                );
                // Refresh the rent details
                loadRentDetails(rentId);
              } else {
                swal({
                  title: "Error!",
                  text: result.message || "Failed to delete payment.",
                  type: "error",
                  showConfirmButton: true,
                });
              }
            },
            error: function () {
              swal({
                title: "Error!",
                text: "Failed to delete deposit payment.",
                type: "error",
                showConfirmButton: true,
              });
            },
          });
        }
      },
    );
  });

  // Settle Company Refund Outstanding
  $(document).on("click", "#btn-settle-company-refund", function () {
    var rentId = $("#rent_id").val();
    if (!rentId) return;

    var currentOutstanding =
      parseFloat($("#company_refund_balance_display").text()) || 0;
    if (currentOutstanding <= 0) {
      swal({
        title: "No Outstanding",
        text: "No company refund outstanding to settle.",
        type: "info",
      });
      return;
    }

    swal(
      {
        title: "Settle Company Refund",
        text:
          "Company Outstanding: Rs. " +
          currentOutstanding.toFixed(2) +
          "\nEnter amount to pay to customer:",
        type: "input",
        inputValue: currentOutstanding.toFixed(2),
        showCancelButton: true,
        confirmButtonText: "Settle",
        cancelButtonText: "Cancel",
        closeOnConfirm: false,
        inputPlaceholder: "Enter amount",
      },
      function (inputVal) {
        if (inputVal === false) return;
        var amount = parseFloat(inputVal);
        if (isNaN(amount) || amount <= 0) {
          swal.showInputError("Please enter a valid amount greater than 0.");
          return false;
        }
        if (amount > currentOutstanding) {
          swal.showInputError(
            "Amount cannot exceed outstanding (Rs. " +
            currentOutstanding.toFixed(2) +
            ").",
          );
          return false;
        }

        $.ajax({
          url: "ajax/php/equipment-rent-master.php",
          type: "POST",
          data: {
            action: "settle_company_refund",
            rent_id: rentId,
            amount: amount,
          },
          dataType: "JSON",
          success: function (result) {
            if (result.status === "success") {
              swal({
                title: "Success!",
                text:
                  "Company refund of Rs. " +
                  amount.toFixed(2) +
                  " settled successfully.",
                type: "success",
                timer: 2000,
                showConfirmButton: false,
              });
              // Update display
              var newOutstanding = parseFloat(
                result.new_company_outstanding || 0,
              );
              if (newOutstanding > 0) {
                $("#company_refund_balance_display").text(
                  newOutstanding.toFixed(2),
                );
              } else {
                $("#company_refund_balance_display").text("0.00");
                $("#company_refund_row").hide();
              }
              // Reload full rent details to sync all values
              loadRentDetails(rentId);
            } else {
              swal({
                title: "Error!",
                text: result.message || "Failed to settle.",
                type: "error",
              });
            }
          },
          error: function () {
            swal({
              title: "Error!",
              text: "Server error. Please try again.",
              type: "error",
            });
          },
        });
      },
    );
  });

  // Check for rent_id in URL on load
  const urlParams = new URLSearchParams(window.location.search);
  const rentId = urlParams.get('rent_id');
  if (rentId) {
    loadRentDetails(rentId);
  }
});
