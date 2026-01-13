<?php
// Prevent any output before headers
if (ob_get_level() === 0) {
    ob_start();
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$USER = new User(NULL);
if (!$USER->authenticate()) {
    header('Location: login.php');
    exit();
}

// Handle system status redirects
$currentPage = basename($_SERVER['PHP_SELF']);

// Default to system up (0) if status not set, and ensure it's an integer
$systemStatus = isset($_SESSION['system_down_status']) ? (int)$_SESSION['system_down_status'] : 0;

// Debug output (remove after testing)
error_log("Auth Check - System Status: " . $systemStatus . ", Current Page: " . $currentPage . ", Session ID: " . session_id());

// If system is down (1) and not on payment page, redirect to payment page
if ($systemStatus === 1 && $currentPage !== 'system-payment-required.php') {
    header('Location: system-payment-required.php');
    exit();
}
// If system is up (0) and on payment page, redirect to index
elseif ($systemStatus === 0 && $currentPage === 'system-payment-required.php') {
    header('Location: index.php');
    exit();
}

// Only proceed with permission checks if not redirected
$USER_PERMISSION = new UserPermission();

// Get the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Add non-permission pages dynamically
$NP = new NonPermissionPage();
$nonPermissionPages = $NP->all(); // fetch all non-permission pages

// Initialize skipPages array
$skipPages = [];

// Add system-payment-required.php to skip pages
$skipPages[] = 'system-payment-required.php';

foreach ($nonPermissionPages as $page) {
    $skipPages[] = $page['page']; // add page name to skipPages array
}

// Check access if current page is not in skipPages
if (!in_array($current_page, $skipPages)) {
    $page_id = $_GET['page_id'] ?? null;
    $USER_PERMISSION->checkAccess($page_id);
}

// Get company details
$US = new User($_SESSION['id']);
$company_id = $US->company_id;

$COMPANY_PROFILE_DETAILS = new CompanyProfile($company_id);

// Add account year start date and end date 
$year_start = '2025-04-01';
$year_end = '2026-03-31';

$DOCUMENT_TRACKINGS = new DocumentTracking(NULL);
$doc_id = $DOCUMENT_TRACKINGS->getAllByCompanyAndYear($company_id, $year_start, $year_end);

$PERMISSIONS = $USER_PERMISSION->hasPermission($_SESSION['id'], $page_id ?? 0);
