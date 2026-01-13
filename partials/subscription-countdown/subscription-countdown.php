<?php

/**
 * Subscription Countdown Partial
 * Displays a countdown banner when monthly payment is due within 10 days
 */

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

// Get system down status from session
$systemDownStatus = isset($_SESSION['system_down_status']) ? $_SESSION['system_down_status'] : null;
$systemDownLastUpdated = isset($_SESSION['system_down_last_updated']) ? $_SESSION['system_down_last_updated'] : null;

?>

<div class="row mb-4">
    <div class="col-12">
        <div class="welcome-card" style="
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            color: #ffffff;
            position: relative;
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-left: 1px solid rgba(255, 255, 255, 0.05);
            min-height: 190px;
        ">
            <div class="card-body p-4 position-relative">
                <div class="position-absolute d-none d-lg-block" style="right: -90px; top: 50%; transform: translateY(-50%) scale(1.25); transform-origin: right center; z-index: 1;">
                    <div style="position: relative; width: 320px; height: 260px;">
                        <div style="
                            position: absolute;
                            inset: 0;
                            opacity: 0.25;
                            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.35), rgba(255,255,255,0) 60%);
                            border-radius: 50%;
                        "></div>

                        <div style="position: absolute; inset: 0;">
                            <div style="
                                position: absolute;
                                top: 10px;
                                right: 18px;
                                width: 220px;
                                height: 220px;
                                background: rgba(255,255,255,0.12);
                                border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
                                animation: morph 8s ease-in-out infinite;
                            "></div>

                            <div style="
                                position: absolute;
                                top: 40px;
                                right: 55px;
                                width: 190px;
                                height: 190px;
                                border: 4px solid rgba(255,255,255,0.22);
                                border-radius: 50%;
                            "></div>
                            <div style="
                                position: absolute;
                                top: 5px;
                                right: 5px;
                                width: 140px;
                                height: 140px;
                                border: 3px solid rgba(0,0,0,0.20);
                                border-radius: 50%;
                            "></div>
                            <div style="
                                position: absolute;
                                top: 120px;
                                right: 25px;
                                width: 70px;
                                height: 70px;
                                border: 3px solid rgba(255,255,255,0.35);
                                border-radius: 50%;
                            "></div>
                            <div style="
                                position: absolute;
                                top: 25px;
                                right: 190px;
                                width: 55px;
                                height: 55px;
                                border: 3px solid rgba(255,255,255,0.35);
                                border-radius: 50%;
                            "></div>
                        </div>

                        <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; animation: float 6s ease-in-out infinite;">
                            <img src="assets/images/welcome.png" alt="Welcome" class="img-fluid position-relative" style="max-height: 240px; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));">
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-center" style="position: relative; z-index: 2;">
                    <div class="flex-shrink-0 me-4 mb-3 mb-md-0 position-relative">
                        <div class="avatar-xxl position-relative">
                            <div class="position-absolute" style="
                                width: 100px;
                                height: 100px;
                                background: rgba(255,255,255,0.15);
                                border-radius: 50%;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                z-index: 0;
                                animation: pulse 2s infinite;
                            "></div>
                            <img src="<?php echo $profileImage; ?>" alt="Profile" class="img-fluid rounded-circle position-relative" style="
                                width: 90px;
                                height: 90px;
                                object-fit: cover;
                                border: 3px solid rgba(255,255,255,0.9);
                                box-shadow: 0 6px 24px rgba(0,0,0,0.1);
                                z-index: 1;
                            " onerror="this.onerror=null; this.src='<?php echo $defaultImage; ?>'">
                        </div>
                    </div>

                    <div class="flex-grow-1 text-center text-md-start">
                        <h2 class="mb-2" style="
                            font-weight: 700;
                            font-size: 1.8rem;
                            background: linear-gradient(90deg, #ffffff, #e6f9ff);
                            -webkit-background-clip: text;
                            background-clip: text;
                            -webkit-text-fill-color: transparent;
                            text-shadow: 0 2px 8px rgba(26, 41, 128, 0.2);
                            display: inline-block;
                        ">
                            Welcome back, <span style="
                                background: linear-gradient(90deg, #ffd700, #ffb700);
                                -webkit-background-clip: text;
                                background-clip: text;
                                -webkit-text-fill-color: transparent;
                                text-shadow: 0 2px 8px rgba(255, 183, 0, 0.2);
                                font-weight: 800;
                            "><?php echo htmlspecialchars($US->name); ?></span>!
                        </h2>

                        <p class="mb-3" style="font-size: 1.1rem; opacity: 0.9; max-width: 650px;">
                            <?php
                            date_default_timezone_set('Asia/Colombo');
                            $current_hour = (int)date('H');
                            $greeting = '';
                            $icon = '';
                            if ($current_hour < 12) {
                                $greeting = 'Good Morning';
                                $icon = '☀️';
                            } elseif ($current_hour < 17) {
                                $greeting = 'Good Afternoon';
                                $icon = '🌤️';
                            } else {
                                $greeting = 'Good Evening';
                                $icon = '🌙';
                            }
                            echo "<span class='d-inline-flex align-items-center'>";
                            echo "<span class='me-2' style='display: inline-block;'>$icon</span>";
                            echo "<span><span class='fw-medium'>$greeting!</span> Here's what's happening with your store today.</span>";
                            echo "</span>";
                            ?>
                        </p>

                        <div id="subscription-timer-wrapper" class="d-none" style="
                            padding: 0;
                            border-radius: 14px;
                            box-shadow: none;
                        ">
                            <div id="subscription-timer-label" style="
                                font-weight: 900;
                                font-size: 0.98rem;
                                margin: 0 0 6px;
                                text-transform: uppercase;
                                letter-spacing: 0.8px;
                                color: #ffffff;
                                text-shadow: none;
                                text-align: center;
                            ">Payment due in</div>
                            <div id="subscription-syotimer" class="syotimer"></div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-3">
                            <span class="badge" style="
                                background: rgba(255,255,255,0.15);
                                backdrop-filter: blur(5px);
                                border: 1px solid rgba(255,255,255,0.2);
                                font-size: 0.9rem;
                                font-weight: 500;
                                padding: 0.5rem 1rem;
                                border-radius: 50px;
                                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                            ">
                                <i class="bx bx-calendar me-2"></i> <?php echo date('l, F j, Y'); ?>
                            </span>
                            <span class="badge" style="
                                background: rgba(255,255,255,0.15);
                                backdrop-filter: blur(5px);
                                border: 1px solid rgba(255,255,255,0.2);
                                font-size: 0.9rem;
                                font-weight: 500;
                                padding: 0.5rem 1rem;
                                border-radius: 50px;
                                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                            ">
                                <i class="bx bx-time me-2"></i> <span id="time-display"><?php echo date('h:i A'); ?></span>
                            </span>

                            <span class="badge d-none" id="subscription-badge" style="
                                background: rgba(255,255,255,0.15);
                                backdrop-filter: blur(5px);
                                border: 1px solid rgba(255,255,255,0.2);
                                font-size: 0.9rem;
                                font-weight: 600;
                                padding: 0.5rem 1rem;
                                border-radius: 50px;
                                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                            ">
                                <i class="bx bx-error-circle me-2"></i>
                                <span id="subscription-status-text"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="position-absolute" style="
                    bottom: -50px;
                    right: -50px;
                    width: 250px;
                    height: 250px;
                    background: rgba(255,255,255,0.05);
                    border-radius: 50%;
                    z-index: 0;
                "></div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        const el = document.getElementById('time-display');
        if (el) el.textContent = timeString;
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>

<style>
    @keyframes float {
        0% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }

        100% {
            transform: translateY(0px);
        }
    }

    @keyframes morph {
        0% {
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        }

        50% {
            border-radius: 58% 42% 35% 65% / 55% 50% 50% 45%;
        }

        100% {
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        }
    }

    @keyframes blinkRed {
        0% {
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 0 rgba(245, 54, 92, 0.0);
        }

        50% {
            opacity: 0.55;
            transform: scale(1.02);
            box-shadow: 0 0 25px rgba(245, 54, 92, 0.45);
        }

        100% {
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 0 rgba(245, 54, 92, 0.0);
        }
    }

    #subscription-timer-wrapper .syotimer {
        display: inline-block;
        margin: 0;
        padding: 0;
        transform: scale(0.75);
        transform-origin: top center;
    }

    @media (min-width: 992px) {
        #subscription-timer-wrapper {
            position: absolute;
            top: 6px;
            right: 260px;
            z-index: 3;
            margin: 0;
        }
    }

    @media (max-width: 991.98px) {
        #subscription-timer-wrapper {
            margin: 10px 0 0;
        }
    }
</style>

<script src="partials/subscription-countdown/ajax/js/system-down-status.js"></script>

<script>
    // Ensure jQuery is loaded before running system down status
    if (typeof jQuery === 'undefined') {
        console.warn('jQuery not loaded, system down status will not work');
    } else {
        // jQuery is available, the script will initialize automatically
    }
</script>