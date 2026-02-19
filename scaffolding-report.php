<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>

<head>
    <meta charset="utf-8" />
    <title>Scaffolding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <link href="assets/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            max-height: 80vh;
            overflow: auto;
        }
        thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa; /* light gray background */
            z-index: 1;
            text-align: center;
            vertical-align: middle;
            font-size: 12px;
            white-space: nowrap;
        }
        tbody td {
            font-size: 12px;
            padding: 4px !important;
            text-align: center;
        }
        /* Rotated headers for narrow columns */
        .rotate-header {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            max-height: 150px;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            #print-area, #print-area * {
                visibility: visible;
            }
            #print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .page-content {
                padding: 0 !important;
                margin: 0 !important;
            }
            .table-responsive {
                overflow: visible !important;
                max-height: none !important;
            }
            table {
                width: 100% !important;
                table-layout: fixed;
            }
            th, td {
                font-size: 10px !important;
                padding: 2px !important;
                border: 1px solid #000 !important;
            }
            /* Ensure table fits on page landscape mostly */
            @page {
                size: landscape;
                margin: 5mm;
            }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <div id="layout-wrapper">
        <div class="no-print">
            <?php include 'navigation.php' ?>
        </div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row no-print">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Scaffolding Stock Report</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="row mb-4 no-print">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Filter by Date</h5>
                                    <form id="filter-form" class="row align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">From Date</label>
                                            <input type="text" class="form-control date-picker" id="from_date" 
                                                   value="<?php echo date('Y-m-01'); ?>" placeholder="Select Start Date">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">To Date</label>
                                            <input type="text" class="form-control date-picker" id="to_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" placeholder="Select End Date">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" id="btn-filter" class="btn btn-primary w-100">
                                                <i class="uil uil-filter me-1"></i> Generate Report
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Content -->
                    <div class="row" id="print-area">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <!-- Print Header -->
                                    <div class="d-none d-print-block text-center mb-4">
                                        <h3><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h3>
                                        <h5>Scaffolding Stock Report</h5>
                                        <p class="mb-0">
                                            From: <span id="print_from_date"><?php echo date('Y-m-01'); ?></span> To: <span id="print_to_date"><?php echo date('Y-m-d'); ?></span>
                                        </p>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover table-sm display nowrap" style="width:100%" id="report_table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Gate Pass No</th>
                                                    <th>Date</th>
                                                    <th>Bill No</th>
                                                    <th style="min-width: 200px;">Customer</th>
                                                    <th>Acro Jack</th>
                                                    <th>U Jack</th>
                                                    <th>T Jack</th>
                                                    <th>Plate 6 ft</th>
                                                    <th>Plate 12 ft</th>
                                                    <th>SF (5 1/2) * 4</th>
                                                    <th>SF (5 1/2) * 3</th>
                                                    <th>SF (5 1/2) * 2 1/2</th>
                                                    <th>SF (3*4)</th>
                                                    <th>Walking Board</th>
                                                    <th>Joint/Coupler</th>
                                                    <th>Base Jack</th>
                                                    <!-- Numbered Pipes -->
                                                    <?php
                                                    $pipe_sizes = ["1 1/2", "2", "2 1/2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20"];
                                                    foreach ($pipe_sizes as $size) {
                                                        echo "<th class='rotate-header'><div style='writing-mode: vertical-rl; transform: rotate(180deg);'>$size</div></th>";
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody id="report_body">
                                                <!-- Data will be generated by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-end mt-3 no-print">
                                        <button onclick="window.print()" class="btn btn-secondary">
                                            <i class="uil uil-print me-1"></i> Print Report
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    <?php include 'footer.php' ?>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <?php include 'main-js.php' ?>
    
    <script>
        $(document).ready(function() {
            // Datepicker init
            $('.date-picker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });

            // Mock Data Generation
            function generateMockData() {
                const customers = [
                    "D.D. Ranjith Pullaperuma", "A.S. Kadar", "T. Sokar", "G.A.S. Kumara", 
                    "Access Engineering - UDA Borella", "Fifty at Lester's", "M.M.M. Rohan Somarathna",
                    "PS Dehiwala", "PS Anuradhapura", "Skill Engineering", "P. Sasikrishnan",
                    "M.M.M. Mifthaan", "B. Fedriq Perera", "Ms. Shiromi - Selling Items",
                    "M.L. Harischandra", "Field Constructions", "V.L. Sudharshana", 
                    "V.M. Dhanushka Madushan", "M.N.M. Shariz", "D.M. Nalinda Pathmakumara",
                    "Built Mech - N. Balakrishnan", "G.A.S. Kumara"
                ];

                let html = '';
                const startDate = new Date($('#from_date').val());
                const endDate = new Date($('#to_date').val());
                let currentDate = new Date(startDate);

                let gatePass = 2518;
                let billNo = 962;

                // Generate about 50 rows
                for (let i = 0; i < 50; i++) {
                    const randomCustomer = customers[Math.floor(Math.random() * customers.length)];
                    const dateStr = currentDate.toISOString().split('T')[0];
                    
                    // Increment date occasionally
                    if (i % 3 === 0) {
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    if (currentDate > endDate) break;

                    html += `<tr>
                        <td>${gatePass++}</td>
                        <td>${dateStr}</td>
                        <td>${billNo + Math.floor(Math.random() * 5)}</td>
                        <td class="text-start">${randomCustomer}</td>
                        <td>${Math.random() > 0.7 ? Math.floor(Math.random() * 50) : ''}</td>
                        <td>${Math.random() > 0.8 ? Math.floor(Math.random() * 20) : ''}</td>
                        <td>${Math.random() > 0.9 ? Math.floor(Math.random() * 10) : ''}</td>
                        <td>${Math.random() > 0.6 ? Math.floor(Math.random() * 100) : ''}</td>
                        <td>${Math.random() > 0.6 ? Math.floor(Math.random() * 200) : ''}</td>
                        <td>${Math.random() > 0.8 ? Math.floor(Math.random() * 50) : ''}</td>
                        <td>${Math.random() > 0.8 ? Math.floor(Math.random() * 40) : ''}</td>
                        <td>${Math.random() > 0.9 ? Math.floor(Math.random() * 30) : ''}</td>
                        <td>${Math.random() > 0.9 ? Math.floor(Math.random() * 20) : ''}</td>
                        <td>${Math.random() > 0.7 ? Math.floor(Math.random() * 100) : ''}</td>
                        <td>${Math.random() > 0.5 ? Math.floor(Math.random() * 500) : ''}</td>
                        <td>${Math.random() > 0.8 ? Math.floor(Math.random() * 50) : ''}</td>`;
                    
                    // Pipe sizes columns (21 columns)
                    for (let j = 0; j < 21; j++) {
                        html += `<td>${Math.random() > 0.9 ? Math.floor(Math.random() * 50) : ''}</td>`;
                    }
                    
                    html += `</tr>`;
                }
                $('#report_body').html(html);
            }

            // Initial Generation
            generateMockData();

            // Re-generate on filter
            $('#btn-filter').click(function() {
                $('#print_from_date').text($('#from_date').val());
                $('#print_to_date').text($('#to_date').val());
                generateMockData();
            });
        });
    </script>
</body>
</html>
