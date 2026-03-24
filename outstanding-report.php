<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom styles to match print report */
        .report-summary-box {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .report-stat-item {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .report-stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 8px;
            display: block;
        }
        .report-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
        }
        .text-danger-custom { color: #dc3545; }
        .text-success-custom { color: #198754; }

        /* Table Styling Overrides */
        .table thead th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 2px solid #dee2e6;
            vertical-align: middle;
        }
        .table tbody td {
            vertical-align: middle;
        }
        
        /* Row coloring based on status - Maximum specificity */
        table#reportTable.dataTable tbody tr.status-returned,
        table#reportTable.dataTable tbody tr.status-returned td,
        table#reportTable tbody tr.status-returned,
        table#reportTable tbody tr.status-returned td,
        #reportTable tbody tr.status-returned,
        #reportTable tbody tr.status-returned td {
            background-color: #d4edda !important;
            background: #d4edda !important;
        }
        
        table#reportTable.dataTable tbody tr.status-not-returned,
        table#reportTable.dataTable tbody tr.status-not-returned td,
        table#reportTable tbody tr.status-not-returned,
        table#reportTable tbody tr.status-not-returned td,
        #reportTable tbody tr.status-not-returned,
        #reportTable tbody tr.status-not-returned td {
            background-color: #fff3cd !important;
            background: #fff3cd !important;
        }
        
        /* Ensure hover state still works */
        table#reportTable.dataTable tbody tr.status-returned:hover,
        table#reportTable.dataTable tbody tr.status-returned:hover td,
        #reportTable tbody tr.status-returned:hover,
        #reportTable tbody tr.status-returned:hover td {
            background-color: #c3e6cb !important;
            background: #c3e6cb !important;
        }
        
        table#reportTable.dataTable tbody tr.status-not-returned:hover,
        table#reportTable.dataTable tbody tr.status-not-returned:hover td,
        #reportTable tbody tr.status-not-returned:hover,
        #reportTable tbody tr.status-not-returned:hover td {
            background-color: #ffeaa7 !important;
            background: #ffeaa7 !important;
        }
        
        /* Override any DataTables even/odd row coloring */
        table#reportTable tbody tr.status-returned.odd,
        table#reportTable tbody tr.status-returned.odd td,
        table#reportTable tbody tr.status-returned.even,
        table#reportTable tbody tr.status-returned.even td {
            background-color: #d4edda !important;
        }
        
        table#reportTable tbody tr.status-not-returned.odd,
        table#reportTable tbody tr.status-not-returned.odd td,
        table#reportTable tbody tr.status-not-returned.even,
        table#reportTable tbody tr.status-not-returned.even td {
            background-color: #fff3cd !important;
        }
        
        /* Hide elements marked for print exclusion */
        .print-hide { display: block; }
        @media print {
            .print-hide { display: none !important; }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">හිඟ බිල්පත් වාර්තාව</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            
                            <!-- Summary Cards -->
                            <div class="report-summary-box">
                                <div class="report-stat-item">
                                    <span class="report-stat-label">සාමාන්‍ය කුලිය</span>
                                    <span class="report-stat-value" id="cardTotalRent">රු. 0.00</span>
                                </div>
                                <div class="report-stat-item">
                                    <span class="report-stat-label">ගෙවූ මුදල් එකතුව</span>
                                    <span class="report-stat-value text-success-custom" id="cardTotalPaid">රු. 0.00</span>
                                </div>
                                <div class="report-stat-item">
                                    <span class="report-stat-label">මුළු හිඟ මුදල</span>
                                    <span class="report-stat-value text-danger-custom" id="cardTotalBalance">රු. 0.00</span>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">වාර්තාව පෙරහන් කරන්න</h5>
                                    <div class="row mb-4 g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">පාරිභෝගිකයා</label>
                                            <div class="input-group">
                                                <input id="customer_code" name="customer_code" type="text" placeholder="පාරිභෝගිකයා තෝරන්න (විකල්ප)" class="form-control" readonly>
                                                <input type="hidden" id="customer_id" name="customer_id">
                                                <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#customerModal">
                                                    <i class="uil uil-search me-1"></i>
                                                </button>
                                                <button class="btn btn-secondary" type="button" id="clearCustomer">
                                                    <i class="uil uil-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">දිනයන් පරාසය</label>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <input type="text" id="from_date" class="form-control date-picker" placeholder="ආරම්භක දිනය" autocomplete="off">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" id="to_date" class="form-control date-picker" placeholder="අවසාන දිනය" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">මාසය (විකල්ප)</label>
                                            <select id="month_filter" class="form-select">
                                                <option value="">සියලු මාස</option>
                                                <option value="1">ජනවාරි</option>
                                                <option value="2">පෙබරවාරි</option>
                                                <option value="3">මාර්තු</option>
                                                <option value="4">අප්‍රේල්</option>
                                                <option value="5">මැයි</option>
                                                <option value="6">ජූනි</option>
                                                <option value="7">ජූලි</option>
                                                <option value="8">අගෝස්තු</option>
                                                <option value="9">සැප්තැම්බර්</option>
                                                <option value="10">ඔක්තෝබර්</option>
                                                <option value="11">නොවැම්බර්</option>
                                                <option value="12">දෙසැම්බර්</option>
                                            </select>
                                            <small class="text-muted">මාසයක් තෝරන විට දිනයන් පරාසය නොගනී.</small>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end flex-wrap gap-2">
                                            <button id="generateBtn" class="btn btn-primary"><i class="uil uil-file-alt"></i> වාර්තාව සකසන්න</button>
                                            <button id="printBtn" class="btn btn-success"><i class="uil uil-print"></i> මුද්‍රණය (විස්තර)</button>
                                            <button id="printSummaryBtn" class="btn btn-outline-success"><i class="uil uil-print"></i> මුද්‍රණය (සාරාංශය)</button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="reportTable">
                                            <thead>
                                                <tr>
                                                    <th style="width:40px;"></th>
                                                    <th style="width:60px;">පින්තූරය</th>
                                                    <th>ඉන්වොයිස් අංකය</th>
                                                    <th>දිනය</th>
                                                    <th>ගෙවීමේ වර්ගය</th>
                                                    <th>පාරිභෝගිකයා</th>
                                                    <th>තත්ත්වය</th>
                                                    <th class="text-end">දින කුලිය</th>
                                                    <th class="text-end">කුලිය (මුළු)</th>
                                                    <th class="text-end">ආරම්භක තැන්පතුව</th>
                                                    <th class="text-end">ගෙවූ මුදල</th>
                                                    <th class="text-end">බැලන්ස් (අවැසි)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data loaded via AJAX -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="7" class="text-end">මුළු එකතුව:</th>
                                                    <th class="text-end" id="totalDayRent">0.00</th>
                                                    <th class="text-end" id="totalRent">0.00</th>
                                                    <th class="text-end" id="totalInitialDeposit">0.00</th>
                                                    <th class="text-end" id="totalPaid">0.00</th>
                                                    <th class="text-end" id="totalBalance">0.00</th>
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
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Bill Detail Modal -->
    <div class="modal fade" id="billDetailModal" tabindex="-1" aria-labelledby="billDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="billDetailModalLabel">බිල් විස්තර</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" id="billModalDateLabel"></div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>ඉන්වොයිස් අංකය:</strong> <span id="billModalInvoice">-</span></div>
                        <div class="col-md-3"><strong>දිනය:</strong> <span id="billModalDate">-</span> <small class="text-muted" id="billModalDayCount"></small></div>
                        <div class="col-md-3"><strong>ගෙවීමේ වර්ගය:</strong> <span id="billModalPayment">-</span></div>
                        <div class="col-md-3"><strong>තත්ත්වය:</strong> <span id="billModalStatus">-</span></div>
                        <div class="col-md-4"><span id="billModalCustomer">-</span></div>
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <div id="billModalCustomerPhoto" class="text-center">
                                    <!-- Customer Photo -->
                                </div>
                                <div id="billModalNicFront" class="text-center">
                                    <!-- NIC Front -->
                                </div>
                                <div id="billModalNicBack" class="text-center">
                                    <!-- NIC Back -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 mb-3 print-hide">
                        <div class="flex-fill" style="min-width:260px;">
                            <label class="form-label"><strong>ආරම්භක දිනය:</strong></label>
                            <input type="text" id="billModalCalcStartDate" class="form-control date-picker" placeholder="ආරම්භක දිනය තෝරන්න" autocomplete="off">
                            <small class="text-muted d-block">හිඟ මුදල ගණනය ආරම්භ වන දිනය</small>
                        </div>
                        <div class="flex-fill" style="min-width:260px;">
                            <label class="form-label"><strong>අවසාන දිනය:</strong></label>
                            <input type="text" id="billModalCalcDate" class="form-control date-picker" placeholder="අවසාන දිනය තෝරන්න" autocomplete="off">
                            <small class="text-muted d-block">හිඟ මුදල ගණනය අවසන් වන දිනය</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between"><span>කුලිය</span><span id="billModalBaseRent">0.00</span></div>
                                <div class="d-flex justify-content-between"><span>හානි</span><span id="billModalDamage">0.00</span></div>
                                <div class="d-flex justify-content-between"><span>දින කුලිය</span><span id="billModalDayRent">0.00</span></div>
                                <div class="d-flex justify-content-between fw-bold"><span>මුළු කුලිය</span><span id="billModalTotalRent">0.00</span></div>
                                <div class="d-flex justify-content-between"><span>ආරම්භක තැන්පතුව</span><span id="billModalInitialDeposit">0.00</span></div>
                                <div class="d-flex justify-content-between"><span>ගෙවූ මුදල</span><span id="billModalTotalPaid">0.00</span></div>
                                <div class="d-flex justify-content-between fw-bold"><span>බැලන්ස්</span><span id="billModalBalance">0.00</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between"><span>වාර්තාගත හිඟ මුදල</span><span id="billModalRecordedTotal">0.00</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between"><span>ඇස්තමේන්තු කළ හිඟ මුදල</span><span id="billModalProjectedTotal">0.00</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between fw-bold"><span>සම්පූර්ණ හිඟ මුදල</span><span id="billModalFullOutstanding">0.00</span></div>
                                <small class="text-muted">බිල ආරම්භක දිනය සිට අද දක්වා</small>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">බිල් අයිතම</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalItems">
                                        <thead class="table-light">
                                            <tr>
                                                <th>අයිතම</th>
                                                <th class="text-center">ප්‍රමාණය</th>
                                                <th class="text-center">කාලය</th>
                                                <th class="text-center">වර්ගය</th>
                                                <th class="text-center">ආපසු</th>
                                                <th class="text-center">සිදුවීම්</th>
                                                <th class="text-center">තත්ත්වය</th>
                                                <th class="text-end">මුදල</th>
                                                <th class="text-center">හානි</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="9" class="text-muted">අයිතම නොමැත.</td></tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="8" class="text-end">මුළු</th>
                                                <th class="text-end" id="billModalItemsTotal">0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">ලියාපදිංචි හිඟ බිල්පත්</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalRecorded">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ආපසු දිනය</th>
                                                <th>අයිතම</th>
                                                <th class="text-end">හිඟ</th>
                                                <th class="text-end">ගෙවූ</th>
                                                <th>සටහන</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="5" class="text-muted">ලියාපදිංචි ඇතුළත් කිරීම් නොමැත.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">ගෙවීම් ඉතිහාසය</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalPayments">
                                        <thead class="table-light">
                                            <tr>
                                                <th>දිනය</th>
                                                <th>රිසිට් අංකය</th>
                                                <th class="text-end">මුදල</th>
                                                <th>වిధය / යොමු</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="4" class="text-muted">ගෙවීම් නොමැත.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <h6 class="fw-bold mb-2 mt-3">තැන්පත් ගෙවීම්</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalDeposits">
                                        <thead class="table-light">
                                            <tr>
                                                <th>දිනය</th>
                                                <th>සටහන</th>
                                                <th class="text-end">මුදල</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="3" class="text-muted">තැන්පත් ගෙවීම් නොමැත.</td></tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total</th>
                                                <th class="text-end" id="billModalDepositTotal">0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 print-hide">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <h6 class="fw-bold mb-0">සටහන්</h6>
                                    <div class="d-flex align-items-center gap-2 flex-wrap" style="flex:1;">
                                        <input type="text" id="billModalRemarkInput" class="form-control form-control-sm" placeholder="සටහන ඇතුළත් කරන්න" style="min-width:360px; flex:1;">
                                        <button class="btn btn-primary btn-sm" id="billModalRemarkSave">සුරකින්න</button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalRemarks">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:160px;">දිනය</th>
                                                <th>සටහන</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="2" class="text-muted">සටහන් නොමැත.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">ආපසු ඉතිහාසය</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalReturns">
                                        <thead class="table-light">
                                            <tr>
                                                <th>දිනය</th>
                                                <th>වේලාව</th>
                                                <th>අයිතම</th>
                                                <th class="text-center">ප්‍රමාණය</th>
                                                <th class="text-end">කුලිය</th>
                                                <th class="text-end">අමතර</th>
                                                <th class="text-end">දඩ</th>
                                                <th class="text-end">හානි</th>
                                                <th class="text-end">අමතර ගාස්තු</th>
                                                <th class="text-end">අලුත්වැඩියා</th>
                                                <th class="text-end">ගෙවීම</th>
                                                <th class="text-end">ගෙවූ</th>
                                                <th class="text-end">හිඟ</th>
                                                <th>සටහන</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="14" class="text-muted">ආපසු ඉතිහාසය නොමැත.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="billModalPrint"><i class="uil uil-print me-1"></i> මුද්‍රණය</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">වසන්න</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Modal -->
    <?php include 'customer-master-model.php'; ?>

    <?php include 'main-js.php'; ?>
    <script src="ajax/js/common.js"></script>
    
    <!-- Page Specific JS -->
    <script src="ajax/js/outstanding-report.js"></script>

</body>
</html>
