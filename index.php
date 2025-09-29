<?php

/**
 * Parish Management System
 * 
 * cPanel-compatible index.php that works from document root
 */

// Define directory containing this file as the project root
define('LARAVEL_ROOT', __DIR__);

// Check if the public directory contains the original index.php
if (file_exists(LARAVEL_ROOT.'/public/index.php')) {
    // Require the original index.php from the public directory
    require LARAVEL_ROOT.'/public/index.php';
} else {
    die('Could not find the public/index.php file.');
}
