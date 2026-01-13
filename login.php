<?php
include 'class/include.php';

// Get active company logo and theme
$company = new CompanyProfile();
$activeCompany = $company->getActiveCompany();
$logoPath = 'assets/images/logo.png'; // Default logo path
$themeColor = '#5b73e8'; // Default theme color
$companyName = '';

if (!empty($activeCompany[0])) {
    $companyData = $activeCompany[0];
    if (!empty($companyData['image_name'])) {
        $logoPath = 'uploads/company-logos/' . $companyData['image_name'];
    }
    if (!empty($companyData['theme'])) {
        $themeColor = $companyData['theme'];
    }
    if (!empty($companyData['name'])) {
        $companyName = $companyData['name'];
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Login | <?php echo $companyName; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="#" name="description" />
    <meta content="Themesbrand" name="author" />
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- Theme Color Variables -->
    <style>
        :root {
            --bs-primary: <?php echo $themeColor; ?>;
            --bs-primary-rgb: <?php echo implode(', ', sscanf(ltrim($themeColor, '#'), '%02x%02x%02x')); ?>;
        }

        .authentication-bg {
            background-color: <?php echo $themeColor; ?>;
            background: linear-gradient(135deg, <?php echo $themeColor; ?> 0%, <?php echo adjustBrightness($themeColor, -30); ?> 100%);
        }

        .btn-primary {
            background-color: <?php echo $themeColor; ?>;
            border-color: <?php echo $themeColor; ?>;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: <?php echo adjustBrightness($themeColor, -10); ?>;
            border-color: <?php echo adjustBrightness($themeColor, -10); ?>;
        }

        .form-check-input:checked {
            background-color: <?php echo $themeColor; ?>;
            border-color: <?php echo $themeColor; ?>;
        }
    </style>

    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/sweetalert/sweetalert.css" rel="stylesheet" type="text/css" />

    <?php
    // Helper function to adjust color brightness
    function adjustBrightness($hex, $steps)
    {
        // Remove # if present
        $hex = str_replace('#', '', $hex);

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Adjust brightness
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        // Convert back to hex
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
    ?>
</head>

<body class="authentication-bg">
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <a href="#" class="d-block auth-logo" style="margin-bottom: 18px;">
                            <img src="<?php echo $logoPath; ?>" alt="Company Logo" class="img-fluid" style="max-height: 100px; max-width: 300px;">
                        </a>
                    </div>
                </div>
            </div>

            <div class="row align-items-center justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card">

                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <h5 class="text-primary">Welcome Back !</h5>
                                <p class="text-muted">Sign in to continue again.</p>
                                <a href="live-stock-public.php" class="btn btn-outline-primary btn-sm mt-2">View Live Stock (Public)</a>
                            </div>

                            <div class="p-2 mt-4">
                                <form action="#" method="post" id="login">

                                    <div class="mb-3">
                                        <label class="form-label" for="username">Username</label>
                                        <input type="text" class="form-control" name="username" id="username" placeholder="Username">
                                    </div>

                                    <div class="mb-3">
                                        <div class="float-end">
                                            <a href="forget-password.php" class="text-muted">Forgot password?</a>
                                        </div>
                                        <label class="form-label" for="userpassword">Password</label>
                                        <input type="password" class="form-control" name="password" id="password" placeholder="Password">
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="auth-remember-check">
                                        <label class="form-check-label" for="auth-remember-check">Remember me</label>
                                    </div>

                                    <div class="mt-3 text-end">
                                        <button class="btn btn-primary w-sm waves-effect waves-light" type="submit" id="login-button">Log In</button>
                                    </div>


                                </form>
                            </div>

                        </div>
                    </div>

                    <div class="mt-5 text-center" style="color:white;">
                        <p> &copy; <script>
                                document.write(new Date().getFullYear())
                            </script> AI ERP Development <i class="mdi mdi-heart text-danger"></i> by sourcecode.lk</p>
                    </div>

                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <script src="assets/libs/sweetalert/sweetalert.min.js" type="text/javascript"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <script src="ajax/js/login.js" type="text/javascript"></script>

</body>