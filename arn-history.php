<!doctype html>
<?php
include 'class/include.php';
include './auth.php';

// Check permissions if needed
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>ARN History | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <style>
        /* ARN Returns Styles */
        .toggle-returns {
            transition: all 0.3s ease;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 12px;
        }
        .toggle-returns:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.4);
        }
        .toggle-returns.expanded {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%) !important;
            color: #000 !important;
            border-color: #ff9800 !important;
            box-shadow: 0 3px 10px rgba(255, 152, 0, 0.3);
        }
        .toggle-returns .badge {
            font-size: 10px;
            padding: 3px 6px;
            margin-left: 4px;
            border-radius: 10px;
        }
        
        /* Return Row Styles */
        .return-row {
            background: linear-gradient(180deg, #fffef5 0%, #fff8e1 100%) !important;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .return-row:hover {
            background: linear-gradient(180deg, #fff8e1 0%, #ffecb3 100%) !important;
        }
        
        /* Return Card Design */
        .return-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin: 8px 12px;
            overflow: hidden;
            border-left: 4px solid #ff9800;
        }
        .return-card-header {
            background: linear-gradient(135deg, #fff8e1 0%, #ffe0b2 100%);
            padding: 12px 16px;
            border-bottom: 1px solid #ffe0b2;
        }
        .return-card-body {
            padding: 0;
        }
        
        /* Return Badge Styles */
        .return-badge {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);
        }
        .return-total-badge {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        
        /* Return Info Styles */
        .return-info {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        .return-info-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #555;
        }
        .return-info-item i {
            color: #ff9800;
            font-size: 12px;
        }
        .return-info-item strong {
            color: #333;
        }
        
        /* Return Items Table */
        .return-items-table {
            margin: 0;
            font-size: 12px;
        }
        .return-items-table thead th {
            background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 10px 12px;
            border: none;
            border-bottom: 2px solid #dee2e6;
        }
        .return-items-table tbody td {
            padding: 10px 12px;
            vertical-align: middle;
            border-color: #f0f0f0;
        }
        .return-items-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .return-items-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* No Returns State */
        .no-returns-msg {
            color: #6c757d;
            font-style: italic;
            padding: 20px;
            text-align: center;
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
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                           ARN Summary Report
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">ARN History</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class=" mb-3  ">
                        <div class="card">
                            <div class="card-body row">
                                
                               
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="text" id="date_from" class="form-control date-picker">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="text" id="date_to" class="form-control date-picker">
                        </div>
                        <div class="col-md-3">
                            <label for="supplier" class="form-label">Supplier</label>
                            <div class="input-group mb-3">
                                <input id="supplier_code" name="supplier_code" type="text"
                                    class="form-control" placeholder="Select Supplier" readonly>
                                <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                    data-bs-target="#supplierModal" title="Search Supplier">
                                    <i class="uil uil-search"></i>
                                </button>
                            </div>
                            <input type="hidden" id="supplier_id" name="supplier_id" />
                        </div>
                        <div class="col-md-3">
                            <label for="payment_type" class="form-label">Payment Type</label>
                            <select id="payment_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="1">Cash</option>
                                <option value="2">Credit</option>
                                <!-- Add more as needed -->
                            </select>
                        </div>
                        
                      
                        <div class="col-md-3">
                            <label for="grn_id" class="form-label">Search GRN ID</label>
                            <div class="input-group mb-3">
                                <input type="text" id="grn_id" class="form-control" placeholder="Select ARN No" readonly>
                                <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#arnModal" title="Search ARN">
                                    <i class="uil uil-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success form-control" id="filterBtn">Filter</button>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-secondary form-control" id="resetBtn">Reset</button>
                        </div>
                    </div>
 </div>
                        </div>
                    <!-- ARN History Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="arnHistoryTable" class="table table-bordered dt-responsive nowrap"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ARN No</th>
                                                <th>Date</th>
                                                <th>Supplier</th>
                                                <th>Payment Type</th>
                                                <th>Amount</th>
                                                <th>Paid</th>
                                                <th>Outstanding</th>
                                                <th>Status</th>
                                                <th>Returns</th>
                                            </tr>
                                        </thead>
                                        <tbody id="arnHistoryBody">
                                            <?php
                                            $ARN_MASTER = new ArnMaster(null);
                                            $arns = $ARN_MASTER->all();
                                            $db = Database::getInstance();
                                            foreach ($arns as $key => $arn) {
                                                $key++;
                                                $CUSTOMER_MASTER = new CustomerMaster($arn['supplier_id']);
                                                $DEPARTMENT_MASTER = new DepartmentMaster($arn['department']);
                                                $PAYMENT_TYPE = new PaymentType($arn['purchase_type']);
                                                $is_cancelled = isset($arn['is_cancelled']) && $arn['is_cancelled'] == 1;
                                                $rowClass = $is_cancelled ? 'table-danger' : '';
                                                
                                                // Check for returns
                                                $return_count_query = "SELECT COUNT(*) as count FROM purchase_return WHERE arn_id = " . (int)$arn['id'];
                                                $return_count_result = mysqli_fetch_assoc($db->readQuery($return_count_query));
                                                $has_returns = $return_count_result['count'] > 0;
                                            ?>
                                                <tr class="<?php echo $rowClass; ?>" data-arn-id="<?php echo $arn['id']; ?>">
                                                    <td><?php echo $key; ?></td>
                                                    <td><?php echo htmlspecialchars($arn['arn_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($arn['invoice_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($CUSTOMER_MASTER->code . ' - ' . $CUSTOMER_MASTER->name); ?></td>
                                                    <td><?php echo htmlspecialchars($PAYMENT_TYPE->name ?? ''); ?></td>
                                                    <td class="text-end"><?php echo number_format($arn['total_arn_value'], 2); ?></td>
                                                    <td class="text-end"><?php echo number_format($arn['paid_amount'], 2); ?></td>
                                                    <td class="text-end"><?php echo number_format($arn['total_arn_value'] - $arn['paid_amount'], 2); ?></td>
                                                    <td>
                                                        <?php if ($is_cancelled): ?>
                                                            <span class="badge bg-danger">Cancelled</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($has_returns): ?>
                                                            <button class="btn btn-sm btn-outline-warning toggle-returns" data-arn-id="<?php echo $arn['id']; ?>" title="View Returns">
                                                                <i class="fas fa-undo-alt"></i>
                                                                <span class="badge bg-warning text-dark"><?php echo $return_count_result['count']; ?></span>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container-fluid -->
            </div>
        </div>
        <!-- End Page-content -->

        <?php include 'footer.php' ?>

    </div>
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!-- Supplier Modal -->
    <?php include 'supplier-master-model.php' ?>

    <!-- ARN Modal -->
    <?php include 'arn-modal.php' ?>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <!-- Additional DataTables buttons -->
    <script src="assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/libs/pdfmake/build/pdfmake.min.js"></script>
    <script src="assets/libs/pdfmake/build/vfs_fonts.js"></script>

    <script src="ajax/js/arn-history.js"></script>
    <script src="ajax/js/common.js"></script>
