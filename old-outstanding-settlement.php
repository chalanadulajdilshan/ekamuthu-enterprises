<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Old Outstanding Settlement | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <style>
        /* Main card styling */
        #settlementContainer>.card {
            border: 1px solid #000;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        /* Status badge styling */
        .badge {
            padding: 0.5em 0.8em;
            font-size: 0.85em;
            font-weight: 500;
        }

        .summary-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
        }

        .summary-label {
            font-weight: 600;
        }

        /* Form elements */
        .form-label {
            margin-bottom: 0.25rem;
        }

        .old-outstanding-highlight {
            background-color: #fff3cd !important;
            color: #856404 !important;
            font-weight: bold;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Old Outstanding Settlement</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="settlementForm">
                                        <div class="row g-4 align-items-end">
                                            <div class="col-md-4">
                                                <label for="customer_code" class="form-label">Customer</label>
                                                <div class="input-group">
                                                    <input id="customer_code" name="customer_code" type="text" placeholder="Select Customer" class="form-control" readonly>
                                                    <input type="hidden" id="customer_id" name="customer_id">
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#oldOutstandingCustomerModal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <button type="button" id="viewBtn" class="btn btn-primary"><i class="mdi mdi-eye me-1"></i> View</button>
                                                <button type="button" id="resetBtn" class="btn btn-secondary"><i class="mdi mdi-refresh me-1"></i> Reset</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <!-- Settlement Container -->
                                    <div id="settlementContainer">
                                        <div class="text-muted text-center py-5">
                                            <i class="uil uil-invoice display-4"></i>
                                            <p class="mt-2">Select a customer to view old outstanding amount</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <?php include 'old-outstanding-customer-model.php'; ?>
    <?php include 'main-js.php'; ?>

    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="ajax/js/old-outstanding-settlement.js"></script>
    <script src="ajax/js/common.js"></script>

</body>

</html>
