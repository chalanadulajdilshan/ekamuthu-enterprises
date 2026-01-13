<?php
// Prevent any output before headers
if (ob_get_level() === 0) {
    ob_start();
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Debug output (remove after testing)
error_log("Payment Required Page - Session Status: " . session_status() . ", ID: " . session_id());

/**
 * System Payment Required Page
 * Displays when system is down due to non-payment
 */

// Include required classes
require_once 'class/include.php';

// Check system status first
$systemStatus = isset($_SESSION['system_down_status']) ? (int)$_SESSION['system_down_status'] : 0;

// If system is up, redirect to index
if ($systemStatus === 0) {
    header('Location: index.php');
    exit();
}

// Include auth after system status check
require_once 'auth.php';

// This page should only be accessible when system is down (status = 0)
// The auth.php handles the redirection logic

$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');
$base_path = $isLocal ? '/360-ERP/' : '/';
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $base_path;

$fs_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . rtrim($base_path, '/');

$defaultImage = $base_url . 'assets/images/users/avatar-1.jpg';
$profileImage = $defaultImage;

if (isset($US) && !empty($US->image_name)) {
    $image_path = $fs_root . '/upload/users/' . $US->image_name;
    if (file_exists($image_path)) {
        $profileImage = $base_url . 'upload/users/' . $US->image_name;
    }
} elseif (isset($COMPANY_PROFILE_DETAILS) && !empty($COMPANY_PROFILE_DETAILS->image_name)) {
    $logo_path = $fs_root . '/uploads/company-logos/' . $COMPANY_PROFILE_DETAILS->image_name;
    if (file_exists($logo_path)) {
        $profileImage = $base_url . 'uploads/company-logos/' . $COMPANY_PROFILE_DETAILS->image_name;
    }
}

$profileImage .= '?v=' . time();
$systemDownLastUpdated = isset($_SESSION['system_down_last_updated']) ? $_SESSION['system_down_last_updated'] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Required - System Activation</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons CSS -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .payment-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .payment-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .payment-header {
            background: linear-gradient(135deg, #f5365c 0%, #f56036 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }

        .payment-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .payment-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .payment-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .payment-body {
            padding: 40px;
        }

        .status-badge {
            display: inline-block;
            background: rgba(245, 54, 92, 0.1);
            color: #f5365c;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            border: 2px solid rgba(245, 54, 92, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }

        .feature-list li {
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list i {
            font-size: 1.5rem;
            color: #f5365c;
            margin-right: 15px;
        }

        .contact-info {
            background: rgba(245, 54, 92, 0.05);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }

        .contact-info h4 {
            color: #f5365c;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .contact-method {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .contact-method i {
            font-size: 1.2rem;
            color: #f5365c;
            margin-right: 15px;
            width: 30px;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(245, 54, 92, 0.2);
            margin-right: 15px;
        }

        .user-info h5 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .user-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .payment-header h1 {
                font-size: 2rem;
            }

            .payment-body {
                padding: 20px;
            }

            .payment-header {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <i class="bx bx-lock-alt" style="font-size: 4rem; margin-bottom: 20px;"></i>
                <h1>System Access Suspended</h1>
                <p>Your system access has been temporarily suspended due to payment issues</p>
            </div>

            <div class="payment-body">
                <div class="user-profile">
                    <img src="<?php echo $profileImage; ?>" alt="Profile" class="user-avatar" onerror="this.src='<?php echo $defaultImage; ?>'">
                    <div class="user-info">
                        <h5><?php echo htmlspecialchars($US->name); ?></h5>
                        <p>Account ID: #<?php echo htmlspecialchars($US->id); ?></p>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <span class="status-badge">
                        <i class="bx bx-error-circle me-2"></i>
                        PAYMENT REQUIRED
                    </span>
                </div>

                <div class="alert alert-warning" role="alert">
                    <i class="bx bx-info-circle me-2"></i>
                    <strong>Important:</strong> Your system access has been suspended. To reactivate your system, please complete the payment process.
                </div>

                <h4 class="mb-3">What happens when payment is completed:</h4>
                <ul class="feature-list">
                    <li>
                        <i class="bx bx-check-circle"></i>
                        <div>
                            <strong>Immediate System Reactivation</strong>
                            <p class="mb-0 text-muted">Your system will be activated instantly after payment confirmation</p>
                        </div>
                    </li>
                    <li>
                        <i class="bx bx-check-circle"></i>
                        <div>
                            <strong>Full Access Restored</strong>
                            <p class="mb-0 text-muted">All features and functionalities will be available again</p>
                        </div>
                    </li>
                    <li>
                        <i class="bx bx-check-circle"></i>
                        <div>
                            <strong>24/7 Support Access</strong>
                            <p class="mb-0 text-muted">Get priority support for any technical assistance</p>
                        </div>
                    </li>
                    <li>
                        <i class="bx bx-check-circle"></i>
                        <div>
                            <strong>Regular Updates</strong>
                            <p class="mb-0 text-muted">Continue receiving system updates and new features</p>
                        </div>
                    </li>
                </ul>

                <div class="contact-info">
                    <h4><i class="bx bx-phone-call me-2"></i>Contact Support for Payment</h4>

                    <div class="contact-method">
                        <i class="bx bx-phone"></i>
                        <div>
                            <strong>Hotline:</strong> 072 8888 550
                            <p class="mb-0 text-muted">Available 24/7 for urgent payment assistance</p>
                        </div>
                    </div>

                    <div class="contact-method">
                        <i class="bx bx-envelope"></i>
                        <div>
                            <strong>Email:</strong> info@sourcecode.lk 
                            <p class="mb-0 text-muted">Send payment confirmations and inquiries</p>
                        </div>
                    </div>

                    <div class="contact-method">
                        <i class="bx bx-message-rounded"></i>
                        <div>
                            <strong>WhatsApp:</strong> 072 8888 550
                            <p class="mb-0 text-muted">Quick payment support via WhatsApp</p>
                        </div>
                    </div>
                </div>

                <?php if ($systemDownLastUpdated): ?>
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bx bx-time-five me-1"></i>
                            Status last updated: <?php echo date('F j, Y, g:i A', strtotime($systemDownLastUpdated)); ?>
                        </small>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button class="refresh-btn" onclick="checkPaymentStatus()">
                        <i class="bx bx-refresh me-2"></i>
                        Check Payment Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        async function checkPaymentStatus() {
            const btn = document.querySelector('.refresh-btn');
            const originalText = btn.innerHTML;

            try {
                // Show loading state
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Checking...';

                const response = await fetch('partials/subscription-countdown/ajax/php/get-system-down-status.php');
                const data = await response.json();

                if (data.status === 'success') {
                    if (data.data.system_down === 0) {
                        // System is back up, show success and redirect
                        await Swal.fire({
                            icon: 'success',
                            title: 'Payment Verified!',
                            text: 'Your payment has been processed successfully. Redirecting you to the dashboard...',
                            showConfirmButton: false,
                            timer: 2000,
                            didClose: () => {
                                window.location.href = 'index.php';
                            }
                        });
                    } else {
                        // System is still down
                        await Swal.fire({
                            icon: 'info',
                            title: 'Payment Pending',
                            text: 'Your payment is still being processed. Please try again later.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#667eea'
                        });
                    }
                } else {
                    throw new Error(data.message || 'Failed to check payment status');
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to check payment status. Please try again later.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#f5365c'
                });
            } finally {
                // Restore button state
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // Auto-check payment status every 5 minutes (without showing alerts)
        function autoCheckStatus() {
            fetch('partials/subscription-countdown/ajax/php/get-system-down-status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data.system_down === 0) {
                        window.location.href = 'index.php';
                    }
                })
                .catch(console.error);
        }

        // Initial auto-check
        autoCheckStatus();

        // Set up interval for auto-check
        setInterval(autoCheckStatus, 300000);
    </script>
</body>

</html>