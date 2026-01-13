<!-- jQuery (latest version, only once) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SweetAlert -->
<script src="assets/libs/sweetalert/sweetalert-dev.js"></script>

<!-- Preloader -->
<script src="assets/js/jquery.preloader.min.js"></script>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>

<!-- DataTables (Bootstrap 4 compatible, but still works with Bootstrap 5) -->
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>


<!-- Datatables init -->
<script src="assets/js/pages/datatables.init.js"></script>

<!-- Select2 (stable version) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        // Initialize Select2
        $('.select2').select2({
            placeholder: 'Select a vehicle',
            allowClear: true
        });

        // Initialize datepickers
        $(".date-picker, .date-picker-date").datepicker({
            dateFormat: 'yy-mm-dd'
        });

        // Set today's date for .date-picker fields
        $(".date-picker").val($.datepicker.formatDate('yy-mm-dd', new Date()));

        // Initialize datatables
        $('#dagTable, #maindagTable, .datatable').DataTable();
    });
</script>

<?php
// Include real-time system status monitor for auto-redirect when system goes down
include_once __DIR__ . '/partials/subscription-countdown/system-status-monitor.php';
?>
