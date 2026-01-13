jQuery(document).ready(function () {
  // Create Service Income
  $("#create").click(function (event) {
    event.preventDefault();

    if (!$("#name").val() || $("#name").val().length === 0) {
      swal({
        title: "Error!",
        text: "Please enter service name",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (!$("#amount").val() || $("#amount").val().length === 0) {
      swal({
        title: "Error!",
        text: "Please enter amount",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      $(".someBlock").preloader();

      var formData = new FormData($("#form-data")[0]);
      formData.append("create", true);

      $.ajax({
        url: "ajax/php/service-income.php",
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
              text: "Service income added successfully!",
              type: "success",
              timer: 2000,
              showConfirmButton: false,
            });

            window.setTimeout(function () {
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
    }

    return false;
  });

  // Update Service Income
  $("#update").click(function (event) {
    event.preventDefault();

    if (!$("#name").val() || $("#name").val().length === 0) {
      swal({
        title: "Error!",
        text: "Please enter service name",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else if (!$("#amount").val() || $("#amount").val().length === 0) {
      swal({
        title: "Error!",
        text: "Please enter amount",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      $(".someBlock").preloader();

      var formData = new FormData($("#form-data")[0]);
      formData.append("update", true);

      $.ajax({
        url: "ajax/php/service-income.php",
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
              text: "Service income updated successfully!",
              type: "success",
              timer: 2000,
              showConfirmButton: false,
            });

            window.setTimeout(function () {
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
    }

    return false;
  });

  // Reset form
  $("#new").click(function (e) {
    e.preventDefault();
    $("#form-data")[0].reset();
    $("#create").show();
    $("#update").hide();
  });

  // Populate form for editing
  $(document).on("click", ".select-service-income", function () {
    $("#income_id").val($(this).data("id"));
    $("#name").val($(this).data("name"));
    $("#amount").val($(this).data("amount"));
    $("#remark").val($(this).data("remark"));

    $("#create").hide();
    $("#update").show();
    $("#serviceIncomeModel").modal("hide");
  });

  // Delete Service Income
  $(document).on("click", ".delete-income", function (e) {
    e.preventDefault();

    var incomeId = $("#income_id").val();
    var incomeName = $("#name").val();

    if (!incomeId || incomeId === "") {
      swal({
        title: "Error!",
        text: "Please select a record first.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    swal(
      {
        title: "Are you sure?",
        text: "Do you want to delete '" + incomeName + "'?",
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
            url: "ajax/php/service-income.php",
            type: "POST",
            data: { id: incomeId, delete: true },
            dataType: "JSON",
            success: function (response) {
              $(".someBlock").preloader("remove");

              if (response.status === "success") {
                swal({
                  title: "Deleted!",
                  text: "Service income has been deleted.",
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
        }
      }
    );
  });
});
