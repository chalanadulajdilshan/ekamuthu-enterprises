<?php
include 'class/include.php';
include 'auth.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$pageCategory = new PageCategory($category_id);

if (!$pageCategory->id) {
    header('Location: index.php');
    exit;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $pageCategory->name; ?> | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name; ?>" name="author" />
    <?php include 'main-css.php'; ?>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">
                    <?php
                    $DEFAULT_DATA = new DefaultData();
                    $USER_PERMISSION = new UserPermission();
                    $user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
                    $PAGES = new Pages(null);
                    foreach ($DEFAULT_DATA->pagesSubCategory() as $key => $subCategoryTitle):
                        $subPages = $PAGES->getPagesBySubCategory($key);
                        $visiblePages = [];
                        foreach ($subPages as $page) {
                            $permissions = $USER_PERMISSION->hasPermission($user_id, $page['id']);
                            if (in_array(true, $permissions, true)) {
                                $visiblePages[] = $page;
                            }
                        }
                        if (!empty($visiblePages)):
                    ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><?php echo $subCategoryTitle; ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($visiblePages as $page): ?>
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                            <a href="<?php echo $page['page_url'] . '?page_id=' . $page['id']; ?>" class="btn btn-outline-primary btn-lg w-100 d-flex align-items-center justify-content-start gp-tile-btn">
                                                <?php if (!empty($page['page_icon'])): ?>
                                                    <i class="<?php echo htmlspecialchars($page['page_icon']); ?> me-3 gp-tile-icon"></i>
                                                <?php else: ?>
                                                    <i class="uil uil-file me-3 gp-tile-icon"></i>
                                                <?php endif; ?>
                                                <?php echo $page['page_name']; ?>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
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

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

</body>

</html>
