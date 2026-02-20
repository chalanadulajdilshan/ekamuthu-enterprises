jQuery(document).ready(function () {
  // Image Cropping
  var cropper;
  var croppedBlob;

  // Load Equipment Table when modal opens
  $("#EquipmentModal").on("shown.bs.modal", function () {
    loadEquipmentTable();
  });

  // Shared required fields list
  var requiredFields = [
    { selector: "#code", message: "Please enter equipment code" },
    { selector: "#item_name", message: "Please enter item name" },
    { selector: "#category", message: "Please select category" },
    { selector: "#department", message: "Please select department" },
    { selector: "#serial_number", message: "Please enter serial number" },
    { selector: "#damage", message: "Please enter damage status/notes" },
    { selector: "#size", message: "Please enter size" },
    { selector: "#rent_one_day", message: "Please enter one day's rent" },
    { selector: "#deposit_one_day", message: "Please enter one day's deposit" },
    { selector: "#rent_one_month", message: "Please enter one month's rent" },
    { selector: "#value", message: "Please enter value" },
    { selector: "#quantity", message: "Please enter quantity" },
  ];

  function getCheckboxVal(selector) {
    return $(selector).is(":checked") ? 1 : 0;
  }

  function toggleAddSubButton() {
    var hasEquipment = $("#equipment_id").val();
    var noSubItems = $("#no_sub_items").is(":checked");

    if (noSubItems) {
      $("#department_container").hide();
      $("#department").prop("required", false);
    } else {
      $("#department_container").show();
      $("#department").prop("required", true);
    }

    if (hasEquipment) {
      if (noSubItems) {
        $("#add-sub-equipment").addClass("d-none");
        $("#add-department-stock").removeClass("d-none");
      } else {
        $("#add-sub-equipment").removeClass("d-none");
        $("#add-department-stock").addClass("d-none");
      }
    } else {
      $("#add-sub-equipment").addClass("d-none");
      $("#add-department-stock").addClass("d-none");
    }
  }

  $("#no_sub_items").change(function () {
    toggleAddSubButton();
  });

  toggleAddSubButton();

  // Handle "Stock" button click to set equipment_id in the modal
  $("#add-department-stock").click(function () {
    var equipmentId = $("#equipment_id").val();
    $("#stock_equipment_id").val(equipmentId);
  });

  // Equipment code autocomplete
  var equipmentCodeCache = [];

  function loadEquipmentCodes() {
    if (equipmentCodeCache.length) return;
    $.ajax({
      url: "ajax/php/equipment-master.php",
      type: "POST",
      dataType: "JSON",
      data: { action: "list_codes" },
      success: function (res) {
        if (res.status === "success" && Array.isArray(res.data)) {
          equipmentCodeCache = res.data;
          initAutocomplete();
        }
      },
    });
  }

  function initAutocomplete() {
    if (!$.ui || !$.ui.autocomplete) return;
    $("#code").autocomplete({
      minLength: 0,
      source: equipmentCodeCache,
      select: function (event, ui) {
        if (ui && ui.item) {
          autofillEquipment(ui.item.code);
        }
      },
    });
  }

  function autofillEquipment(code) {
    $.ajax({
      url: "ajax/php/equipment-master.php",
      type: "POST",
      dataType: "JSON",
      data: { action: "get_by_code", code: code },
      success: function (res) {
        if (res.status === "success" && res.data) {
          var data = res.data;
          $("#equipment_id").val(data.id || "");
          $("#code").val(data.code || "");
          $("#item_name").val(data.item_name || "");
          $("#category").val(data.category || "");
          $("#department").val(data.department_id || "");
          $("#serial_number").val(data.serial_number || "");
          $("#damage").val(data.damage || "");
          $("#size").val(data.size || "");
          $("#rent_one_day").val(data.rent_one_day || "0");
          $("#deposit_one_day").val(data.deposit_one_day || "0");
          $("#rent_one_month").val(data.rent_one_month || "0");
          $("#value").val(data.value || "0");
          $("#quantity").val(data.quantity || "0");
          $("#no_sub_items").prop("checked", data.no_sub_items == 1);
          $("#change_value").prop("checked", data.change_value == 1);
          $("#is_fixed_rate").prop("checked", data.is_fixed_rate == 1);
          $("#remark").val(data.remark || "");
          $("#old_image_name").val(data.image_name || "");

          if (data.image_name) {
            $("#image_preview").attr(
              "src",
              "uploads/equipment/" + data.image_name,
            );
            $("#preview_text").hide();
          } else {
            $("#image_preview").attr("src", "assets/images/no-image.png");
            $("#preview_text").show();
          }

          // Show update button, hide create button
          $("#create").hide();
          $("#update").show();

          toggleAddSubButton();
        }
      },
    });
  }

  // Trigger suggestions on focus
  $("#code").on("focus", function () {
    loadEquipmentCodes();
    $(this).autocomplete("search", $(this).val());
  });

  var equipmentRowsCache = [];

  function renderEquipmentRows(query) {
    var $tbody = $("#equipmentTable tbody");
    $tbody.empty();

    var normalizedQuery = (query || "").toString().toLowerCase().trim();

    var filtered = equipmentRowsCache.filter(function (row) {
      if (!normalizedQuery) return true;
      var id = (row.id || "").toString().toLowerCase();
      var code = (row.code || "").toString().toLowerCase();
      var name = (row.item_name || "").toString().toLowerCase();
      return id.includes(normalizedQuery) || code.includes(normalizedQuery) || name.includes(normalizedQuery);
    });

    // If no search query, show only first 5 rows; otherwise show all matches
    var rowsToRender = normalizedQuery ? filtered : filtered.slice(0, 5);

    rowsToRender.forEach(function (row) {
      var imgSrc = row.image_name
        ? "uploads/equipment/" + row.image_name
        : "assets/images/no-image.png";

      var detailsHtml = `
        <div class="fw-bold">${row.item_name || "-"}</div>
        <div class="text-muted small">
          <span class="fw-bold text-primary">SN -</span> <span class="text-danger">${row.serial_number || "-"}</span> |
          <span class="fw-bold text-primary">Deposit -</span> <span class="text-danger">Rs. ${row.deposit_one_day || "0.00"}</span> |
          <span class="fw-bold text-primary">Qty -</span> <span class="badge bg-info">${row.quantity || 0}</span> |
          <span class="fw-bold text-primary">Size -</span> <span class="text-danger">${row.size || "-"}</span> |
          <span class="fw-bold text-primary">Category -</span> <span class="text-danger">${row.category_label || "-"}</span>
        </div>
      `;

      var $tr = $(
        `<tr data-id="${row.id || ""}" data-code="${row.code || ""}" data-item_name="${row.item_name || ""}" data-category="${row.category || ""}" data-department_id="${row.department_id || ""}" data-serial_number="${row.serial_number || ""}" data-damage="${row.damage || ""}" data-size="${row.size || ""}" data-rent_one_day="${row.rent_one_day || "0"}" data-deposit_one_day="${row.deposit_one_day || "0"}" data-rent_one_month="${row.rent_one_month || "0"}" data-value="${row.value || "0"}" data-quantity="${row.quantity || "0"}" data-no_sub_items="${row.no_sub_items || 0}" data-change_value="${row.change_value || 0}" data-is_fixed_rate="${row.is_fixed_rate || 0}" data-remark="${row.remark || ""}" data-image_name="${row.image_name || ""}">
              <td>${row.key || ""}</td>
              <td><img src="${imgSrc}" alt="Img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
              <td>${row.code || ""}</td>
              <td>${detailsHtml}</td>
            </tr>`,
      );

      $tbody.append($tr);
    });

    // Row click event to populate form and close modal
    $tbody
      .off("click", "tr")
      .on("click", "tr", function () {
        var data = $(this).data();

        $("#equipment_id").val(data.id || "");
        $("#code").val(data.code || "");
        $("#item_name").val(data.item_name || "");
        $("#category").val(data.category || "");
        $("#department").val(data.department_id || "");
        $("#serial_number").val(data.serial_number || "");
        $("#damage").val(data.damage || "");
        $("#size").val(data.size || "");
        $("#rent_one_day").val(data.rent_one_day || "0");
        $("#deposit_one_day").val(data.deposit_one_day || "0");
        $("#rent_one_month").val(data.rent_one_month || "0");
        $("#value").val(data.value || "0");
        $("#quantity").val(data.quantity || "0");
        $("#no_sub_items").prop("checked", data.no_sub_items == 1);
        $("#change_value").prop("checked", data.change_value == 1);
        $("#is_fixed_rate").prop("checked", data.is_fixed_rate == 1);
        $("#remark").val(data.remark || "");
        $("#old_image_name").val(data.image_name || "");

        if (data.image_name) {
          $("#image_preview").attr("src", "uploads/equipment/" + data.image_name);
          $("#preview_text").hide();
        } else {
          $("#image_preview").attr("src", "assets/images/no-image.png");
          $("#preview_text").show();
        }

        $("#create").hide();
        $("#update").show();

        toggleAddSubButton();

        $("#EquipmentModal").modal("hide");
      });
  }

  function loadEquipmentTable() {
    $.ajax({
      url: "ajax/php/equipment-master.php",
      type: "POST",
      dataType: "JSON",
      data: { filter: true },
      success: function (res) {
        equipmentRowsCache = Array.isArray(res.data) ? res.data : [];

        // initial render (shows 5 rows)
        renderEquipmentRows("");

        // Search filter by ID, code, or item name (renders all matches)
        $("#equipmentSearch")
          .off("input")
          .on("input", function () {
            renderEquipmentRows($(this).val());
          });
      },
      error: function (xhr) {
        console.error("Server Error Response:", xhr.responseText);
        $("#equipmentTable tbody").empty().append(
          '<tr><td colspan="4" class="text-center text-danger">Failed to load equipment list</td></tr>',
        );
      },
    });
  }

  // Create Equipment
  $("#create").click(function (event) {
    event.preventDefault();

    // Disable the button to prevent multiple submissions
    $("#create").prop("disabled", true);

    // Validation
    var invalidField = requiredFields.find(function (field) {
      if (field.selector === "#department" && $("#no_sub_items").is(":checked")) {
        return false;
      }
      return !$(field.selector).val();
    });

    if (invalidField) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: invalidField.message,
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      // Show page preloader
      $("#page-preloader").show();

      var formData = new FormData($("#form-data")[0]);
      formData.append("create", true);
      formData.append("no_sub_items", getCheckboxVal("#no_sub_items"));
      formData.append("change_value", getCheckboxVal("#change_value"));
      formData.append("is_fixed_rate", getCheckboxVal("#is_fixed_rate"));

      if (croppedBlob) {
        formData.append("equipment_image", croppedBlob, "equipment.jpg");
      }

      $.ajax({
        url: "ajax/php/equipment-master.php",
        type: "POST",
        data: formData,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function (result) {
          // Hide page preloader
          $("#page-preloader").hide();

          // Re-enable the button
          $("#create").prop("disabled", false);

          if (result.status === "success") {
            swal({
              title: "Success!",
              text: "Equipment added successfully!",
              type: "success",
              timer: 2000,
              showConfirmButton: false,
            });

            setTimeout(() => {
              window.location.href =
                "sub-equipment-master.php?equipment_id=" + result.equipment_id;
            }, 2000);
          } else if (result.status === "duplicate") {
            swal({
              title: "Duplicate Entry!",
              text: result.message,
              type: "warning",
              showConfirmButton: true,
            });
          } else {
            swal({
              title: "Error!",
              text: "Something went wrong.",
              type: "error",
              timer: 2000,
              showConfirmButton: false,
            });
          }
        },
        error: function (xhr, status, error) {
          // Hide page preloader
          $("#page-preloader").hide();

          // Re-enable the button
          $("#create").prop("disabled", false);

          console.error("AJAX Error:", status, error);
          console.error("Response:", xhr.responseText);

          swal({
            title: "Error!",
            text: "Failed to create equipment. Please check the console for details.",
            type: "error",
            showConfirmButton: true,
          });
        },
      });
    }

    return false;
  });

  // Update Equipment
  $("#update").click(function (event) {
    event.preventDefault();

    // Disable the button to prevent multiple submissions
    $("#update").prop("disabled", true);

    // Validation
    var invalidUpdateField = requiredFields.find(function (field) {
      if (field.selector === "#department" && $("#no_sub_items").is(":checked")) {
        return false;
      }
      return !$(field.selector).val();
    });

    if (invalidUpdateField) {
      $("#update").prop("disabled", false);
      swal({
        title: "Error!",
        text: invalidUpdateField.message,
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      // Show page preloader
      $("#page-preloader").show();

      var formData = new FormData($("#form-data")[0]);
      formData.append("update", true);
      formData.append("no_sub_items", getCheckboxVal("#no_sub_items"));
      formData.append("change_value", getCheckboxVal("#change_value"));
      formData.append("is_fixed_rate", getCheckboxVal("#is_fixed_rate"));

      if (croppedBlob) {
        formData.append("equipment_image", croppedBlob, "equipment.jpg");
      }

      $.ajax({
        url: "ajax/php/equipment-master.php",
        type: "POST",
        data: formData,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function (result) {
          // Hide page preloader
          $("#page-preloader").hide();

          if (result.status == "success") {
            swal({
              title: "Success!",
              text: "Equipment updated successfully!",
              type: "success",
              timer: 2500,
              showConfirmButton: false,
            });

            setTimeout(() => {
              window.location.reload();
            }, 2000);
          } else if (result.status === "duplicate") {
            // Re-enable the button
            $("#update").prop("disabled", false);
            swal({
              title: "Duplicate Entry!",
              text: result.message,
              type: "warning",
              showConfirmButton: true,
            });
          } else {
            // Re-enable the button
            $("#update").prop("disabled", false);
            swal({
              title: "Error!",
              text: "Something went wrong.",
              type: "error",
              timer: 2000,
              showConfirmButton: false,
            });
          }
        },
        error: function (xhr, status, error) {
          // Hide page preloader
          $("#page-preloader").hide();

          // Re-enable the button
          $("#update").prop("disabled", false);

          console.error("AJAX Error:", status, error);
          console.error("Response:", xhr.responseText);

          swal({
            title: "Error!",
            text: "Failed to update equipment. Please check the console for details.",
            type: "error",
            showConfirmButton: true,
          });
        },
      });
    }

    return false;
  });

  // Reset input fields
  $("#new").click(function (e) {
    e.preventDefault();
    $("#form-data")[0].reset();
    $("#equipment_id").val("");
    $("#no_sub_items").prop("checked", false);
    $("#change_value").prop("checked", false);
    $("#is_fixed_rate").prop("checked", false);
    $("#old_image_name").val("");
    $("#image_preview").attr("src", "assets/images/no-image.png");
    $("#update").hide();
    croppedBlob = null;
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }

    // Generate new code
    $.ajax({
      url: "ajax/php/equipment-master.php",
      type: "POST",
      data: { action: "get_new_code" },
      dataType: "JSON",
      success: function (result) {
        if (result.status === "success") {
          $("#code").val(result.code);
        }
      },
    });

    toggleAddSubButton();
  });

  // Header Add Sub Equipment button
  $(document).on("click", "#add-sub-equipment", function (e) {
    e.preventDefault();

    var equipmentId = $("#equipment_id").val();
    var itemName = $("#item_name").val();

    if (!equipmentId) {
      swal({
        title: "Error!",
        text: "Please select equipment first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    var url =
      "sub-equipment-master.php?equipment_id=" +
      equipmentId +
      "&equipment_name=" +
      encodeURIComponent(itemName || "");

    // ✅ OPEN IN NEW TAB
    window.open(url, "_blank");
  });

  // Delete Equipment
  $(document).on("click", ".delete-equipment", function (e) {
    e.preventDefault();

    // Disable the button to prevent multiple submissions
    $(".delete-equipment").prop("disabled", true);

    var equipmentId = $("#equipment_id").val();
    var itemName = $("#item_name").val();

    if (!equipmentId || equipmentId === "") {
      // Re-enable the button on validation error
      $(".delete-equipment").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please select equipment first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    swal(
      {
        title: "Are you sure?",
        text: "Do you want to delete equipment '" + itemName + "'?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel",
        closeOnConfirm: false,
      },
      function (isConfirm) {
        if (isConfirm) {
          // Show page preloader
          $("#page-preloader").show();

          $.ajax({
            url: "ajax/php/equipment-master.php",
            type: "POST",
            data: {
              id: equipmentId,
              delete: true,
            },
            dataType: "JSON",
            success: function (response) {
              // Hide page preloader
              $("#page-preloader").hide();

              // Re-enable the button
              $(".delete-equipment").prop("disabled", false);

              if (response.status === "success") {
                swal({
                  title: "Deleted!",
                  text: "Equipment has been deleted.",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false,
                });

                setTimeout(() => {
                  window.location.reload();
                }, 2000);
              } else {
                swal({
                  title: "Error!",
                  text: "Something went wrong.",
                  type: "error",
                  timer: 2000,
                  showConfirmButton: false,
                });
              }
            },
          });
        } else {
          // Re-enable the button if user cancels
          $(".delete-equipment").prop("disabled", false);
        }
      },
    );
  });

  // Image Preview and Cropping Logic
  $(document).on("change", "#equipment_image", function () {
    var files = this.files;
    var file;

    if (files && files.length > 0) {
      file = files[0];

      if (/^image\/\w+$/.test(file.type)) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $("#image-to-crop").attr("src", e.target.result);
          $("#cropModal").modal("show");
        };
        reader.readAsDataURL(file);
      } else {
        swal("Error", "Please choose an image file.", "error");
      }
    }
  });

  $("#cropModal")
    .on("shown.bs.modal", function () {
      cropper = new Cropper(document.getElementById("image-to-crop"), {
        aspectRatio: 1,
        viewMode: 1,
        autoCropArea: 1,
      });
    })
    .on("hidden.bs.modal", function () {
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
    });

  $("#crop-button").click(function () {
    if (cropper) {
      var canvas = cropper.getCroppedCanvas({
        width: 600,
        height: 600,
      });

      $("#image_preview").attr("src", canvas.toDataURL("image/jpeg"));
      $("#preview_text").hide();

      canvas.toBlob(function (blob) {
        croppedBlob = blob;
      }, "image/jpeg");

      $("#cropModal").modal("hide");
    }
  });
});
