jQuery(document).ready(function () {
  // Create Supplier Payment
  $("#savePayment").click(function (event) {
    event.preventDefault();

    // Validation
    let isValid = true;
    let errorMessage = "";

    // Check if ARN is selected
    if (!$("#arn_id").val() || $("#arn_id").val().length === 0) {
      errorMessage = "Please select ARN";
      isValid = false;
    }

    // Check if there's at least one payment row
    const paymentRows = $(".payment-row");
    if (paymentRows.length === 0) {
      errorMessage = "Please add at least one payment method";
      isValid = false;
    }

    // Validate each payment row
    paymentRows.each(function (index) {
      const row = $(this);
      const paymentType = row.find(".paymentType").val();
      const amount = parseFloat(row.find(".paymentAmount").val()) || 0;

      if (!paymentType) {
        errorMessage = `Please select payment type in row ${index + 1}`;
        isValid = false;
        return false; // Exit the each loop
      }

      if (isNaN(amount) || amount <= 0) {
        errorMessage = `Please enter a valid amount in row ${index + 1}`;
        isValid = false;
        return false; // Exit the each loop
      }

      if (paymentType === "2") {
        // Cheque validation
        const chequeNumber = row.find('input[name="chequeNumber[]"]').val();
        const chequeBank = row.find('input[name="chequeBank[]"]').val();
        const chequeDate = row.find('input[name="chequeDate[]"]').val();

        if (!chequeNumber || chequeNumber.trim() === "") {
          errorMessage = `Please enter cheque number in row ${index + 1}`;
          isValid = false;
          return false;
        }
        if (!chequeBank || chequeBank.trim() === "") {
          errorMessage = `Please enter bank name in row ${index + 1}`;
          isValid = false;
          return false;
        }
        if (!chequeDate) {
          errorMessage = `Please select cheque date in row ${index + 1}`;
          isValid = false;
          return false;
        }
      }
    });

    // Check if total paid matches the final total
    const totalPaid =
      parseFloat(
        $("#totalPaid")
          .val()
          .replace(/[^0-9.-]+/g, "")
      ) || 0;
    const finalTotal =
      parseFloat(
        $("#modalFinalTotal")
          .val()
          .replace(/[^0-9.-]+/g, "")
      ) || 0;

    if (totalPaid !== finalTotal) {
      errorMessage = "Total paid amount must be equal to the final total";
      isValid = false;
    }

    if (!isValid) {
      swal({
        title: "Error!",
        text: errorMessage,
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return false;
    } else {
      $(".someBlock").preloader(); // Optional preloader

      var formData = new FormData($("#paymentForm")[0]);
      formData.append("arn_id", $("#arn_id").val());
      formData.append("create", true);

      $.ajax({
        url: "ajax/php/supplier-payment.php",
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
            swal({
              title: "Success!",
              text: "Supplier payment added successfully!",
              type: "success",
              timer: 2000,
              showConfirmButton: false,
            });

            setTimeout(() => window.location.reload(), 2000);
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
    }
    return false;
  });

  // Update Supplier Payment
  $("#update").click(function (event) {
    event.preventDefault();

    // Same validation as create
    if (!$("#invoiceId").val() || $("#invoiceId").val().length === 0) {
      swal({
        title: "Error!",
        text: "Please enter receipt ID",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (!$("#invoiceId").val() || $("#invoiceId").val().length === 0) {
      swal({
        title: "Error!",
        text: "Please select invoice",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (
      !$("#paymentTypeId").val() ||
      $("#paymentTypeId").val().length === 0
    ) {
      swal({
        title: "Error!",
        text: "Please select payment type",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (
      !$("#amount").val() ||
      $("#amount").val().length === 0 ||
      parseFloat($("#amount").val()) <= 0
    ) {
      swal({
        title: "Error!",
        text: "Please enter a valid amount",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (
      $("#paymentTypeId").val() === "cheque" &&
      (!$("#cheqNo").val() || $("#cheqNo").val().length === 0)
    ) {
      swal({
        title: "Error!",
        text: "Please enter cheque number",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      $(".someBlock").preloader();

      var formData = new FormData($("#form-data")[0]);
      formData.append("update", true);

      $.ajax({
        url: "ajax/php/supplier_payment.php",
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
            swal({
              title: "Success!",
              text: "Supplier payment updated successfully!",
              type: "success",
              timer: 2000,
              showConfirmButton: false,
            });

            setTimeout(() => window.location.reload(), 2000);
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
    }
    return false;
  });

  // Reset form for new payment
  $("#new").click(function (e) {
    e.preventDefault();
    $("#form-data")[0].reset();
    $("#create").show();
    $("#update").hide();
  });

  // Fill form when selecting an existing payment (from modal or table)
  $(document).on("click", ".select-payment", function () {
    $("#payment_id").val($(this).data("id"));
    $("#receiptId").val($(this).data("receiptid"));
    $("#invoiceId").val($(this).data("invoiceid"));
    $("#paymentTypeId").val($(this).data("paymenttypeid"));
    $("#amount").val($(this).data("amount"));
    $("#cheqNo").val($(this).data("cheqno"));
    $("#bankId").val($(this).data("bankid"));
    $("#branchId").val($(this).data("branchid"));
    $("#cheqDate").val($(this).data("cheqdate"));

    if ($(this).data("settle") == 1) {
      $("#isSettle").prop("checked", true);
    } else {
      $("#isSettle").prop("checked", false);
    }

    $("#create").hide();
    $("#update").show();
    $("#supplier_payment_modal").modal("hide");
  });

  // Delete supplier payment
  $(document).on("click", ".delete-payment", function (e) {
    e.preventDefault();

    var paymentId = $("#payment_id").val();
    if (!paymentId || paymentId === "") {
      swal({
        title: "Error!",
        text: "Please select a payment first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    swal(
      {
        title: "Are you sure?",
        text: "Do you want to delete this supplier payment?",
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
          $(".someBlock").preloader();

          $.ajax({
            url: "ajax/php/supplier_payment.php",
            type: "POST",
            data: { id: paymentId, delete: true },
            dataType: "JSON",
            success: function (response) {
              $(".someBlock").preloader("remove");

              if (response.status === "success") {
                swal({
                  title: "Deleted!",
                  text: "Supplier payment has been deleted.",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false,
                });

                setTimeout(() => window.location.reload(), 2000);
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
        }
      }
    );
  });
});
