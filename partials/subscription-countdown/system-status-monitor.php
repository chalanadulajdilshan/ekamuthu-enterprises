<?php
/**
 * System Status Monitor
 * Include this file in pages that need real-time system down detection.
 * This will automatically redirect to system-payment-required.php when system is down.
 */

// Get base path for AJAX calls
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');
$monitor_base_path = $isLocal ? '/360-ERP/' : '/';
?>

<script>
(function() {
    const MONITOR_BASE_PATH = '<?php echo $monitor_base_path; ?>';
    const CHECK_INTERVAL = 30000; // Check every 30 seconds
    const PAYMENT_PAGE = 'system-payment-required.php';
    
    // Don't run on the payment required page itself
    const currentPage = window.location.pathname.split('/').pop();
    if (currentPage === PAYMENT_PAGE) {
        return;
    }
    
    function checkSystemStatus() {
        fetch(MONITOR_BASE_PATH + 'partials/subscription-countdown/ajax/php/get-system-down-status.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data.system_down === 1) {
                    // System is down, redirect to payment page immediately
                    window.location.href = MONITOR_BASE_PATH + PAYMENT_PAGE;
                }
            })
            .catch(error => {
                console.error('System status check failed:', error);
            });
    }
    
    // Check immediately on page load
    checkSystemStatus();
    
    // Then check periodically
    setInterval(checkSystemStatus, CHECK_INTERVAL);
    
    // Also check when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            checkSystemStatus();
        }
    });
})();
</script>
