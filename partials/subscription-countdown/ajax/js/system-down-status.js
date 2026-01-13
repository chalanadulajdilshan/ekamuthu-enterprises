/**
 * System Down Status Checker
 * Fetches system down status from customer table
 */

// Wait for jQuery to be available
(function () {
  function initWhenReady() {
    if (typeof jQuery === "undefined") {
      // jQuery not loaded yet, wait and try again
      setTimeout(initWhenReady, 100);
      return;
    }

    // jQuery is available, initialize the system
    (function ($) {
      "use strict";

      // Initialize on document ready
      $(document).ready(function () {
        initSystemDownStatus();
      });

      function initSystemDownStatus() {
        fetchSystemDownStatus();

        // Refresh every 5 minutes
        setInterval(fetchSystemDownStatus, 300000);
      }

      function fetchSystemDownStatus() {
        $.ajax({
          url: "partials/subscription-countdown/ajax/php/get-system-down-status.php",
          type: "GET",
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              updateSystemDownDisplay(response.data);
            } else {
              console.log("System down status:", response.message);
              hideSystemDownAlert();
            }
          },
          error: function (xhr, status, error) {
            console.error("Error fetching system down status:", error);
            hideSystemDownAlert();
          },
        });
      }

      function updateSystemDownDisplay(data) {
        if (!data) {
          hideSystemDownAlert();
          return;
        }

        var $alert = $("#system-down-alert");

        if (data.system_down == 1 || data.system_down === true) {
          showSystemDownAlert();
        } else {
          hideSystemDownAlert();
        }
      }

      function showSystemDownAlert() {
        var $alert = $("#system-down-alert");

        if (!$alert.length) {
          // Create alert if it doesn't exist
          $alert = $(
            '<div id="system-down-alert" class="alert alert-warning alert-dismissible fade show" role="alert">' +
              "<strong>System Down!</strong> The system is currently down for maintenance. " +
              '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
              "</div>"
          );

          // Insert at the top of the page
          $("body").prepend($alert);
        }

        $alert.removeClass("d-none").addClass("show");
      }

      function hideSystemDownAlert() {
        var $alert = $("#system-down-alert");
        if ($alert.length) {
          $alert.addClass("d-none").removeClass("show");
        }
      }

      // Expose functions globally if needed
      window.SystemDownStatus = {
        refresh: fetchSystemDownStatus,
        hide: hideSystemDownAlert,
        show: showSystemDownAlert,
      };
    })(jQuery);
  }

  // Start the initialization process
  initWhenReady();
})();
