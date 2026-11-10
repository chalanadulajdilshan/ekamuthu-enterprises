<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// The app is served from a sub-folder whose .htaccess rewrites into public/
// (…/pos-system/x -> …/pos-system/public/x). Tell Symfony the script lives one
// level up so the base URL is "…/pos-system" (routes match, generated links
// have no "/public"). No-op when the document root already points at public/.
$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (str_ends_with($script, '/public/index.php')
    && ! str_contains($_SERVER['REQUEST_URI'] ?? '', '/public/')) {
    $_SERVER['SCRIPT_NAME'] = substr($script, 0, -strlen('/public/index.php')).'/index.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
