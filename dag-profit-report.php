<!doctype html>
<?php
include 'class/include.php';
include './auth.php';
?>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>DAG Profit Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                            <a href="#" class="btn btn-primary" id="view_dag_profit_report">
                                <i class="uil uil-chart-line me-1"></i> Generate Report
                            </a>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">DAG Profit Report</li>
                            </ol>
                        </div>
                    </div>

                    <!-- end page title -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-xs">
                                                <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                    01
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h5 class="font-size-16 mb-1">Serial Number Wise DAG Profit Report</h5>
                                            <p class="text-muted text-truncate mb-0">Generate detailed profit report for DAG items by serial number</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        <form id="dag-profit-form" autocomplete="off">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="from_date" class="form-label">From Date</label>
                                                    <div class="input-group" id="datepicker1">
                                                        <input type="text" class="form-control date-picker"
                                                            id="from_date" name="from_date">
                                                        <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="to_date" class="form-label">To Date</label>
                                                    <div class="input-group" id="datepicker2">
                                                        <input type="text" class="form-control date-picker"
                                                            id="to_date" name="to_date">
                                                        <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="customer_id" class="form-label">Customer</label>
                                                    <div class="input-group mb-3">
                                                        <select id="customer_id" name="customer_id" class="form-select">
                                                            <option value="">-- All Customers --</option>
                                                            <?php
                                                            $CUSTOMER = new CustomerMaster();
                                                            foreach ($CUSTOMER->all() as $customer) {
                                                            ?>
                                                                <option value="<?php echo $customer['id'] ?>">
                                                                    <?php echo $customer['name'] ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="company_id" class="form-label">Company</label>
                                                    <div class="input-group mb-3">
                                                        <select id="company_id" name="company_id" class="form-select">
                                                            <option value="">-- All Companies --</option>
                                                            <?php
                                                            $DAG_COMPANY = new DagCompany();
                                                            foreach ($DAG_COMPANY->all() as $company) {
                                                            ?>
                                                                <option value="<?php echo $company['id'] ?>">
                                                                    <?php echo $company['name'] ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="serial_number" class="form-label">Serial Number</label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" class="form-control" id="serial_number" 
                                                               name="serial_number" placeholder="Enter serial number">
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="job_number" class="form-label">Job Number</label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" class="form-control" id="job_number" 
                                                               name="job_number" placeholder="Enter job number">
                                                    </div>
                                                </div>
                                            </div>
                                        </form>

                                        <hr class="my-4">

                                        <div id="dagProfitReportDateRange" class="mb-3"></div>

                                        <!-- Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="dagProfitReportTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Serial Number</th>
                                                        <th>DAG Received Date</th>
                                                        <th>Customer Name</th>
                                                        <th>Previous Customer</th>
                                                        <th>Company</th>
                                                        <th>Company Issued Date</th>
                                                        <th>Company Delivered Date</th>
                                                        <th>Job No</th>
                                                        <th>Size</th>
                                                        <th>Casing Cost</th>
                                                        <th>Total Amount</th>
                                                        <th>Profit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="12" class="text-center text-muted">Click "Generate Report" to view data</td>
                                                    </tr>
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th colspan="9">Total:</th>
                                                        <th id="total_casing_cost">0.00</th>
                                                        <th id="total_amount">0.00</th>
                                                        <th id="total_profit">0.00</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

    <!-- DAG Profit Report JS -->
    <script src="ajax/js/dag-profit-report.js"></script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <!-- App js -->
    <script src="assets/js/app.js"></script>
    
    <script>
        $(function() {
            // Initialize the datepicker
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd'
            });

            // Set today's date as default value
            var today = $.datepicker.formatDate('yy-mm-dd', new Date());
            $(".date-picker").val(today);
        });
    </script>

</body>

</html>
