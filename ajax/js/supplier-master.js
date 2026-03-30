jQuery(document).ready(function () {
  // Create Supplier
  $("#create").click(function (event) {
    event.preventDefault();

    $("#create").prop("disabled", true);

    // Validation
    if (!$("#code").val()) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please enter supplier code",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (!$("#name").val()) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please enter supplier name",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (!$("#mobile_number").val()) {
      $("#create").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please enter mobile number",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      $(".someBlock").preloader();

      var formData = new FormData($("#form-data")[0]);
      formData.append("create", true);

      $.ajax({
        url: "ajax/php/supplier-master.php",
        type: "POST",
        data: formData,
        async: true,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function (result) {
          $(".someBlock").preloader("remove");
          $("#create").prop("disabled", false);

          if (result.status === "success") {
            swal({
              title: "Success!",
              text: "Supplier added successfully!",
              type: "success",
              timer: 2000,
              showConfirmButton: false,
            });

            setTimeout(() => {
              window.location.reload();
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
          $(".someBlock").preloader("remove");
          $("#create").prop("disabled", false);
          swal({
            title: "Error!",
            text: "Failed to create supplier. Please check the console for details.",
            type: "error",
            showConfirmButton: true,
          });
        },
      });
    }

    return false;
  });

  // New Button - Reset Form
  $("#new").click(function () {
    window.location.reload();
  });

  // Update Supplier
  $("#update").click(function (event) {
    event.preventDefault();

    $("#update").prop("disabled", true);

    if (!$("#code").val()) {
      $("#update").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please enter supplier code",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (!$("#name").val()) {
      $("#update").prop("disabled", false);
      swal({
        title: "Error!",
        text: "Please enter supplier name",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      var formData = new FormData($("#form-data")[0]);
      formData.append("update", true);

      $(".someBlock").preloader();

      $.ajax({
        url: "ajax/php/supplier-master.php",
        type: "POST",
        data: formData,
        async: true,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function (result) {
          $(".someBlock").preloader("remove");
          $("#update").prop("disabled", false);

          if (result.status == "success") {
            swal({
              title: "Success!",
              text: "Supplier updated successfully!",
              type: "success",
              timer: 2500,
              showConfirmButton: false,
            });

            setTimeout(() => {
              window.location.reload();
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
        error: function () {
          $(".someBlock").preloader("remove");
          $("#update").prop("disabled", false);
          swal({
            title: "Error!",
            text: "System encountered an error",
            type: "error",
            showConfirmButton: true,
          });
        },
      });
    }

    return false;
  });

  // Delete Supplier
  $(document).on("click", ".delete-customer", function (e) {
    e.preventDefault();

    var supplierId = $("#supplier_id_hidden").val();
    var name = $("#name").val();

    if (!supplierId || supplierId === "") {
      swal({
        title: "Error!",
        text: "Please select a supplier first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    swal(
      {
        title: "Are you sure?",
        text: "Do you want to delete supplier '" + name + "'?",
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
          $.ajax({
            url: "ajax/php/supplier-master.php",
            type: "POST",
            data: {
              id: supplierId,
              delete: true,
            },
            dataType: "JSON",
            success: function (response) {
              if (response.status === "success") {
                swal({
                  title: "Deleted!",
                  text: "Supplier has been deleted.",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false,
                });

                setTimeout(() => {
                  window.location.reload();
                }, 2000);
              } else {
                swal("Error", "Something went wrong.", "error");
              }
            },
          });
        }
      },
    );
  });


});
