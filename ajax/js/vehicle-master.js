jQuery(document).ready(function () {
    // Create Vehicle
    $("#create").click(function (event) {
      event.preventDefault();
  
      // Validation
      if (!$("#vehicle_no").val() || $("#vehicle_no").val().length === 0) {
        swal({
          title: "Error!",
          text: "Please enter vehicle number",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
      } else {
        // Preloader start
        $(".someBlock").preloader();
  
        // Grab all form data
        var formData = new FormData($("#form-data")[0]);
        formData.append("create", true);
  
        $.ajax({
          url: "ajax/php/vehicle-master.php",
          type: "POST",
          data: formData,
          async: false,
          cache: false,
          contentType: false,
          processData: false,
          success: function (result) {
            // Remove preloader
            $(".someBlock").preloader("remove");
  
            if (result.status === "success") {
              swal({
                title: "Success!",
                text: "Vehicle added Successfully!",
                type: "success",
                timer: 2000,
                showConfirmButton: false,
              });
  
              window.setTimeout(function () {
                window.location.reload();
              }, 2000);
            } else if (result.status === "error") {
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
  
    // Update Vehicle
    $("#update").click(function (event) {
      event.preventDefault();
  
      // Validation
      if (!$("#vehicle_no").val() || $("#vehicle_no").val().length === 0) {
        swal({
          title: "Error!",
          text: "Please enter vehicle number",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
      } else {
        // Preloader start
        $(".someBlock").preloader();
  
        // Grab all form data
        var formData = new FormData($("#form-data")[0]);
        formData.append("update", true);
  
        $.ajax({
          url: "ajax/php/vehicle-master.php",
          type: "POST",
          data: formData,
          async: false,
          cache: false,
          contentType: false,
          processData: false,
          dataType: "JSON",
          success: function (result) {
            // Remove preloader
            $(".someBlock").preloader("remove");
  
            if (result.status == "success") {
              swal({
                title: "Success!",
                text: "Vehicle updated Successfully!",
                type: "success",
                timer: 2500,
                showConfirmButton: false,
              });
  
              window.setTimeout(function () {
                window.location.reload();
              }, 2000);
            } else if (result.status === "error") {
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
  
    // Delete Vehicle
    $(document).on("click", ".delete-vehicle-master", function (e) {
      e.preventDefault();
  
      var id = $("#id").val();
      var vehicle_no = $("#vehicle_no").val();
  
      if (!vehicle_no || vehicle_no === "") {
        swal({
          title: "Error!",
          text: "Please select a Vehicle first.",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
        return;
      }
  
      swal(
        {
          title: "Are you sure?",
          text: "Do you want to delete vehicle '" + vehicle_no + "'?",
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
              url: "ajax/php/vehicle-master.php",
              type: "POST",
              data: {
                id: id,
                delete: true,
              },
              dataType: "json",
              success: function (response) {
                $(".someBlock").preloader("remove");
  
                if (response.status === "success") {
                  swal({
                    title: "Deleted!",
                    text: "Vehicle has been deleted.",
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
  
    // Reset form
    $("#new").click(function (e) {
      e.preventDefault();
      window.location.reload();
    });
  
    // Model selection
    $(document).on("click", ".select-vehicle", function () {
      $("#id").val($(this).data("id"));
      $("#ref_no").val($(this).data("ref_no"));
      $("#vehicle_no").val($(this).data("vehicle_no"));
      $("#brand").val($(this).data("brand"));
      $("#model").val($(this).data("model"));
      $("#type").val($(this).data("type"));
      $("#start_meter").val($(this).data("start_meter"));
  
      $("#create").hide();
      $("#update").show();
      $("#vehicleModel").modal("hide");
    });
  });
