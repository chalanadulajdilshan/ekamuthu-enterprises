<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
 
 
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>PD Check Balance | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <style>
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .balance-amount {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .cashbook-table {
            font-size: 0.9rem;
        }

        .cashbook-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .transaction-in {
            color: #28a745;
        }

        .transaction-out {
            color: #dc3545;
        }
    </style>
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
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap align-items-end gap-3">
                                        <div class="d-flex flex-wrap align-items-end gap-2">
                                            
                                            <div class="me-2">
                                                <label for="date" class="form-label">Date From</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="uil uil-calendar-alt"></i></span>
                                                    <input type="text" class="form-control date-picker cashbook-date" id="date" name="date" autocomplete="off" >
                                                </div>
                                            </div>
                                            <div class="me-2">
                                                <label for="date_to" class="form-label">Date To</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="uil uil-calendar-alt"></i></span>
                                                    <input type="text" class="form-control date-picker cashbook-date" id="date_to" name="date_to" autocomplete="off"  >
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap align-items-end gap-2">
                                                <button class="btn btn-primary" id="btn-filter">
                                                    <i class="uil uil-filter me-1"></i> Filter
                                                </button>
                                                <button class="btn btn-secondary" id="btn-reset-filter">
                                                    <i class="uil uil-redo me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    

                    <!-- Cashbook Transactions Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">PD Check Details</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="pending-check-table" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Check No</th>
                                                    <th>Check Date</th>
                                                    <th>Bank</th>
                                                    <th>Branch</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cashbook-tbody">
                                                <!-- Data will be loaded by DataTables -->
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <td colspan="5" class="text-end"><strong>Total PD Check value:</strong></td>
                                                    <td class="text-end"><strong id="total-pb-check-value">0.00</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     

                </div>
            </div> <!-- container-fluid -->
        </div>

        <?php include 'footer.php' ?>

    </div>
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->
 

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <?php include 'main-js.php' ?>
    <script src="ajax/js/pending-check.js"></script>
 
</body>

</html>