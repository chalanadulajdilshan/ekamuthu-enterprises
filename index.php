<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$homeViewMode = $COMPANY_PROFILE_DETAILS->home_view_mode ?? 'both';

?>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Homes | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name; ?>" name="author" />
    <?php include 'main-css.php'; ?>


    <style>
        .chart-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .chart-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .chart-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #5b73e8, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .chart-subtitle {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }

        .chart-wrapper {
            position: relative;
            height: 500px;
            margin: 30px 100px;
            background: linear-gradient(145deg, #f8f9ff, #e8ecff);
            border-radius: 15px;
            padding: 40px;
            box-shadow: inset 0 2px 10px rgba(91, 115, 232, 0.1);
        }

        .bar-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            height: 100%;
            position: relative;
        }

        .bar-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            margin: 0 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .bar-wrapper:hover {
            transform: translateY(-5px);
        }

        .bar {
            width: 100%;
            max-width: 50px;
            background: linear-gradient(180deg, #5b73e8, #667eea);
            border-radius: 8px 8px 4px 4px;
            position: relative;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(91, 115, 232, 0.3);
            transform-origin: bottom;
            animation: barGrow 1.5s ease-out forwards;
            animation-delay: calc(var(--index) * 0.1s);
            height: 0;
        }

        @keyframes barGrow {
            from {
                height: 0;
                transform: scaleY(0);
            }

            to {
                height: var(--height);
                transform: scaleY(1);
            }
        }

        .bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #fff, #f0f2ff);
            border-radius: 8px 8px 0 0;
            opacity: 0.8;
        }

        .bar:hover {
            background: linear-gradient(180deg, #6c82f0, #7589f2);
            box-shadow: 0 6px 25px rgba(91, 115, 232, 0.4);
            transform: scale(1.05);
        }

        .bar-value {
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(91, 115, 232, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .bar-wrapper:hover .bar-value {
            opacity: 1;
        }

        .bar-label {
            margin-top: 15px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-grid {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 60px;
            pointer-events: none;
        }

        .grid-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(108, 117, 125, 0.15);
        }

        .grid-label {
            position: absolute;
            left: -50px;
            transform: translateY(-50%);
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .chart-wrapper {
                padding: 20px;
                height: 400px;
            }

            .chart-title {
                font-size: 2rem;
            }

            .bar {
                max-width: 35px;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 4px 15px rgba(91, 115, 232, 0.3);
            }

            50% {
                box-shadow: 0 6px 25px rgba(91, 115, 232, 0.5);
            }

            100% {
                box-shadow: 0 4px 15px rgba(91, 115, 232, 0.3);
            }
        }
    </style>

</head>

<body data-layout="horizontal" data-topbar="colored">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php'; ?>


        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">
                    <?php include 'partials/subscription-countdown/subscription-countdown.php'; ?>
                    <?php
                    $ITEM_MASTER = new ItemMaster(NULL);
                    $MESSAGE = new Message(null);

                    $reorderItems = $ITEM_MASTER->checkReorderLevel();

                    if (!empty($reorderItems)) {
                        $customMessages = [];

                        foreach ($reorderItems as $item) {
                            $customMessages[] = "Reorder Alert: <strong>{$item['code']}</strong> - {$item['name']} is below reorder level.";
                        }

                        $MESSAGE->showCustomMessages($customMessages, 'danger');
                    }

                    // Due Date Notifications
                    $db = Database::getInstance();
                    $dueDateColumnCheck = $db->readQuery("SHOW COLUMNS FROM `sales_invoice` LIKE 'due_date'");
                    $hasDueDateColumn = ($dueDateColumnCheck && mysqli_num_rows($dueDateColumnCheck) > 0);

                    if ($hasDueDateColumn) {
                        $query = "SELECT COUNT(*) as total FROM sales_invoice 
                                  WHERE payment_type = 2 AND due_date IS NOT NULL 
                                  AND due_date >= CURDATE() AND due_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY) 
                                  AND is_cancel = 0";
                        $result = $db->readQuery($query);
                        if ($result) {
                            $row = mysqli_fetch_assoc($result);
                            $totalDueNotifications = $row['total'];
                            if ($totalDueNotifications > 0) {
                                $dueNotifications = ["<a href='customer-outstanding-report.php' class='alert-link'>View {$totalDueNotifications} upcoming due date(s) within 2 days</a>"];
                                echo '<div id="due_date_notification">';
                                $MESSAGE->showCustomMessages($dueNotifications, 'warning');
                                echo '</div>';
                            }
                        }
                    }

                    ?>
                    <?php if ($homeViewMode !== 'header') { ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Quick Navigation</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php
                                        $PAGE_CATEGORY = new PageCategory(NULL);
                                        $USER_PERMISSION = new UserPermission();
                                        $user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
                                        foreach ($PAGE_CATEGORY->getActiveCategory() as $category):
                                            $hasCategoryAccess = false;
                                            $firstPage = null;
                                            $PAGES = new Pages(null);
                                            if ($category['id'] == 1) { // Dashboard
                                                $dashboardPages = $PAGES->getPagesByCategory($category['id']);
                                                if (!empty($dashboardPages)) {
                                                    $dashboardPage = $dashboardPages[0];
                                                    $permissions = $USER_PERMISSION->hasPermission($user_id, $dashboardPage['id']);
                                                    if (in_array(true, $permissions, true)) {
                                                        $hasCategoryAccess = true;
                                                        $firstPage = $dashboardPage;
                                                    }
                                                }
                                            } elseif ($category['id'] == 4) { // Reports
                                                // For reports, get the first subpage
                                                $DEFAULT_DATA = new DefaultData();
                                                foreach ($DEFAULT_DATA->pagesSubCategory() as $key => $subCategoryTitle) {
                                                    $subPages = $PAGES->getPagesBySubCategory($key);
                                                    foreach ($subPages as $page) {
                                                        $permissions = $USER_PERMISSION->hasPermission($user_id, $page['id']);
                                                        if (in_array(true, $permissions, true)) {
                                                            $hasCategoryAccess = true;
                                                            $firstPage = $page;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            } else { // Other categories
                                                $categoryPages = $PAGES->getPagesByCategory($category['id']);
                                                foreach ($categoryPages as $page) {
                                                    $permissions = $USER_PERMISSION->hasPermission($user_id, $page['id']);
                                                    if (in_array(true, $permissions, true)) {
                                                        $hasCategoryAccess = true;
                                                        $firstPage = $page;
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($hasCategoryAccess && $firstPage):
                                        ?>
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                            <a href="<?php echo strtolower(str_replace(' ', '-', $category['name'])) . '-tab.php?category_id=' . $category['id']; ?>" class="btn btn-outline-primary btn-lg w-100 d-flex align-items-center justify-content-start gp-tile-btn">
                                                <i class="<?php echo $category['icon']; ?> me-3 gp-tile-icon"></i> <?php echo $category['name']; ?>
                                            </a>
                                        </div>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->


            <?php include 'footer.php' ?>

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->



    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/common.js"></script>

    <!-- ApexCharts -->
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <script src="assets/libs/Simple-Countdown-Periodic-Timer-Plugin-With-jQuery-SyoTimer/Simple-Countdown-Periodic-Timer-Plugin-With-jQuery-SyoTimer/build/jquery.syotimer.min.js"></script>
    <script src="partials/subscription-countdown/ajax/js/subscription-countdown.js"></script>

    <!-- Dashboard init -->
    <script src="assets/js/pages/dashboard.init.js"></script>

</body>

</html>