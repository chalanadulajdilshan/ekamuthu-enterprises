jQuery(document).ready(function () {

  // Toggle Previous Customer fields
  $("#has_previous_customer").change(function() {
    if ($(this).is(":checked")) {
      $("#item_customer_code_section").show();
      $("#item_customer_name_section").show();
    } else {
      $("#item_customer_code_section").hide();
      $("#item_customer_name_section").hide();
      $("#item_customer_code").val("");
      $("#item_customer_name").val("");
      $("#item_customer_id").val("");
    }
  });

  // Store DAG-level customer values before modal opens when Previous Customer is enabled
  let dagCustomerBackup = { id: '', code: '', name: '' };
  let isItemCustomerSelection = false;
  
  // Handle new customer creation - also populate item fields if checkbox is checked
  $('#customerAddModal').on('hidden.bs.modal', function() {
    if ($("#has_previous_customer").is(":checked")) {
      // Small delay to ensure customer-master.js has populated the fields
      setTimeout(function() {
        const customerCode = $("#customer_code").val();
        const customerName = $("#customer_name").val();
        
        // If customer fields were just populated, copy to item fields
        if (customerCode && !dagCustomerBackup.code) {
          $("#item_customer_code").val(customerCode);
          $("#item_customer_name").val(customerName);
          // Customer ID might not be available from the form, so keep the one from response
        }
      }, 100);
    }
  });
  
  $('#customerModal').on('show.bs.modal', function() {
    // Check if Previous Customer checkbox is checked
    isItemCustomerSelection = $("#has_previous_customer").is(":checked");
    
    if (isItemCustomerSelection) {
      // Backup current DAG customer values
      dagCustomerBackup = {
        id: $("#customer_id").val() || '',
        code: $("#customer_code").val() || '',
        name: $("#customer_name").val() || ''
      };
    }
  });
  
  // Handle customer selection for item-level customer
  // This needs to run AFTER common.js handler, so we use a slight delay
  $('#customerModal').on('hidden.bs.modal', function() {
    // If this was an item customer selection
    if (isItemCustomerSelection && $("#item_customer_id").val()) {
      // Restore DAG-level customer fields to their original values
      setTimeout(function() {
        $("#customer_id").val(dagCustomerBackup.id);
        $("#customer_code").val(dagCustomerBackup.code);
        $("#customer_name").val(dagCustomerBackup.name);
      }, 50);
    }
    isItemCustomerSelection = false;
  });
  
  // Intercept customer table click when Previous Customer checkbox is checked
  $(document).on("click", "#customerTable tbody tr", function (e) {
    // Only process if Previous Customer checkbox is checked
    if ($("#has_previous_customer").is(":checked")) {
      try {
        var table = $("#customerTable").DataTable();
        if (table) {
          var data = table.row(this).data();
          
          if (data) {
            // Set item-level customer fields
            $("#item_customer_id").val(data.id);
            $("#item_customer_code").val(data.code);
            $("#item_customer_name").val(data.name);
          }
        }
      } catch (error) {
        console.log("Error selecting customer:", error);
      }
    }
    // Don't prevent default - let common.js handler run too
  });

  function loadDagItemsToTable(items) {
    $("#dagItemsBodyInvoice").empty();

    if (!items.length) {
      $("#dagItemsBodyInvoice").append(`
      <tr id="noDagItemRow">
        <td colspan="6" class="text-center text-muted">No items found</td>
      </tr>`);
      return;
    }

    items.forEach((item) => {
      const price = parseFloat(item.price) || 0;
      const qty = parseFloat(item.qty) || 0;
      const total = price * qty;

      const row = $(`
    <tr class="dag-item-row clickable-row">
      <td>
        ${item.vehicle_no}
        <input type="hidden" class="vehicle_no" value="${item.vehicle_no}">
      </td>
      <td>
        ${item.belt_title}
        <input type="hidden" class="belt_id" value="${item.belt_id}">
      </td>
      <td>
        ${item.barcode}
        <input type="hidden" class="barcode" value="${item.barcode}">
      </td>
      <td>
        ${qty}
        <input type="hidden" class="qty" value="${qty}">
      </td>
      <td>
        <input type="number" class="form-control form-control-sm price" value="${price}" readonly>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm total_amount" value="${total.toFixed(2)}" readonly>
      </td>
    </tr>
    `);

      // On row click → populate input fields
      row.on("click", function () {
        $("#vehicleNo").val(item.vehicle_no);
        $("#beltDesign").val(item.belt_id).trigger("change");
        $("#barcode").val(item.barcode);
        $("#quantity").val(qty);
        $("#casingCost").val(price);
        $("#vehicleNo").focus();
      });

      $("#dagItemsBodyInvoice").append(row);
    });
  }


  function resetDagInputs() {
    $("#beltDesign").val("").trigger("change");
    $("#sizeDesign").val("").trigger("change");
    $("#brand_id").val("").trigger("change");
    $("#serial_num1").val("");
    $("#has_previous_customer").prop("checked", false).trigger("change");
    $("#item_customer_code").val("");
    $("#item_customer_name").val("");
    $("#item_customer_id").val("");
  }

  function resetDagForm() {
    // Reset all form inputs
    $("#form-data")[0].reset();

    // Reset select2 dropdowns
    $("#department_id, #customer_id, #dag_company_id, #brand_id").val("").trigger("change");

    // Reset date inputs
    $("#received_date, #delivery_date, #customer_request_date, #company_issued_date, #company_delivery_date").val("");

    // Reset status to default
    $("#status").val("pending");

    // Hide update button, show create button
    $("#update").hide();
    $("#create").show();

    // Hide print button
    $("#print").hide();

    // Reset hidden fields
    $("#id").val("0");
    $("#dag_id").val("");

    // Clear any error messages
    $(".text-danger").remove();
  }


  function addDagItem() {
    try {
      const beltDesignId = $("#beltDesign").val();
      const beltDesignText = $("#beltDesign option:selected").text();
      const sizeDesignId = $("#sizeDesign").val();
      const sizeDesignText = $("#sizeDesign option:selected").text();
      
      // Safe handling of serial number
      const serialNum1Element = $("#serial_num1");
      const serialNum1 = serialNum1Element.length && serialNum1Element.val() ? serialNum1Element.val().trim() : "";

      console.log("Adding DAG item:", {
        beltDesignId, beltDesignText, sizeDesignId, sizeDesignText, serialNum1
      });

      // Check if required fields are filled
      if (!beltDesignId) {
        swal("Error!", "Please select Belt Design.", "error");
        return;
      }
      
      if (!serialNum1) {
        swal("Error!", "Please enter Serial Number.", "error");
        return;
      }

      // Get company field values with safe checks
      const companyElement = $("#dag_company_id");
      const companyId = companyElement.length ? (companyElement.val() || "") : "";
      const companyText = companyElement.length ? (companyElement.find("option:selected").text() || "") : "";
      
      const issuedDateElement = $("#company_issued_date");
      const issuedDate = issuedDateElement.length ? (issuedDateElement.val() || "") : "";
      
      const deliveryDateElement = $("#company_delivery_date");
      const deliveryDate = deliveryDateElement.length ? (deliveryDateElement.val() || "") : "";
      
      const receiptNoElement = $("#receipt_no");
      const receiptNo = receiptNoElement.length ? (receiptNoElement.val() || "") : "";
      
      const brandElement = $("#brand_id");
      const brandId = brandElement.length ? (brandElement.val() || "") : "";
      const brandText = brandElement.length ? (brandElement.find("option:selected").text() || "") : "";

      const jobNumberElement = $("#job_number");
      const jobNumber = jobNumberElement.length ? (jobNumberElement.val() || "") : "";
      
      const statusElement = $("#dag_status");
      const statusValue = statusElement.length ? (statusElement.val() || "") : "";
      const statusText = statusElement.length ? (statusElement.find("option:selected").text() || "") : "";

      // Get customer info if checkbox is checked
      const hasPreviousCustomer = $("#has_previous_customer").is(":checked");
      const itemCustomerId = hasPreviousCustomer ? $("#item_customer_id").val() : "";
      const itemCustomerCode = hasPreviousCustomer ? $("#item_customer_code").val() : "";
      const itemCustomerName = hasPreviousCustomer ? $("#item_customer_name").val() : "";

      const newRow = $(`
        <tr class="dag-item-row">
          <td>${beltDesignText}<input type="hidden" name="belt_design_id[]" class="belt_id" value="${beltDesignId}"></td>
          <td>${sizeDesignText}<input type="hidden" name="size_design_id[]" class="size_id" value="${sizeDesignId}"></td>
          <td>${serialNum1}<input type="hidden" name="serial_num1[]" class="serial_num1" value="${serialNum1}"></td>
          <td>${companyText}<input type="hidden" name="dag_company_id[]" value="${companyId}"></td>
          <td>${issuedDate}<input type="hidden" name="company_issued_date[]" value="${issuedDate}"></td>
          <td>${deliveryDate}<input type="hidden" name="company_delivery_date[]" value="${deliveryDate}"></td>
          <td>${receiptNo}<input type="hidden" name="receipt_no[]" value="${receiptNo}"></td>
          <td>${brandText}<input type="hidden" name="brand_id[]" class="brand_id" value="${brandId}"></td>
          <td>${jobNumber}<input type="hidden" name="job_number[]" value="${jobNumber}"></td>
          <td>${statusText}<input type="hidden" name="status[]" value="${statusValue}"></td>
          <td>${itemCustomerName || ''}<input type="hidden" name="item_customer_id[]" class="item_customer_id" value="${itemCustomerId}"></td>
          <td>
            <button type="button" class="btn btn-warning btn-sm edit-item">Edit</button>
            <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
          </td>
        </tr>
      `);

      console.log("Appending row to #dagItemsBody");
      console.log("Table body exists:", $("#dagItemsBody").length > 0);
      console.log("Current rows before append:", $("#dagItemsBody tr").length);
      
      // Hide the "no items" row first
      $("#noDagItemRow").hide();
      
      // Then append the new row
      $("#dagItemsBody").append(newRow);
      
      console.log("Current rows after append:", $("#dagItemsBody tr").length);
      console.log("DAG item rows:", $(".dag-item-row").length);
      
      // Reset the form inputs
      resetDagInputs();

      // Focus back to belt design for next entry
      $("#beltDesign").focus();
      
      // Show success message
      console.log("Item added successfully");
      
    } catch (error) {
      console.error("Error in addDagItem:", error);
      swal("Error!", "An error occurred while adding the item. Please check the console for details.", "error");
    }
  }



  $("#addDagItemBtn").click(function (e) {
    e.preventDefault();
    addDagItem();
  });


  $("#beltDesign, #sizeDesign, #serial_num1").on("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      addDagItem();
    }
  });

  $(document).on("click", ".remove-item", function () {
    $(this).closest("tr").remove();

  });

  $("#create").click(function (event) {
    event.preventDefault();

    if (!$("#ref_no").val().trim()) {
      swal({
        title: "Error!",
        text: "Reference Number is required to proceed.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    if (!$("#received_date").val().trim()) {
      swal({
        title: "Error!",
        text: "Please enter the Received Date to continue.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    if (!$("#customer_request_date").val().trim()) {
      swal({
        title: "Error!",
        text: "Customer Request Date is needed for scheduling.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    let dagItems = [];
    $(".dag-item-row").each(function () {
      dagItems.push({
        belt_id: $(this).find("input[name='belt_design_id[]']").val(),
        size_id: $(this).find("input[name='size_design_id[]']").val(),
        serial_num1: $(this).find("input[name='serial_num1[]']").val(),
        dag_company_id: $(this).find("input[name='dag_company_id[]']").val(),
        company_issued_date: $(this).find("input[name='company_issued_date[]']").val(),
        company_delivery_date: $(this).find("input[name='company_delivery_date[]']").val(),
        receipt_no: $(this).find("input[name='receipt_no[]']").val(),
        brand_id: $(this).find("input[name='brand_id[]']").val(),
        job_number: $(this).find("input[name='job_number[]']").val(),
        status: $(this).find("input[name='status[]']").val(),
        customer_id: $(this).find("input[name='item_customer_id[]']").val() || null
      });
    });

    if (dagItems.length === 0) {
      swal({
        title: "Error!",
        text: "Please add at least one DAG item before saving.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    $(".someBlock").preloader();
    const formData = new FormData($("#form-data")[0]);
    formData.append("create", true); // Create flag
    formData.append("dag_items", JSON.stringify(dagItems));

    $.ajax({
      url: "ajax/php/create-dag.php",
      type: "POST",
      data: formData,
      async: false,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (result) {
        console.log("DAG Create Response:", result);
        $(".someBlock").preloader("remove");
        if (result.status === "success") {
          // Reset the form and clear all inputs
          resetDagForm();

          // Clear DAG items table
          $("#dagItemsBody").empty();
          $("#dagItemsBody").append(`
            <tr id="noDagItemRow">
              <td colspan="12" class="text-center text-muted">No items added</td>
            </tr>
          `);

          // Clear invoice items table
          $("#dagItemsBodyInvoice").empty();
          $("#dagItemsBodyInvoice").append(`
            <tr id="noDagItemRow">
              <td colspan="6" class="text-center text-muted">No items found</td>
            </tr>
          `);

          // Reset totals
          $("#subTotal, #finalTotal").val("0.00");

          // Show success message and then fully reload the page
          swal("Success!", "DAG created successfully!", "success");
          setTimeout(function () {
            location.reload();
          }, 2000);
        } else {
          swal("Error!", result.message || "Something went wrong while creating.", "error");
        }
      },
      error: function (xhr, status, error) {
        $(".someBlock").preloader("remove");
        console.error("AJAX Error:", status, error);
        console.error("Response:", xhr.responseText);
        swal("Error!", "Failed to create DAG. Please check console for details.", "error");
      }
    });
  });



  $("#update").click(function (event) {
    event.preventDefault();
    if (!$("#ref_no").val().trim()) {
      swal({
        title: "Error!",
        text: "Reference Number is required to proceed.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    if (!$("#received_date").val().trim()) {
      swal({
        title: "Error!",
        text: "Please enter the Received Date to continue.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }



    if (!$("#customer_request_date").val().trim()) {
      swal({
        title: "Error!",
        text: "Customer Request Date is needed for scheduling.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    if (!$("#remark").val().trim()) {
      swal({
        title: "Error!",
        text: "Dag Remark added.!",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }


    $(".someBlock").preloader();
    const formData = new FormData($("#form-data")[0]);
    formData.append("update", true);
    formData.append("dag_id", $("#id").val());

    let dagItems = [];
    $(".dag-item-row").each(function () {
      dagItems.push({
        belt_id: $(this).find("input[name='belt_design_id[]']").val(),
        size_id: $(this).find("input[name='size_design_id[]']").val(),
        serial_num1: $(this).find("input[name='serial_num1[]']").val(),
        barcode: $(this).find(".barcode").val(),
        dag_company_id: $(this).find("input[name='dag_company_id[]']").val(),
        company_issued_date: $(this).find("input[name='company_issued_date[]']").val(),
        company_delivery_date: $(this).find("input[name='company_delivery_date[]']").val(),
        receipt_no: $(this).find("input[name='receipt_no[]']").val(),
        brand_id: $(this).find("input[name='brand_id[]']").val(),
        job_number: $(this).find("input[name='job_number[]']").val(),
        status: $(this).find("input[name='status[]']").val(),
        customer_id: $(this).find("input[name='item_customer_id[]']").val() || null
      });

    });
    formData.append("dag_items", JSON.stringify(dagItems));

    $.ajax({
      url: "ajax/php/create-dag.php",
      type: "POST",
      data: formData,
      async: false,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "JSON",
      success: function (result) {
        $(".someBlock").preloader("remove");
        if (result.status === "success") {
          swal("Success!", "DAG updated successfully!", "success");
          setTimeout(() => location.reload(), 2000);
        } else {
          swal("Error!", "Something went wrong while updating.", "error");
        }
      },
    });
  });


  $(document).on("click", ".edit-item", function () {
    const row = $(this).closest("tr");

    $("#beltDesign").val(row.find(".belt_id").val()).trigger("change");
    $("#sizeDesign").val(row.find(".size_id").val()).trigger("change");
    $("#serial_num1").val(row.find(".serial_num1").val());
    $("#brand_id").val(row.find(".brand_id").val()).trigger("change");

    row.remove();

    $("#beltDesign").focus();
  });


  $(document).on("click", "#searchDagBtn", function () {
    loadDagTable();
  });

  $(document).on("keypress", "#dagSearchInput", function (e) {
    if (e.which === 13) { // Enter key
      loadDagTable();
    }
  });

  $('#mainDagModel').on('shown.bs.modal', function () {
    loadDagTable();
  });

  function loadDagTable() {
    const searchTerm = $("#dagSearchInput").val().trim();

    $.ajax({
      url: "ajax/php/create-dag.php",
      type: "POST",
      data: { load_dags: true, search: searchTerm },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          $("#mainDagTableBody").html(response.html);
        } else {
          $("#mainDagTableBody").html('<tr><td colspan="7" class="text-center text-muted">No DAGs found</td></tr>');
        }
      },
      error: function () {
        $("#mainDagTableBody").html('<tr><td colspan="7" class="text-center text-danger">Error loading DAGs</td></tr>');
      }
    });
  }

  $(document).on("click", ".select-dag", function () {
    const data = $(this).data();

    $("#id").val(data.id);
    $("#dag_id").val(data.id);
    $("#ref_no").val(data.ref_no);
    $("#job_number").val(data.job_number);
    $("#department_id").val(data.department_id).trigger("change");
    $("#customer_id").val(data.customer_id).trigger("change");


    $("#customer_code").val(data.customer_code);
    $("#customer_name").val(data.customer_name);
    $("#vehicle_no").val(data.vehicle_no);

    $("#received_date").val(data.received_date);
    $("#delivery_date").val(data.delivery_date);
    $("#customer_request_date").val(data.customer_request_date);
    $("#remark").val(data.remark);

    $("#create").hide();
    $("#dagModel").modal("hide");
    $("#mainDagModel").modal("hide");

    $("#noDagItemRow").hide();
    $("#invoiceTable").hide();
    $("#dagTableHide").show();
    $("#addItemTable").hide();
    $("#quotationTableHide").hide();



    $("#dagItemsBody").empty();
    $("#print").data("dag-id", data.id);
    $("#print").show();
    $("#update").show();
    $.ajax({
      url: "ajax/php/create-dag.php",
      type: "POST",
      data: { dag_id: data.id },
      dataType: "json",
      success: function (res) {
        if (res.status === "success") {
          const items = res.data;
          console.log("Total items received:", items.length);
          console.log("Items data:", items);
          
          // Clear the table first
          $("#dagItemsBody").empty();
          console.log("Table cleared, current rows:", $("#dagItemsBody tr").length);
          
          items.forEach((item, index) => {
            console.log(`Processing item ${index + 1}:`, {
              serial: item.serial_number,
              company: item.dag_company_name,
              brand: item.brand_name,
              job: item.job_number
            });
            
            try {
              const row = `
  <tr class="dag-item-row">
    <td>${item.belt_title}<input type="hidden" name="belt_design_id[]" class="belt_id" value="${item.belt_id}"></td>
    <td>${item.size_name || ''}<input type="hidden" name="size_design_id[]" class="size_id" value="${item.size_id}"></td>
    <td>${item.serial_number || ''}<input type="hidden" name="serial_num1[]" class="serial_num1" value="${item.serial_number}"></td>
    <td>${item.dag_company_name || ''}<input type="hidden" name="dag_company_id[]" value="${item.dag_company_id}"></td>
    <td>${item.company_issued_date || ''}<input type="hidden" name="company_issued_date[]" value="${item.company_issued_date}"></td>
    <td>${item.company_delivery_date || ''}<input type="hidden" name="company_delivery_date[]" value="${item.company_delivery_date}"></td>
    <td>${item.receipt_no || ''}<input type="hidden" name="receipt_no[]" value="${item.receipt_no}"></td>
    <td>${item.brand_name || ''}<input type="hidden" name="brand_id[]" class="brand_id" value="${item.brand_id}"></td>
    <td>${item.job_number || ''}<input type="hidden" name="job_number[]" value="${item.job_number}"></td>
    <td>${item.status || ''}<input type="hidden" name="status[]" value="${item.status}"></td>
    <td>${item.customer_name || ''}<input type="hidden" name="item_customer_id[]" class="item_customer_id" value="${item.customer_id || ''}"></td>
    <td>
      <button type="button" class="btn btn-warning btn-sm edit-item">Edit</button>
      <button type="button" class="btn btn-sm btn-danger remove-item">Remove</button>
    </td>
  </tr>`;

              console.log(`About to append row ${index + 1}`);
              $("#dagItemsBody").append(row);
              console.log(`Rows in table after appending item ${index + 1}:`, $("#dagItemsBody tr").length);
              console.log(`DAG item rows:`, $(".dag-item-row").length);

              const price = parseFloat(item.casing_cost) || 0;
              const qty = parseFloat(item.qty) || 0;
              const total = price * qty;

              const invoiceRow = `
                <tr class="dag-item-row clickable-row">
                  <td>${$("#vehicle_no").val()}</td>
                  <td>${item.belt_title}</td>
                  <td>${item.barcode || ''}</td>
                  <td>${qty}</td>
                  <td><input type="number" class="form-control form-control-sm price" value="${price}"></td>
                  <td><input type="text" class="form-control form-control-sm totalPrice" value="${total.toFixed(2)}" readonly>
                  <input type="hidden" class="dag_item_id" value="${item.id}" />
                  </td>
                </tr>`;
              $("#dagItemsBodyInvoice").append(invoiceRow);
              
            } catch (error) {
              console.error(`Error processing item ${index + 1}:`, error);
            }
          });
          
          console.log("Final table state:");
          console.log("Total rows in dagItemsBody:", $("#dagItemsBody tr").length);
          console.log("Total dag-item-row elements:", $(".dag-item-row").length);
          
          calculateTotals();

        } else {
          swal("Warning!", "No items returned for this DAG.", "warning");
        }
      },
      error: function () {
        swal("Error!", "Failed to load DAG items.", "error");
      },
    });
  });

  $(document).on("click", "#print", function (e) {
    e.preventDefault();

    const dagId = $(this).data("dag-id");
    if (!dagId) {
      swal("Error!", "No DAG selected to print.", "error");
      return;
    }

    // Redirect to print page
    window.open(`dag-receipt-print.php?id=${dagId}`, "_blank");
  });


  function calculateTotals() {
    let subTotal = 0;

    $("#dagItemsBodyInvoice tr").each(function () {
      const price = parseFloat($(this).find('.price').val()) || 0;
      const qty = parseFloat($(this).find("td:eq(3)").text()) || 0;
      const rowTotal = price * qty;


      // Update totalPrice input (using class, not id)
      $(this).find('input.totalPrice').val(rowTotal.toFixed(2));

      subTotal += rowTotal;
    });

    const discountStr = $("#disTotal").val().replace(/,/g, '').trim();
    const discountPercent = parseFloat(discountStr) || 0;
    const discountAmount = (subTotal * discountPercent) / 100;

    const finalTotal = subTotal - discountAmount;

    $("#subTotal").val(subTotal.toFixed(2));
    $("#finalTotal").val(finalTotal.toFixed(2));

    if (finalTotal < subTotal) {
      $("#finalTotal").css("color", "red");
    } else {
      $("#finalTotal").css("color", "");
    }
  }

  // Handle price input changes dynamically
  $(document).on('input', '.price', function () {
    const row = $(this).closest('tr');
    const price = parseFloat($(this).val()) || 0;
    const qty = parseFloat(row.find("td:eq(3)").text()) || 0;

    const total = price * qty;
    row.find('.totalPrice').val(total.toFixed(2));

    // Enable discount input if needed
    $("#disTotal").prop("disabled", false);

    calculateTotals();
  });

  // Discount input triggers recalculation
  $(document).on("input", "#disTotal", function () {
    setTimeout(() => {
      calculateTotals();
    }, 10);
  });

  // Delete DAG functionality
  $(".delete-dag").click(function (event) {
    event.preventDefault();
    
    const dagId = $("#id").val();
    if (!dagId || dagId === "0") {
      swal("Error!", "Please select a DAG to delete.", "error");
      return;
    }

    // Show confirmation dialog
    swal({
      title: "Are you sure?",
      text: "Once deleted, you will not be able to recover this DAG!",
      type: "warning",
      showCancelButton: true,
      confirmButtonColor: "#DD6B55",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "No, cancel!",
      closeOnConfirm: false,
      closeOnCancel: false
    }, function(isConfirm) {
      if (isConfirm) {
        // User confirmed, proceed with deletion
        $(".someBlock").preloader();
        
        $.ajax({
          url: "ajax/php/create-dag.php",
          type: "POST",
          data: { delete: true, dag_id: dagId },
          dataType: "JSON",
          success: function (result) {
            $(".someBlock").preloader("remove");
            if (result.status === "success") {
              swal("Deleted!", "The DAG has been deleted.", "success");
              // Reset form and redirect or reload
              resetDagForm();
              setTimeout(() => {
                location.reload();
              }, 2000);
            } else {
              swal("Error!", result.message || "Failed to delete DAG.", "error");
            }
          },
          error: function () {
            $(".someBlock").preloader("remove");
            swal("Error!", "An error occurred while deleting the DAG.", "error");
          }
        });
      } else {
        // User cancelled
        swal("Cancelled", "The DAG is safe :)", "error");
      }
    });
  });



});
