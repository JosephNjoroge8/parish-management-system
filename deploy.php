<?php
/**
 * Parish Management System - cPanel Deployment Script
 * 
 * This script handles automatic deployment when code is pushed to GitHub
 * Place this file in your cPanel public_html directory
 */

// Security: Only allow execution from GitHub webhooks or manual trigger with secret
$secret = 'your_webhook_secret_here_change_this'; // Change this to a secure random string
$github_signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');

// Log deployment attempts
$log_file = __DIR__ . '/deployment.log';
$timestamp = date('Y-m-d H:i:s');

function log_message($message) {
    global $log_file, $timestamp;
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

function verify_signature($payload, $signature, $secret) {
    $expected_signature = 'sha1=' . hash_hmac('sha1', $payload, $secret);
    return hash_equals($expected_signature, $signature);
}

// For manual deployment, check for secret parameter
if (isset($_GET['deploy']) && $_GET['deploy'] === $secret) {
    log_message("Manual deployment triggered");
    $manual_deploy = true;
} else {
    $manual_deploy = false;
    // Verify GitHub webhook signature
    if (!verify_signature($payload, $github_signature, $secret)) {
        log_message("Invalid signature or unauthorized access attempt");
        http_response_code(403);
        exit('Unauthorized');
    }
}

log_message("=== DEPLOYMENT STARTED ===");

try {
    // Change to your repository directory
    $repo_path = __DIR__ . '/parish-management-system'; // Adjust this path
    
    if (!is_dir($repo_path)) {
        throw new Exception("Repository directory not found: $repo_path");
    }
    
    chdir($repo_path);
    log_message("Changed to repository directory: $repo_path");
    
    // Pull latest changes from GitHub
    log_message("Pulling latest changes from GitHub...");
    $output = shell_exec('git pull origin main 2>&1');
    log_message("Git pull output: $output");
    
    // Check if composer.json exists and run composer install
    if (file_exists('composer.json')) {
        log_message("Running composer install...");
        $composer_output = shell_exec('composer install --no-dev --optimize-autoloader 2>&1');
        log_message("Composer output: $composer_output");
    }
    
    // Check if package.json exists and build assets
    if (file_exists('package.json')) {
        log_message("Installing npm dependencies...");
        $npm_install = shell_exec('npm ci 2>&1');
        log_message("NPM install output: $npm_install");
        
        log_message("Building production assets...");
        $npm_build = shell_exec('npm run build 2>&1');
        log_message("NPM build output: $npm_build");
    }
    
    // Check and preserve existing .env file
    if (!file_exists('.env')) {
        if (file_exists('.env.cpanel')) {
            log_message("No .env found, copying from .env.cpanel template");
            copy('.env.cpanel', '.env');
        } else {
            log_message("WARNING: No .env file found and no template available");
        }
    } else {
        log_message("Existing .env file preserved - not overwriting");
    }
    
    // Laravel specific commands
    if (file_exists('artisan')) {
        log_message("Running Laravel optimization commands...");
        
        // Clear caches
        $cache_clear = shell_exec('php artisan cache:clear 2>&1');
        log_message("Cache clear: $cache_clear");
        
        $config_clear = shell_exec('php artisan config:clear 2>&1');
        log_message("Config clear: $config_clear");
        
        $route_clear = shell_exec('php artisan route:clear 2>&1');
        log_message("Route clear: $route_clear");
        
        $view_clear = shell_exec('php artisan view:clear 2>&1');
        log_message("View clear: $view_clear");
        
        // Optimize for production
        $config_cache = shell_exec('php artisan config:cache 2>&1');
        log_message("Config cache: $config_cache");
        
        $route_cache = shell_exec('php artisan route:cache 2>&1');
        log_message("Route cache: $route_cache");
        
        $view_cache = shell_exec('php artisan view:cache 2>&1');
        log_message("View cache: $view_cache");
        
        // Only run migrations if specifically enabled (safety measure)
        if (getenv('AUTO_MIGRATE') === 'true') {
            $migrate = shell_exec('php artisan migrate --force 2>&1');
            log_message("Migration output: $migrate");
        } else {
            log_message("Migrations skipped (set AUTO_MIGRATE=true to enable)");
        }
        
        // Ensure authentication system is always up to date
        $seed_roles = shell_exec('php artisan db:seed --class=RolePermissionSeeder --force 2>&1');
        log_message("Role seeding: $seed_roles");
        
        // Create storage link if needed
        if (!file_exists('public/storage')) {
            $storage_link = shell_exec('php artisan storage:link 2>&1');
            log_message("Storage link: $storage_link");
        }
    }
    
    // Set proper permissions
    log_message("Setting file permissions...");
    if (is_dir('storage')) {
        shell_exec('chmod -R 755 storage');
        shell_exec('chmod -R 755 bootstrap/cache');
    }
    
    log_message("=== DEPLOYMENT COMPLETED SUCCESSFULLY ===");
    
    // Send success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment completed successfully',
        'timestamp' => $timestamp
    ]);
    
} catch (Exception $e) {
    log_message("DEPLOYMENT FAILED: " . $e->getMessage());
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => $timestamp
    ]);
}

log_message("=== DEPLOYMENT PROCESS ENDED ===\n");
?>