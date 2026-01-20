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
    var mm = String(returnDate.getMonth() + 1).padStart(2, '0');
    var dd = String(returnDate.getDate()).padStart(2, '0');
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
        if (item.status === "rented") {
          actionBtns =
            '<button class="btn btn-sm btn-info return-item-btn me-1" data-index="' +
            index +
            '" title="Mark as Returned"><i class="uil uil-redo"></i></button>';
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
          item.sub_equipment_display +
          "</td>" +
          "<td>" +
          (item.rent_type === 'month' ? 'Month' : 'Day') +
          "</td>" +
          "<td>" +
          '<span class="badge ' + (item.rent_type === 'month' ? 'bg-primary' : 'bg-info') + '">' + parseFloat(item.duration).toFixed(0) + (item.rent_type === 'month' ? ' Months' : ' Days') + '</span>' +
          "</td>" +
          "<td>" +
          parseFloat(item.quantity).toFixed(0) +
          "</td>" +
          "<td>" +
          parseFloat(item.amount).toFixed(2) +
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
    if (!subEquipmentId) {
      swal({
        title: "Error!",
        text: "Please select a sub equipment (unit code)",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }
    if (isSubEquipmentAlreadyAdded(subEquipmentId)) {
      swal({
        title: "Error!",
        text: "This sub equipment is already added to the list",
        type: "warning",
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
      amount: $("#item_amount").val(),
      // Store equipment deposit value for calculation
      deposit_one_day: currentDepositOneDay,
      status: "rented",
      remark: "",
    });

    // Update calculated deposit
    totalCalculatedDeposit += (parseFloat(currentDepositOneDay || 0) * parseFloat($("#item_qty").val() || 1));
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
    $("#item_amount").val("");
  });

  // Calculate on input changes
  $("#item_rent_type, #item_duration, #item_qty, #item_rental_date").on("change keyup", function () {
    calculateRentDetails();
  });

  // Calculate when return date changes
  $("#item_return_date").on("change keyup", function () {
    calculateDurationFromDates();
  });

  // Remove item from list
  $(document).on("click", ".remove-item-btn", function () {
    var index = $(this).data("index");

    // Subtract deposit
    var removedItem = rentItems[index];
    totalCalculatedDeposit -= (parseFloat(removedItem.deposit_one_day || 0) * parseFloat(removedItem.quantity || 1));
    if (totalCalculatedDeposit < 0) totalCalculatedDeposit = 0;
    $("#calculated_deposit_display").text(totalCalculatedDeposit.toFixed(2));
    $("#custom_deposit").val(totalCalculatedDeposit.toFixed(2));

    rentItems.splice(index, 1);
    updateItemsTable();
  });

  // Mark item as returned (in memory)
  $(document).on("click", ".return-item-btn", function () {
    var index = $(this).data("index");
    rentItems[index].status = "returned";
    rentItems[index].return_date = new Date().toISOString().split("T")[0];
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
            return data > 0 ? '<span class="text-danger fw-bold">' + data + '</span>' : '<span class="text-success">0</span>';
          }
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
          $("#received_date").val(rent.received_date || "");
          $("#received_date_container").show();
          $("#remark").val(rent.remark || "");
          $("#transport_cost").val(rent.transport_cost || "0.00");
          $("#custom_deposit").val(rent.deposit_total || "0.00");

          // Reset calculated
          totalCalculatedDeposit = 0;

          // Load items
          rentItems = result.items.map(function (item) {

            // Add to calculated total (multiply by qty)
            totalCalculatedDeposit += (parseFloat(item.equipment_deposit || 0) * parseFloat(item.quantity || 1));

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
              amount: item.amount,
              deposit_one_day: item.equipment_deposit, // Store this
              status: item.status,
              remark: item.remark,
            };
          });

          $("#calculated_deposit_display").text(totalCalculatedDeposit.toFixed(2));
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
        { data: "name", title: "Name" },
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

    $("#equipmentSelectTable").DataTable({
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
        { data: "key", title: "#" },
        { data: "code", title: "Code" },
        { data: "item_name", title: "Item Name" },
        { data: "category_label", title: "Category" },
        { data: "availability_label", title: "Availability" },
      ],
      order: [[2, "asc"]],
      pageLength: 50,
    });

    $("#equipmentSelectTable tbody")
      .off("click")
      .on("click", "tr", function () {
        var data = $("#equipmentSelectTable").DataTable().row(this).data();
        if (data) {
          if (data.available_sub <= 0) {
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
          var billNo = $("#code").val();
          window.open('rent-invoice.php?bill_no=' + encodeURIComponent(billNo), '_blank');

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
    $("#item_equipment_id").val("");
    $("#item_equipment_display").val("");
    $("#item_sub_equipment_id").val("");
    $("#item_sub_equipment_display").val("");
    $("#item_sub_equipment_display").val("");
    $("#transport_cost").val("");
    $("#custom_deposit").val("");
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
                  text: "All items marked as returned.",
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
                  text: result.message,
                  type: "error",
                  showConfirmButton: true,
                });
              }
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
              $("#page-preloader").hide();
              $(".delete-equipment-rent").prop("disabled", false);

              if (response.status === "success") {
                swal({
                  title: "Deleted!",
                  text: "Equipment rent has been deleted.",
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
                  text: "Something went wrong.",
                  type: "error",
                  showConfirmButton: true,
                });
              }
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
});
