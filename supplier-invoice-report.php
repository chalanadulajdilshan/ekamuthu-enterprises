<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Supplier Invoice Analysis Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />

    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css">
    
    <style>
        .report-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .bg-soft-primary { background-color: rgba(59, 93, 231, 0.1); color: #3b5de7; }
        .bg-soft-success { background-color: rgba(68, 181, 125, 0.1); color: #44b57d; }
        .bg-soft-warning { background-color: rgba(255, 191, 0, 0.1); color: #ffbf00; }
        .bg-soft-danger { background-color: rgba(244, 106, 106, 0.1); color: #f46a6a; }
        
        #reportTable thead th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            min-height: 350px;
        }

        /* Print Header Style - Standardized with other reports */
        .print-header { display: none; margin-bottom: 30px; text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .print-company-sinhala { font-weight: bold; font-size: 20px; line-height: 1.2; margin-bottom: 2px; }
        .print-company-english { font-weight: 700; font-size: 22px; line-height: 1.2; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
        .print-company-meta { font-size: 13px; color: #555; line-height: 1.4; }
        .print-report-title { font-size: 18px; font-weight: bold; text-decoration: underline; margin: 15px 0; text-transform: uppercase; }

        @media print {
            .no-print { display: none !important; }
            .print-header { display: block !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; margin-bottom: 15px !important; }
            .card-body { padding: 10px !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .page-content { padding: 0 !important; }
            .container-fluid { padding: 0 !important; }
            body { padding: 0; background: #fff !important; }
            .report-card { border: none !important; }
            .table-responsive { overflow: visible !important; }
            table { width: 100% !important; border: 1px solid #333 !important; }
            th { background-color: #f2f2f2 !important; border: 1px solid #333 !important; color: #000 !important; }
            td { border: 1px solid #333 !important; color: #000 !important; }
            
            /* Horizontal layout for summary cards on print */
            .summary-container { display: flex !important; flex-wrap: nowrap !important; gap: 10px !important; margin-bottom: 20px !important; }
            .summary-container .col-lg-3 { flex: 1 !important; max-width: none !important; width: 25% !important; }
            .summary-card { padding: 10px !important; border: 1px solid #333 !important; border-radius: 6px !important; text-align: center !important; }
            .summary-card h6 { font-size: 11px !important; margin-bottom: 2px !important; }
            .summary-card h4 { font-size: 15px !important; margin-bottom: 0 !important; }
            .summary-card p { font-size: 10px !important; }
            
            @page { margin: 10mm; size: auto; }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    
                    <!-- Professional Print Header - Standardized -->
                    <div class="print-header">
                        <div class="print-company-sinhala">පී. එස්. එකමුතු එන්ටර්ප්‍රයිසිස්</div>
                        <div class="print-company-english">P.S. Ekamuthu Enterprises</div>
                        <div class="print-company-meta">
                            <?php echo $COMPANY_PROFILE_DETAILS->address; ?><br>
                            Tel: <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_1; ?> | Email: <?php echo $COMPANY_PROFILE_DETAILS->email; ?><br>
                            Printed on: <?php echo date('Y-m-d H:i:s'); ?>
                        </div>
                        <div class="print-report-title">Supplier Invoice Analysis Report</div>
                        <div id="printFilterInfo" style="font-size: 12px; margin-top: 5px; font-weight: normal;"></div>
                    </div>

                    <!-- start page title -->
                    <div class="row no-print">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Supplier Invoice Analysis Report</h4>
                                <div class="page-title-right no-print">
                                    <button type="button" class="btn btn-success waves-effect waves-light mt-2 mt-sm-0" id="printReport">
                                        <i class="mdi mdi-printer me-1"></i> Print Full Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Filter Section -->
                    <div class="row no-print">
                        <div class="col-12">
                            <div class="card report-card">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="row align-items-end">
                                            <!-- Supplier Filter -->
                                            <div class="col-md-3">
                                                <label for="supplier_code" class="form-label">Supplier</label>
                                                <div class="input-group">
                                                    <input id="code" name="supplier_code" type="text"
                                                        placeholder="All Suppliers" class="form-control" readonly>
                                                    <input type="hidden" id="customer_id" name="supplier_id">
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#AllSupplierModal">
                                                        <i class="uil uil-search"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Date Filter -->
                                            <div class="col-md-2">
                                                <label for="fromDate" class="form-label">From Date</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" autocomplete="off">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="toDate" class="form-label">To Date</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control date-picker" id="toDate" name="toDate" autocomplete="off">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>

                                            <div class="col-md-5">
                                                <button type="button" class="btn btn-primary px-4 me-1" id="searchBtn">
                                                    <i class="mdi mdi-magnify me-1"></i> Generate Report
                                                </button>
                                                <button type="button" class="btn btn-light px-4" id="resetBtn">
                                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Analysis Cards - refactored to summary-container for print layout -->
                    <div class="row summary-container mt-2" id="summarySection">
                        <div class="col-md-3">
                            <div class="card report-card summary-card card-body h-100">
                                <div class="card-icon bg-soft-primary no-print"><i class="mdi mdi-cart-outline"></i></div>
                                <h6 class="text-muted text-uppercase mb-2 no-print">Total Purchases</h6>
                                <h6 class="text-muted text-uppercase mb-2 d-none d-print-block">PURCHASES</h6>
                                <h4 class="mb-0" id="summaryTotalPurchases">0.00</h4>
                                <div class="mt-2 text-muted">
                                    <span class="badge bg-soft-primary text-primary no-print" id="invoiceCountDisp">0</span> 
                                    <span class="no-print">Invoices</span>
                                    <span class="d-none d-print-block small">From <span id="invoiceCountPrint">0</span> Invoices</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card summary-card card-body h-100">
                                <div class="card-icon bg-soft-success no-print"><i class="mdi mdi-cash-multiple"></i></div>
                                <h6 class="text-muted text-uppercase mb-2 no-print">Paid Amount</h6>
                                <h6 class="text-muted text-uppercase mb-2 d-none d-print-block">PAID</h6>
                                <h4 class="mb-0 text-success" id="summaryTotalPaid">0.00</h4>
                                <div class="mt-2 text-muted small">
                                    Cash + Bank Settled
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card summary-card card-body h-100">
                                <div class="card-icon bg-soft-danger no-print"><i class="mdi mdi-alert-circle-outline"></i></div>
                                <h6 class="text-muted text-uppercase mb-2 no-print">Outstanding</h6>
                                <h6 class="text-muted text-uppercase mb-2 d-none d-print-block">OUTSTANDING</h6>
                                <h4 class="mb-0 text-danger" id="summaryTotalOutstanding">0.00</h4>
                                <div class="mt-2">
                                    <span class="text-danger small fw-bold" id="outstandingPercentage">0%</span> of Total
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card summary-card card-body h-100">
                                <div class="card-icon bg-soft-warning no-print"><i class="mdi mdi-repeat"></i></div>
                                <h6 class="text-muted text-uppercase mb-2 no-print">Avg. Per Invoice</h6>
                                <h6 class="text-muted text-uppercase mb-2 d-none d-print-block">AVERAGE</h6>
                                <h4 class="mb-0" id="summaryAvgInvoice">0.00</h4>
                                <div class="mt-2 text-muted small no-print">
                                    Purchase Efficiency
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Analysis - Hidden in Print -->
                    <div class="row no-print">
                        <div class="col-lg-12">
                            <div class="card report-card shadow-sm border-0 mb-4">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-2"><i class="mdi mdi-chart-line me-2 text-primary"></i>Purchase Trends Over Time</h6>
                                    <div id="trendChart" class="chart-container" style="min-height: 200px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row no-print">
                        <div class="col-lg-6">
                            <div class="card report-card mb-4">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-2">Top Suppliers Contribution</h6>
                                    <div id="supplierChart" class="chart-container" style="min-height: 200px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card report-card mb-4">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-2">Top 5 Purchased Items</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="text-end">Qty</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody id="topItemsTableBody">
                                                <!-- Dynamic Content -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row no-print">
                        <div class="col-lg-12">
                            <div class="card report-card shadow-sm border-0 mb-4">
                                <div class="card-body py-2 text-center">
                                    <h6 class="card-title mb-2">Payment Method Share</h6>
                                    <div id="paymentChart" class="chart-container d-inline-block" style="min-height: 200px; width: 100%; max-width: 400px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Report Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <h5 class="card-title mb-0">Invoice Listing & Breakdown</h5>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-centered table-nowrap mb-0">
                                            <thead>
                                                <tr>
                                                    <th>GRN / Invoice</th>
                                                    <th>Date</th>
                                                    <th>Supplier</th>
                                                    <th>Payment Type</th>
                                                    <th class="text-end">Total Amount</th>
                                                    <th class="text-end">Paid</th>
                                                    <th class="text-end">Outstanding</th>
                                                    <th class="no-print text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody">
                                                <!-- Dynamic Content -->
                                            </tbody>
                                            <tfoot class="bg-light fw-bold">
                                                <tr>
                                                    <td colspan="4" class="text-end">Totals</td>
                                                    <td class="text-end" id="footerTotal">0.00</td>
                                                    <td class="text-end" id="footerPaid">0.00</td>
                                                    <td class="text-end text-danger" id="footerOutstanding">0.00</td>
                                                    <td class="no-print"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?php include 'footer.php'; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Invoice Details Modal -->
    <div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 box-shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Invoice Item Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Description</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceItemsTableBody">
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="" id="printSingleInvoice" target="_blank" class="btn btn-primary"><i class="mdi mdi-printer me-1"></i> Print Invoice</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'customer-master-model.php'; // Includes the AllSupplierModal ?>
    <?php include 'all-suppliers-model.php'; ?>
    <?php include 'main-js.php'; ?>

    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- Apex Charts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Custom JS -->
    <script src="ajax/js/supplier-invoice-report.js"></script>

</body>
</html>
