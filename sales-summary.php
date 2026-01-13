<!doctype html>
<?php
include 'class/include.php';
include './auth.php';
?>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Sales Summary Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <!-- Begin page -->
    <div id="layout-wrapper">
        <?php include 'navigation.php' ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <div class="d-flex justify-content-between w-100">
                                    <div class="btn-group">
                                        <button type="button" id="btnViewReport" class="btn btn-primary">
                                            <i class="uil uil-eye me-1"></i> View Report
                                        </button>
                                        <button type="button" id="btnNewSale" class="btn btn-success ms-2">
                                            <i class="uil uil-plus me-1"></i> Reset
                                        </button>
                                    </div>
                                    <div></div> <!-- Empty div for flex spacing -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Page Title -->

                    <!-- Report Filter Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Filter Options</h4>
                                    <p class="card-title-desc">Filter sales data by customer and date range</p>

                                    <form id="filterForm">
                                        <div class="row">
                                            <!-- Customer Selection -->
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Customer</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="customer_code"
                                                            placeholder="Customer code" readonly>
                                                        <input type="hidden" id="customer_id">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#customerModal">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Customer Name (Display Only) -->
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Customer Name</label>
                                                    <input type="text" class="form-control" id="customer_name"
                                                        placeholder="Customer name" readonly>
                                                </div>
                                            </div>

                                            <!-- Date Range -->
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label">From Date <span class="text-danger">*</span></label>
                                                    <div class="input-group" id="datepicker1">
                                                        <input type="text" class="form-control date-picker" id="from_date"
                                                            placeholder="YYYY-MM-DD" required>
                                                        <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label">To Date <span class="text-danger">*</span></label>
                                                    <div class="input-group" id="datepicker2">
                                                        <input type="text" class="form-control date-picker" id="to_date"
                                                            placeholder="YYYY-MM-DD" required>
                                                        <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Show All Invoices Toggle -->
                                            <div class="col-md-2 d-flex align-items-center">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="show_all_invoices">
                                                    <label class="form-check-label" for="show_all_invoices">
                                                        Show all invoices
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Hidden filter button for form submission -->
                                            <div style="display: none;">
                                                <button type="button" id="btnFilter"></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Filter Section -->

                    <!-- Report Table Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Sales Summary Report</h4>
                                    <p class="card-title-desc">View and analyze sales data by customer</p>

                                    <div class="table-responsive">
                                        <table id="salesReportTable" class="table table-bordered dt-responsive nowrap w-100">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Invoice ID</th>
                                                    <th>Date</th>
                                                    <th>Customer</th>
                                                    <th>Department</th>
                                                    <th>Sales Type</th>
                                                    <th class="text-end">VAT Amount</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="6"></th>
                                                    <th class="text-end">VAT Total:</th>
                                                    <th class="text-end" id="totalVat">0.00</th>
                                                </tr>
                                                <tr>
                                                    <th colspan="6"></th>
                                                    <th class="text-end">Amount Total:</th>
                                                    <th class="text-end" id="totalAmount">0.00</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Report Table Section -->
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            <?php include 'footer.php' ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Include Customer Master Model -->
    <?php include 'customer-master-model.php'; ?>

    <!-- JAVASCRIPT -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.js"></script>

    <!-- Include sales summary JS -->
    <script src="ajax/js/sales-summary.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize datepicker with validation
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                onSelect: function() {
                    // Trigger change event when date is selected
                    $(this).trigger('change');
                }
            });

            // Validate date range
            $('#from_date, #to_date').change(function() {
                const fromDate = $('#from_date').datepicker('getDate');
                const toDate = $('#to_date').datepicker('getDate');

                // Only validate when both dates are selected
                if (!fromDate || !toDate) {
                    return;
                }

                if (fromDate > toDate) {
                    alert('From date cannot be after To date');
                    $(this).datepicker('setDate', null);
                }
            });

            // Guard to control when data can be fetched
            var allowFetch = false;

            // Initialize DataTable (do not auto-load on start)
            var salesTable = $('#salesReportTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: function(data, callback, settings) {
                    // If not allowed to fetch yet, return empty set
                    if (!allowFetch) {
                        $('#totalVat').text('0.00');
                        $('#totalAmount').text('0.00');
                        return callback({
                            data: [],
                            recordsTotal: 0,
                            recordsFiltered: 0,
                            total_amount: 0,
                            total_vat: 0
                        });
                    }

                    // Collect filters
                    var payload = {
                        action: 'fetch_sales_summary',
                        customer_id: $('#customer_id').val(),
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val(),
                        show_all: $('#show_all_invoices').is(':checked') ? 1 : 0
                    };

                    $.ajax({
                        url: 'ajax/php/sales-summary.php',
                        type: 'POST',
                        dataType: 'json',
                        data: $.extend({}, data, payload),
                        success: function(json) {
                            // Update total amount
                            const hasTotals = json && json.total_amount !== undefined;
                            $('#totalAmount').text(hasTotals ? parseFloat(json.total_amount).toFixed(2) : '0.00');
                            $('#totalVat').text(json && json.total_vat !== undefined ? parseFloat(json.total_vat).toFixed(2) : '0.00');
                            callback(json);
                        },
                        error: function() {
                            $('#totalVat').text('0.00');
                            $('#totalAmount').text('0.00');
                            callback({
                                data: [],
                                recordsTotal: 0,
                                recordsFiltered: 0,
                                total_amount: 0,
                                total_vat: 0
                            });
                        }
                    });
                },
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'invoice_id'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'customer_name'
                    },
                    {
                        data: 'department'
                    },
                    {
                        data: 'sales_type'
                    },
                    {
                        data: 'vat_amount',
                        className: 'text-end',
                        render: function(data, type) {
                            if (type === 'display' || type === 'filter') {
                                return parseFloat(data || 0).toFixed(2);
                            }
                            return data;
                        }
                    },
                    {
                        data: 'amount',
                        className: 'text-end',
                        render: function(data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                return parseFloat(data).toFixed(2);
                            }
                            return data; // Return raw data for sorting and other operations
                        }
                    }
                ],
                order: [
                    [2, 'desc']
                ], // Default sort by date
                pageLength: 25,
                responsive: true,
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                language: {
                    paginate: {
                        previous: '<i class="uil uil-angle-left">',
                        next: '<i class="uil uil-angle-right">'
                    }
                },
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    var total = 0;
                    var vatTotal = 0;

                    // Calculate total from the data
                    data.forEach(function(row) {
                        total += parseFloat(row.amount) || 0;
                        vatTotal += parseFloat(row.vat_amount) || 0;
                    });

                    // Update footer
                    $('#totalAmount').text(total.toFixed(2));
                    $('#totalVat').text(vatTotal.toFixed(2));
                },
                drawCallback: function() {
                    // Add row numbers
                    var api = this.api();
                    var startIndex = api.page.info().start;
                    api.column(0, {
                        search: 'applied',
                        order: 'applied'
                    }).nodes().each(function(cell, i) {
                        cell.innerHTML = startIndex + i + 1;
                    });
                }
            });

            // Handle customer selection from customer-master-model
            $(document).on('click', '#customerTable tbody tr', function() {
                var customerId = $(this).find('td:eq(0)').text().trim(); // Get ID from first column
                var customerCode = $(this).find('td:eq(1)').text().trim(); // Get Code from second column
                var customerName = $(this).find('td:eq(2)').text().trim(); // Get Name from third column

                console.log('Customer selected:', {
                    id: customerId,
                    code: customerCode,
                    name: customerName
                });

                // Update the form fields
                if (customerId && customerCode) {
                    $('#customer_id').val(customerId);
                    $('#customer_code').val(customerCode);
                    $('#customer_name').val(customerName);

                    // Close the modal
                    $('#customerModal').modal('hide');

                    // Trigger change event to refresh any dependent fields
                    $('#customer_id').trigger('change');

                    // Do not auto-reload; user must click View Report
                } else {
                    console.error('Invalid customer data received');
                }
            });

            // Customer table is initialized in customer-master-model.php

            // Validate filters before loading
            function canLoad() {
                var from = $('#from_date').val();
                var to = $('#to_date').val();
                var showAll = $('#show_all_invoices').is(':checked');
                // Require both dates and the show all toggle checked
                if (!from || !to) {
                    alert('Please select a From and To date.');
                    return false;
                }
                if (!showAll) {
                    alert('Please tick "Show all invoices" to load the report.');
                    return false;
                }
                return true;
            }

            // View Report button click handler
            $('#btnViewReport').click(function() {
                if (canLoad()) {
                    allowFetch = true;
                    salesTable.ajax.reload(null, true);
                } else {
                    allowFetch = false;
                    salesTable.clear().draw();
                    $('#totalVat').text('0.00');
                    $('#totalAmount').text('0.00');
                }
            });

            // New Sale button click handler - reset the page
            $('#btnNewSale').click(function() {
                // Reset filters
                $('#customer_id').val('').trigger('change');
                $('#customer_code').val('');
                $('#customer_name').val('');
                $('#show_all_invoices').prop('checked', false);

                // Clear date range
                $('#from_date').val('');
                $('#to_date').val('');

                // Clear the table and totals
                allowFetch = false;
                salesTable.clear().draw();
                $('#totalVat').text('0.00');
                $('#totalAmount').text('0.00');
            });
        });
    </script>
</body>

</html>