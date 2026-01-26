jQuery(document).ready(function () {
  //windows loder
  loadCustomer();

  //number formate
  function formatPriceInput(inputField) {
    let inputValue = inputField.value;

    inputValue = inputValue.replace(/[^\d.]/g, "");

    // If the input contains a decimal point, make sure it has only one
    let [integerPart, decimalPart] = inputValue.split(".");
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ","); // Add commas for thousands

    if (decimalPart) {
      decimalPart = decimalPart.substring(0, 2); // Limit to 2 decimal places
    }

    // Reconstruct the formatted price
    let formattedPrice = decimalPart
      ? `${integerPart}.${decimalPart}`
      : integerPart;

    inputField.value = formattedPrice;
  }

  document.querySelectorAll(".number-format").forEach(function (inputField) {
    inputField.addEventListener("input", function () {
      formatPriceInput(this);
    });
  });

  //GET DISTRICT NAME
  $("#province").change(function () {
    $(".someBlock").preloader(); // Show preloader

    var province = $(this).val(); // Get selected province

    $("#district").empty(); // Clear existing district options

    $.ajax({
      url: "ajax/php/district.php",
      type: "POST",
      data: {
        province: province,
        action: "GET_DISTRICT_BY_PROVINCE",
      },
      dataType: "JSON",
      success: function (jsonStr) {
        $(".someBlock").preloader("remove"); // Remove preloader

        var html = '<option value=""> - Select District - </option>';
        $.each(jsonStr, function (i, data) {
          html += '<option value="' + data.id + '">' + data.name + "</option>";
        });

        $("#district").html(html); // Set new options
      },
      error: function () {
        $(".someBlock").preloader("remove"); // Remove preloader on error
        swal({
          title: "Error!",
          text: "Failed to load districts.",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
      },
    });
  });

  // add customers for same formate
  $("#customerModal").on("shown.bs.modal", function () {
    loadCustomerTable();
  });

  //loard customers all
  function loadCustomerTable() {
    // Destroy if already initialized
    if ($.fn.DataTable.isDataTable("#customerTable")) {
      $("#customerTable").DataTable().destroy();
    }

    $("#customerTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/customer-master.php",
        type: "POST",
        data: function (d) {
          d.filter = true;
          d.category = 1;
        },
        dataSrc: function (json) {
          return json.data;
        },
        error: function (xhr) {
          console.error("Server Error Response:", xhr.responseText);
        },
      },
      columns: [
        { data: "key", title: "#ID" },
        { data: "code", title: "Code" },
        { data: "name", title: "Name" },
        { data: "mobile_number", title: "Mobile Number" },
        { data: "email", title: "Email" },
        { data: "vat_no", title: "VAT" },
        { data: "outstanding", title: "Outstanding" },
      ],
      order: [[0, "desc"]],
      pageLength: 100,
    });

    $("#customerTable tbody").on("click", "tr", function () {
      var data = $("#customerTable").DataTable().row(this).data();

      if (data) {
        $("#customer_id").val(data.id);
        $("#customer_code").val(data.code);
        $("#customer_name").val(data.name);
        $("#customer_address").val(data.address);
        $("#customer_mobile").val(data.mobile_number);
        $("#customer_vat_no").val(data.vat_no);
        $("#customerModal").modal("hide");
        $("#outstandingInvoiceAmount").val(data.outstanding);

        let total =
          (parseFloat(toNumber($("#outstandingInvoiceAmount").val())) || 0) +
          (parseFloat(toNumber($("#returnChequeAmount").val())) || 0) +
          (parseFloat(toNumber($("#pendingChequeAmount").val())) || 0) +
          (parseFloat(toNumber($("#psdChequeSettlements").val())) || 0);

        $("#totalAmount").val(total.toFixed(2));
      }
    });
  }

  function toNumber(val) {
    return parseFloat(val.replace(/,/g, "")) || 0;
  }

  //get first row cash sales customer
  function loadCustomer() {
    $.ajax({
      url: "ajax/php/customer-master.php",
      method: "POST",
      data: { action: "get_first_customer" }, // you can customize this key/value
      dataType: "json",
      success: function (data) {
        if (!data.error) {
          $("#customer_id").val(data.customer_id);
          $("#customer_code").val(data.customer_code);
          $("#customer_name").val(data.customer_name);
          $("#customer_address").val(data.customer_address);
          $("#customer_mobile").val(data.mobile_number); // adjust key if needed
        } else {
          console.warn("No customer found");
        }
      },
      error: function () {
        console.error("AJAX request failed.");
      },
    });
  }

  //loard supliers model
  $("#supplierModal").on("shown.bs.modal", function () {
    loadSupplierTable();
  });

  //loard supliers
  function loadSupplierTable() {
    // Destroy if already initialized
    if ($.fn.DataTable.isDataTable("#supplierTable")) {
      $("#supplierTable").DataTable().destroy();
    }

    $("#supplierTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/customer-master.php",
        type: "POST",
        data: function (d) {
          d.filter = true;
          d.category = [2, 3]; // send as array
        },
        dataSrc: function (json) {
          return json.data;
        },
        error: function (xhr) {
          console.error("Server Error Response:", xhr.responseText);
        },
      },
      columns: [
        { data: "id", title: "#ID" },
        { data: "code", title: "Code" },
        { data: "name", title: "Name" },
        { data: "mobile_number", title: "Mobile" },
        { data: "email", title: "Email" },
        { data: "credit_limit", title: "Credit Limit" },
        { data: "vat_no", title: "Is Vat" },
        { data: "status_label", title: "Status" },
      ],
      order: [[0, "desc"]],
      pageLength: 100,
    });

    $("#supplierTable tbody").on("click", "tr", function () {
      var data = $("#supplierTable").DataTable().row(this).data();

      if (data) {
        // Populate supplier master form fields
        $("#customer_id").val(data.id || "");
        $("#code").val(data.code || "");
        $("#name").val(data.name || "");
        $("#address").val(data.address || "");
        $("#mobile_number").val(data.mobile_number || "");
        $("#mobile_number_2").val(data.mobile_number_2 || "");
        $("#email").val(data.email || "");
        $("#contact_person").val(data.contact_person || "");
        $("#contact_person_number").val(data.contact_person_number || "");
        // Strip commas from formatted numbers for number inputs
        $("#credit_limit").val(data.credit_limit ? data.credit_limit.replace(/,/g, "") : "");
        $("#outstanding").val(data.outstanding ? data.outstanding.replace(/,/g, "") : "");
        $("#remark").val(data.remark || "");

        // Checkbox (is_active)
        $("#is_active").prop("checked", data.status == 1);

        // Show update button, hide create button
        $("#create").hide();
        $("#update").show();
      }

      $("#supplierModal").modal("hide");
    });
  }

  // When the modal is shown, load the DataTable
  $("#AllCustomerModal").on("shown.bs.modal", function () {
    loadAllCustomerTable();
  });

  function loadAllCustomerTable() {
    // Destroy if already initialized
    if ($.fn.DataTable.isDataTable("#allCustomerTable")) {
      $("#allCustomerTable").DataTable().destroy();
    }

    $("#allCustomerTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/customer-master.php",
        type: "POST",
        data: function (d) {
          d.filter = true;
        },
        dataSrc: function (json) {
          return json.data;
        },
        error: function (xhr) {
          console.error("Server Error Response:", xhr.responseText);
        },
      },
      columns: [
        { data: "key", title: "#ID" },
        { data: "code", title: "Code" },
        {
          data: "is_company",
          title: "Type",
          render: function (data, type, row) {
            return data == 1 ? '<span class="badge bg-primary">Company</span>' : '<span class="badge bg-success">Individual</span>';
          }
        },
        { data: "name", title: "Name" },
        { data: "nic", title: "NIC" },
        { data: "mobile_number", title: "Mobile" },
        { data: "address", title: "Address" },
        { data: "old_outstanding", title: "Outstanding" },
      ],
      order: [[0, "desc"]],
      pageLength: 100,
    });

    // Row click event to populate form and close modal
    $("#allCustomerTable tbody")
      .off("click")
      .on("click", "tr", function () {
        var data = $("#allCustomerTable").DataTable().row(this).data();

        if (data) {
          $("#id").val(data.id || "");
          $("#customer_id").val(data.id || "");
          $("#code").val(data.code || "");
          $("#name").val(data.name || "");
          $("#address").val(data.address || "");
          $("#mobile_number").val(data.mobile_number || "");
          $("#mobile_number_2").val(data.mobile_number_2 || "");
          $("#old_outstanding").val(data.old_outstanding || "");
          $("#remark").val(data.remark || "");
          $("#nic").val(data.nic || "");
          $("#water_bill_no").val(data.utility_bill_no || "");
          $("#utility_bill_no").val(data.utility_bill_no || "");

          // New fields - workplace address and guarantor details
          $("#workplace_address").val(data.workplace_address || "");
          $("#guarantor_name").val(data.guarantor_name || "");
          $("#guarantor_nic").val(data.guarantor_nic || "");
          $("#guarantor_address").val(data.guarantor_address || "");
          $("#guarantor_photo_image_1").val(data.guarantor_photo || "");

          // Company checkbox and fields
          $("#is_company").prop("checked", data.is_company == 1);
          if (data.is_company == 1) {
            $("#company_fields").show();
            $("#company_name").val(data.company_name || "");
          } else {
            $("#company_fields").hide();
            $("#company_name").val("");
          }

          // Load image previews if they exist
          loadImagePreview("nic", 1, data.nic_image_1);
          loadImagePreview("nic", 2, data.nic_image_2);
          loadImagePreview("water_bill", 1, data.utility_bill_image);
          loadImagePreview("guarantor_nic", 1, data.guarantor_nic_image_1);
          loadImagePreview("guarantor_nic", 2, data.guarantor_nic_image_2);
          loadImagePreview("guarantor_photo", 1, data.guarantor_photo);
          loadImagePreview("customer_photo", 1, data.customer_photo);
          loadDocumentPreview("po_document", data.company_document);

          $("#create").hide();
          $("#update").show();
          $("#btnAddDescription").show(); // Show Detail Button

          // Blacklist UI
          if (data.is_blacklisted == 1) {
            $("#blacklist-alert").show().css('display', 'flex'); // Ensure flex is applied
            $("#blacklist-reason-display").text(data.blacklist_reason || "No reason provided");
            $("#blacklist-btn").html('<i class="uil uil-check-circle me-1"></i> Remove from Blacklist').removeClass('btn-dark').addClass('btn-success');
            $("#blacklist-btn").data('status', 1);
          } else {
            $("#blacklist-alert").hide();
            $("#blacklist-btn").html('<i class="uil uil-ban me-1"></i> Blacklist').removeClass('btn-success').addClass('btn-dark');
            $("#blacklist-btn").data('status', 0);
          }
          $("#blacklist-btn").show();

          // Close the modal
          $("#AllCustomerModal").modal("hide");
        }
      });
  }

  // Helper function to load image preview (supports both file paths and base64)
  function loadImagePreview(fieldName, imageNum, imageData) {
    const previewId = `#${fieldName}_preview`;
    const hiddenInputId = `#${fieldName}_image_${imageNum}`;

    // Store data in hidden input (could be path or base64)
    $(hiddenInputId).val(imageData || "");

    if (imageData && imageData.length > 0) {
      // Check if it's a file path or base64
      const isFilePath = imageData.startsWith("uploads/");
      const isPdf = imageData.endsWith(".pdf") || imageData.startsWith("data:application/pdf");

      // Construct the image source
      const imageSrc = isFilePath ? imageData : imageData;

      let previewHtml = "";

      if (isPdf) {
        previewHtml = `
          <div class="position-relative border rounded p-2 clickable-preview" style="background: #f8f9fa; cursor: pointer;" onclick="viewDocument('${imageSrc}', true)" title="Click to view PDF">
            <div class="d-flex align-items-center">
              <i class="uil uil-file-alt text-danger" style="font-size: 24px;"></i>
              <small class="ms-2">PDF Document</small>
              <i class="uil uil-external-link-alt ms-auto text-muted"></i>
            </div>
          </div>
        `;
      } else {
        previewHtml = `
          <div class="position-relative clickable-preview" style="width: 60px; cursor: pointer;" onclick="viewDocument('${imageSrc}', false)" title="Click to view image">
            <img src="${imageSrc}" class="img-fluid rounded border" style="width: 60px; height: 40px; object-fit: cover;">
            <div class="position-absolute" style="top: 0; right: 0; background: rgba(0,0,0,0.5); border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;">
              <i class="uil uil-search-plus text-white" style="font-size: 10px;"></i>
            </div>
          </div>
        `;
      }

      // Append to existing preview or replace
      if (imageNum === 1) {
        $(previewId).html(previewHtml);
      } else {
        $(previewId).append(previewHtml);
      }
    } else if (imageNum === 1) {
      $(previewId).empty();
    }
  }

  // Helper function to load document preview (PO/Letterhead) - supports file paths and base64
  function loadDocumentPreview(fieldName, documentData) {
    const previewId = `#${fieldName}_preview`;
    const hiddenInputId = `#${fieldName}_image_1`;
    const nameInputId = `#${fieldName}_name`;

    // Store data in hidden input
    $(hiddenInputId).val(documentData || "");

    if (documentData && documentData.length > 0) {
      const isFilePath = documentData.startsWith("uploads/");
      const isPdf = documentData.endsWith(".pdf") || documentData.startsWith("data:application/pdf");

      // Extract filename for display
      let displayName = "Document";
      if (isFilePath) {
        displayName = documentData.split('/').pop();
      } else {
        displayName = isPdf ? "Document.pdf" : "Document.jpg";
      }
      $(nameInputId).val(displayName);

      const imageSrc = isFilePath ? documentData : documentData;

      let previewHtml = "";
      if (isPdf) {
        previewHtml = `
          <div class="position-relative border rounded p-2 clickable-preview" style="background: #f8f9fa; cursor: pointer;" onclick="viewDocument('${imageSrc}', true)" title="Click to view PDF">
            <div class="d-flex align-items-center">
              <i class="uil uil-file-alt text-danger" style="font-size: 24px;"></i>
              <small class="ms-2">PDF Document</small>
              <i class="uil uil-external-link-alt ms-auto text-muted"></i>
            </div>
          </div>
        `;
      } else {
        previewHtml = `
          <div class="position-relative clickable-preview" style="width: 60px; cursor: pointer;" onclick="viewDocument('${imageSrc}', false)" title="Click to view image">
            <img src="${imageSrc}" class="img-fluid rounded border" style="width: 60px; height: 40px; object-fit: cover;">
            <div class="position-absolute" style="top: 0; right: 0; background: rgba(0,0,0,0.5); border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;">
              <i class="uil uil-search-plus text-white" style="font-size: 10px;"></i>
            </div>
          </div>
        `;
      }
      $(previewId).html(previewHtml);
    } else {
      $(previewId).empty();
      $(nameInputId).val("");
    }
  }

  // When the modal is shown, load the DataTable
  $("#AllSupplierModal").on("shown.bs.modal", function () {
    loadAllSupplierTable();
  });

  function loadAllSupplierTable() {
    // Destroy if already initialized
    if ($.fn.DataTable.isDataTable("#allSupplierTable")) {
      $("#allSupplierTable").DataTable().destroy();
    }

    $("#allSupplierTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajax/php/customer-master.php",
        type: "POST",
        data: function (d) {
          d.filter = true;
          d.category = [2, 3];
        },
        dataSrc: function (json) {
          return json.data;
        },
        error: function (xhr) {
          console.error("Server Error Response:", xhr.responseText);
        },
      },
      columns: [
        { data: "id", title: "#ID" },
        { data: "code", title: "Code" },
        { data: "name", title: "Name" },
        { data: "mobile_number", title: "Mobile" },
        { data: "email", title: "Email" },
        { data: "credit_limit", title: "Credit Limit" },
        { data: "vat_no", title: "Is Vat" },
        { data: "status_label", title: "Status" },
      ],
      order: [[0, "desc"]],
      pageLength: 100,
    });

    // Row click event to populate form and close modal
    $("#allSupplierTable tbody")
      .off("click")
      .on("click", "tr", function () {
        var data = $("#allSupplierTable").DataTable().row(this).data();

        if (data) {
          $("#customer_id").val(data.id || "");
          $("#code").val(data.code || "");

          // Handle name splitting for suppliers
          if (data.name_2 && data.name_2.trim() !== "") {
            // If name_2 exists, use name as company name and name_2 as supplier name
            $("#name").val(data.name || "");
            $("#name_2").val(data.name_2 || "");
          } else {
            // If name_2 is empty, split the combined name
            var fullName = data.name || "";
            var nameParts = fullName.trim().split(" ");
            if (nameParts.length > 1) {
              // First part as company name, rest as supplier name
              $("#name").val(nameParts[0]);
              $("#name_2").val(nameParts.slice(1).join(" "));
            } else {
              // If only one part, put it in company name
              $("#name").val(fullName);
              $("#name_2").val("");
            }
          }

          $("#address").val(data.address || "");
          $("#mobile_number").val(data.mobile_number || "");
          $("#mobile_number_2").val(data.mobile_number_2 || "");
          $("#email").val(data.email || "");
          $("#contact_person").val(data.contact_person || "");
          $("#contact_person_number").val(data.contact_person_number || "");

          // Checkbox (is_active), assuming 1 = checked, 0 = unchecked
          $("#is_active").prop("checked", data.status == 1);

          $("#credit_limit").val(data.credit_limit || "");
          $("#outstanding").val(data.outstanding || "");
          $("#old_outstanding").val(data.old_outstanding || "");
          $("#overdue").val(data.overdue || "");
          $("#vat_no").val(data.vat_no || "");
          $("#svat_no").val(data.svat_no || "");

          // For select inputs, set value and trigger change to update select2 UI if used
          $("#category")
            .val(data.category_id || "")
            .trigger("change");
          $("#province")
            .val(data.province_id || "")
            .trigger("change");
          $("#district")
            .val(data.district_id || "")
            .trigger("change");
          $("#vat_group")
            .val(data.vat_group || "")
            .trigger("change");

          $("#remark").val(data.remark || "");
          $("#create").hide();
          $("#update").show();
          // Close the modal
          $("#AllSupplierModal").modal("hide");
        }
      });
  }

  //-----------------SALES INVOICE LOARD DATA START---------------
  // Load latest 10 invoices on page load
  function loadLatestInvoices() {
    $.ajax({
      url: "ajax/php/sales-invoice.php",
      type: "POST",
      data: { action: "latest" }, // backend will handle this
      dataType: "json",
      success: function (response) {
        renderInvoices(response.data);
      },
      error: function (xhr) {
        $("#invoiceTableBody").html(
          `< tr > <td colspan="6" class="text-center text-danger">Error loading data</td></tr > `
        );
        console.error(xhr.responseText);
      },
    });
  }

  // Search invoices
  function searchInvoices(query) {
    $.ajax({
      url: "ajax/php/sales-invoice.php",
      type: "POST",
      data: { action: "search", q: query },
      dataType: "json",
      success: function (response) {
        renderInvoices(response.data);
      },
      error: function (xhr) {
        $("#invoiceTableBody").html(
          `< tr > <td colspan="6" class="text-center text-danger">Error loading search results</td></tr > `
        );
        console.error(xhr.responseText);
      },
    });
  }

  // Render table rows
  function renderInvoices(invoices) {
    console.log(invoices);
    let rows = "";
    if (invoices.length > 0) {
      invoices.forEach((inv) => {
        const isCancelled = inv.is_cancel == 1;
        rows += `
    < tr data - id="${inv.id}" ${isCancelled ? 'style="background-color: #fff5f5;"' : ""
          }>
                    <td>${inv.id}</td>
                    <td>${inv.invoice_no} ${isCancelled
            ? '<span class="badge bg-danger ms-2">Cancelled</span>'
            : ""
          }</td>
                    <td>${inv.invoice_date}</td>
                    <td>${inv.department_name}</td>
                    <td>${inv.customer_name}</td>
                    <td>${inv.grand_total}</td>
                </tr >
    `;
      });
    } else {
      rows = `< tr > <td colspan="6" class="text-center">No records found</td></tr > `;
    }
    $("#invoiceTableBody").html(rows);

    // Row click → populate form
    $("#invoiceTableBody tr").on("click", function () {
      const id = $(this).data("id");
      if (id) {
        fetchInvoiceData(id);
      }
    });
  }

  $(document).ready(function () {
    loadLatestInvoices(); // load 10 latest by default

    // Search button click
    $("#searchBtn").on("click", function () {
      const query = $("#invoiceSearch").val().trim();
      if (query.length > 0) {
        searchInvoices(query);
      } else {
        loadLatestInvoices();
      }
    });

    // Press Enter inside search box
    $("#invoiceSearch").on("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault(); // stop form submit if inside form
        $("#searchBtn").click(); // trigger search
      }
    });
  });

  // When the modal is shown, focus on the search input
  $("#invoiceModal").on("shown.bs.modal", function () {
    $("#invoiceSearch").focus();
  });

  //     //  FETCH INVOICE ITEMS
  function fetchInvoiceItems(invoiceId) {
    $(".someBlock").preloader(); // Show preloader
    $.ajax({
      url: "ajax/php/temp-sales-items.php", // Replace with your PHP endpoint
      type: "GET",
      data: { invoice_id: invoiceId },
      dataType: "json",
      success: function (response) {
        $(".someBlock").preloader("remove"); // Remove preloader

        $("#payment").hide();
        $("#save").hide();

        let tbody = $("#invoiceItemsBody");
        tbody.empty();

        if (response && response.length > 0) {
          response.forEach((item) => {
            const discountValue = parseFloat(item.discount) || 0;
            let row = `
    < tr >
                                <td>${item.item_code_name}</td>
                                <td>${item.item_name}</td>
                                <td>${parseFloat(
              item.list_price || item.price
            ).toLocaleString(undefined, {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })}</td>
                                <td>${item.quantity}</td>
                                <td>${discountValue}</td>
                                <td>${parseFloat(item.price).toLocaleString(
              undefined,
              {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }
            )}</td>   
                                <td>${parseFloat(item.total).toLocaleString(
              undefined,
              {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }
            )}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-item" data-code="${item.item_code
              }" data-qty="${item.quantity
              }" data-arn-id="${item.id}">Remove</button>
                                </td>
                            </tr >
    `;
            tbody.append(row);
          });
        } else {
          tbody.html(`< tr id = "noItemRow" >
    <td colspan="8" class="text-center text-muted">No items found</td>
                                </tr > `);
        }
      },
      error: function (xhr, status, error) {
        console.error("Failed to fetch items:", error);
        $("#invoiceItemsBody").html(
          `< tr > <td colspan="8" class="text-center text-danger">Error loading items </td></tr > `
        );
      },
    });
  }

  function fetchInvoiceData(invoiceId) {
    $.ajax({
      url: "ajax/php/sales-invoice.php",
      type: "POST",
      data: {
        get_by_id: true,
        id: invoiceId,
      },
      dataType: "json",
      success: function (response) {
        // Close the modal first
        $("#invoiceModal").modal("hide");

        // Reset form first to clear any previous values
        $("#form-data")[0].reset();

        // Set payment type radio button
        $('input[name="payment_type"]').prop("checked", false);
        $(
          'input[name="payment_type"][value="' +
          (response.payment_type || "1") +
          '"]'
        ).prop("checked", true);

        if (response.is_cancel == 1) {
          $(".cancel-invoice").hide();
        } else {
          $(".cancel-invoice").show();
        }

        // Set basic information
        $("#invoice_id").val(response.id || "");
        $("#invoice_no").val(response.invoice_no || "");
        $("#invoice_date").val(response.invoice_date || "");
        $("#company_id")
          .val(response.company_id || "")
          .trigger("change");

        // Set customer information
        $("#customer_code").val(response.customer_code || "");
        $("#customer_name").val(response.customer_name || "");
        $("#customer_address").val(response.customer_address || "");
        $("#customer_mobile").val(response.customer_mobile || "");
        $("#customer_vat_no").val(response.customer_vat_no);

        // Set other fields
        $("#vat_type")
          .val(response.vat_type || "")
          .trigger("change");
        // Use saved totals (do not recalc existing invoices)
        const savedSubTotal = parseFloat(response.sub_total || 0).toFixed(2);
        const savedDiscount = parseFloat(response.discount || 0).toFixed(2);
        const savedTax = parseFloat(response.tax || 0).toFixed(2);
        const savedGrandTotal = parseFloat(response.grand_total || 0).toFixed(
          2
        );
        $("#subTotal").val(savedSubTotal);
        $("#disTotal").val(savedDiscount);
        $("#tax").val(savedTax);
        $("#finalTotal").val(savedGrandTotal);
        // expose saved totals globally so other scripts don't recalc to 0 before items load
        window.savedInvoiceTotals = {
          subTotal: savedSubTotal,
          discount: savedDiscount,
          tax: savedTax,
          grandTotal: savedGrandTotal,
          paymentType: response.payment_type,
          outstanding: response.outstanding_settle_amount || 0,
        };
        $("#is_vat_invoice").prop("checked", response.tax > 0);
        $("#remark").val(response.remark || "");

        // Handle payment section visibility and data for credit invoices
        if (response.payment_type == 2) {
          // Credit payment - show payment section with data
          $("#paymentSection").show();
          $("#paidAmount").val(
            parseFloat(response.outstanding_settle_amount || 0).toFixed(2)
          );
          const balanceAmount =
            parseFloat(response.grand_total || 0) -
            parseFloat(response.outstanding_settle_amount || 0);
          $("#balanceAmount").val(balanceAmount.toFixed(2));

          // Set credit period if available
          $("#credit_period")
            .val(response.credit_period || "")
            .trigger("change");
        } else {
          // Cash payment - hide payment section and clear credit period
          $("#paymentSection").hide();
          $("#credit_period").val("").trigger("change");
          // For cash, balance mirrors saved grand total
          $("#balanceAmount").val(savedGrandTotal);
        }

        // Show print button since invoice is loaded
        $("#print").show();

        // Trigger change events for any dependent fields
        $("select").trigger("change");

        // Load invoice items
        fetchInvoiceItems(invoiceId);

        // Show cancel button if invoice is not cancelled
        if (response.is_cancel == 1) {
          $("#cancelled-badge").show();
          $("#payment").hide();
          $("#save").hide();
          $("#update").hide();
        } else {
          $("#cancelled-badge").hide();
          $("#payment").show();
          $("#save").show();
          $("#update").show();
        }
      },
      error: function (xhr, status, error) {
        console.error("Error fetching invoice data:", error);
        alert("Failed to load invoice data. Please try again.");
      },
    });
  }

  //SHOW MORE AND LESS IN DASHBOARD NOTIFICATION
  $("#show-more-btn").on("click", function () {
    $(".extra-message").removeClass("d-none");
    $("#show-more-btn").addClass("d-none");
    $("#show-less-btn").removeClass("d-none");
  });

  $("#show-less-btn").on("click", function () {
    $(".extra-message").addClass("d-none");
    $("#show-more-btn").removeClass("d-none");
    $("#show-less-btn").addClass("d-none");
  });

  //index alerts
  $(document).ready(function () {
    const totalMessages = $(".alert").length;
    const toggleLink = $("#toggle-messages");

    if (totalMessages <= 2) {
      toggleLink.hide(); // hide toggle link if ≤ 2 messages
    }

    let expanded = false;

    toggleLink.on("click", function (e) {
      e.preventDefault();
      if (!expanded) {
        $(".extra-message").removeClass("d-none");
        toggleLink.text(
          totalMessages + " of " + totalMessages + " Hide messages"
        );
      } else {
        $(".extra-message").addClass("d-none");
        toggleLink.text("2 of " + totalMessages + " click all messages");
      }
      expanded = !expanded;
    });
  });
});

// Global function to convert input to uppercase
function toUpperCaseInput(element) {
  element.value = element.value.toUpperCase();
}

// NIC Validation Function for Sri Lankan NICs
// Old format: 9 digits + V or X (e.g., 123456789V)
// New format: 12 digits (e.g., 200012345678)
function validateNIC(input) {
  var nic = input.value.toUpperCase().trim();
  input.value = nic; // Convert to uppercase

  var statusSpan = document.getElementById('nic-status');
  var errorSpan = document.getElementById('nic-error');

  // Reset styles
  input.classList.remove('is-valid', 'is-invalid');
  statusSpan.innerHTML = '';
  errorSpan.style.display = 'none';
  errorSpan.textContent = '';

  // If empty, no validation needed
  if (nic === '') {
    statusSpan.innerHTML = '';
    return true;
  }

  // Old NIC format: 9 digits followed by V or X
  var oldNicPattern = /^[0-9]{9}[VX]$/;

  // New NIC format: 12 digits
  var newNicPattern = /^[0-9]{12}$/;

  if (oldNicPattern.test(nic) || newNicPattern.test(nic)) {
    // Valid NIC
    input.classList.add('is-valid');
    statusSpan.innerHTML = '<i class="uil uil-check text-success"></i>';
    return true;
  } else {
    // Invalid NIC
    input.classList.add('is-invalid');
    statusSpan.innerHTML = '<i class="uil uil-times text-danger"></i>';

    // Provide helpful error message
    if (nic.length < 10) {
      errorSpan.textContent = 'NIC is too short';
    } else if (nic.length === 10 && !/[VX]$/.test(nic)) {
      errorSpan.textContent = 'Old NIC must end with V or X';
    } else if (nic.length > 10 && nic.length < 12) {
      errorSpan.textContent = 'New NIC must be 12 digits';
    } else if (nic.length === 12 && !/^[0-9]+$/.test(nic)) {
      errorSpan.textContent = 'New NIC must contain only digits';
    } else {
      errorSpan.textContent = 'Invalid NIC format';
    }
    errorSpan.style.display = 'block';
    return false;
  }
}

// Function to check NIC validity (used before form submission)
function isNICValid() {
  var nic = document.getElementById('nic').value.trim();
  if (nic === '') return true; // Empty is allowed (not required)

  var oldNicPattern = /^[0-9]{9}[VX]$/;
  var newNicPattern = /^[0-9]{12}$/;

  return oldNicPattern.test(nic.toUpperCase()) || newNicPattern.test(nic);
}

// Function to view document (image in modal, PDF in new tab)
function viewDocument(src, isPdf) {
  if (isPdf) {
    // Open PDF in new tab
    window.open(src, '_blank');
  } else {
    // Open image in modal
    const modalHtml = `
      <div class="modal fade" id="documentViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Document Preview</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
              <img src="${src}" class="img-fluid" style="max-height: 80vh; width: auto;">
            </div>
            <div class="modal-footer">
              <a href="${src}" download class="btn btn-primary"><i class="uil uil-download-alt me-1"></i> Download</a>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    `;

    // Remove existing modal if any
    $('#documentViewModal').remove();

    // Add modal to body and show
    $('body').append(modalHtml);
    $('#documentViewModal').modal('show');

    // Clean up modal after hidden
    $('#documentViewModal').on('hidden.bs.modal', function () {
      $(this).remove();
    });
  }
}
