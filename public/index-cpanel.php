<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine the base path for cPanel deployment
$basePath = __DIR__.'/parish_system';

// Check if we're in cPanel structure (Laravel app in subdirectory)
if (file_exists($basePath.'/bootstrap/app.php')) {
    $appPath = $basePath;
} else {
    // Local development structure
    $appPath = __DIR__.'/..';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
