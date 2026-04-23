<?php

/**
 * ============================================================================
 * DEPLOYMENT SCRIPT FOR CPANEL SHARED HOSTING
 * ============================================================================
 *
 * This script handles the actual deployment process:
 * - Pulls latest code from GitHub
 * - Installs/updates dependencies
 * - Runs migrations
 * - Builds assets
 * - Clears caches
 * - Handles rollback on failure
 *
 * This script is called by webhook.php after webhook verification
 *
 * @version 1.0
 *
 * @author Parish Management System
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

define('DEPLOYMENT_PATH', '/home2/shemidig/parish_system');
define('PHP_VERSION', '82');
define('PHP_BIN', '/usr/local/bin/ea-php'.PHP_VERSION);
define('COMPOSER_BIN', '/opt/cpanel/composer/bin/composer');
define('NPM_BIN', '/usr/bin/npm');
define('GIT_BIN', '/usr/bin/git');
define('BRANCH', 'Main');
define('REPO_URL', 'https://github.com/JosephNjoroge8/parish-management-system.git');

// Deployment secret (for security)
define('DEPLOY_SECRET', '7c405eeeaca081e63d0fd443c7f564b1719bf0594169601aa2a706cbea276a8a');

// Maintenance mode settings
define('ENABLE_MAINTENANCE_MODE', true);
define('MAINTENANCE_SECRET', hash('sha256', DEPLOY_SECRET));

// Backup settings
define('ENABLE_BACKUP', true);
define('BACKUP_PATH', DEPLOYMENT_PATH.'/backups');
define('MAX_BACKUPS', 5);

// Log file
define('DEPLOY_LOG', DEPLOYMENT_PATH.'/storage/logs/deployment.log');

// Timeout settings (in seconds)
define('GIT_TIMEOUT', 120);
define('COMPOSER_TIMEOUT', 300);
define('NPM_TIMEOUT', 600);
define('MIGRATION_TIMEOUT', 180);

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Log deployment messages
 */
function deployLog($message, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";

    $logDir = dirname(DEPLOY_LOG);
    if (! is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents(DEPLOY_LOG, $logEntry, FILE_APPEND);
    echo $logEntry;
    flush();
}

/**
 * Execute shell command with timeout
 */
function execCommand($command, $timeout = 60)
{
    deployLog("Executing: {$command}");

    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorspec, $pipes);

    if (! is_resource($process)) {
        deployLog('Failed to execute command', 'ERROR');

        return ['success' => false, 'output' => '', 'error' => 'Failed to start process'];
    }

    fclose($pipes[0]);

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $output = '';
    $error = '';
    $startTime = time();

    while (true) {
        if (time() - $startTime > $timeout) {
            proc_terminate($process);
            deployLog("Command timed out after {$timeout} seconds", 'ERROR');

            return ['success' => false, 'output' => $output, 'error' => 'Timeout'];
        }

        $status = proc_get_status($process);

        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);

        if (! $status['running']) {
            break;
        }

        usleep(100000); // 0.1 second
    }

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        deployLog("Command failed with exit code {$exitCode}", 'ERROR');
        deployLog("Error output: {$error}", 'ERROR');
    }

    return [
        'success' => $exitCode === 0,
        'output' => $output,
        'error' => $error,
        'exit_code' => $exitCode,
    ];
}

/**
 * Create backup before deployment
 */
function createBackup()
{
    if (! ENABLE_BACKUP) {
        return true;
    }

    deployLog('Creating backup...');

    if (! is_dir(BACKUP_PATH)) {
        mkdir(BACKUP_PATH, 0755, true);
    }

    $backupName = 'backup_'.date('YmdHis').'.tar.gz';
    $backupFile = BACKUP_PATH.'/'.$backupName;

    $excludes = '--exclude=node_modules --exclude=vendor --exclude=storage/logs --exclude=storage/framework/cache';
    $command = 'cd '.DEPLOYMENT_PATH." && tar -czf {$backupFile} {$excludes} .";

    $result = execCommand($command, 300);

    if ($result['success']) {
        deployLog("Backup created: {$backupName}");
        cleanOldBackups();

        return true;
    }

    deployLog('Backup failed', 'ERROR');

    return false;
}

/**
 * Clean old backups
 */
function cleanOldBackups()
{
    $backups = glob(BACKUP_PATH.'/backup_*.tar.gz');

    if (count($backups) > MAX_BACKUPS) {
        usort($backups, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        $toDelete = array_slice($backups, 0, count($backups) - MAX_BACKUPS);

        foreach ($toDelete as $backup) {
            unlink($backup);
            deployLog('Deleted old backup: '.basename($backup));
        }
    }
}

/**
 * Enable maintenance mode
 */
function enableMaintenanceMode()
{
    if (! ENABLE_MAINTENANCE_MODE) {
        return true;
    }

    deployLog('Enabling maintenance mode...');

    $command = 'cd '.DEPLOYMENT_PATH.' && '.PHP_BIN.' artisan down --secret='.MAINTENANCE_SECRET.' --retry=60';
    $result = execCommand($command, 30);

    return $result['success'];
}

/**
 * Disable maintenance mode
 */
function disableMaintenanceMode()
{
    if (! ENABLE_MAINTENANCE_MODE) {
        return true;
    }

    deployLog('Disabling maintenance mode...');

    $command = 'cd '.DEPLOYMENT_PATH.' && '.PHP_BIN.' artisan up';
    $result = execCommand($command, 30);

    return $result['success'];
}

/**
 * Pull latest code from Git
 */
function pullLatestCode()
{
    deployLog('Pulling latest code from GitHub...');

    // Ensure we're in the right directory
    if (! is_dir(DEPLOYMENT_PATH.'/.git')) {
        deployLog('Git repository not found, cloning...', 'WARNING');

        $command = GIT_BIN.' clone -b '.BRANCH.' '.REPO_URL.' '.DEPLOYMENT_PATH;
        $result = execCommand($command, GIT_TIMEOUT);

        return $result['success'];
    }

    // Fetch latest changes
    $command = 'cd '.DEPLOYMENT_PATH.' && '.GIT_BIN.' fetch origin '.BRANCH;
    $result = execCommand($command, GIT_TIMEOUT);

    if (! $result['success']) {
        return false;
    }

    // Reset to latest
    $command = 'cd '.DEPLOYMENT_PATH.' && '.GIT_BIN.' reset --hard origin/'.BRANCH;
    $result = execCommand($command, GIT_TIMEOUT);

    if (! $result['success']) {
        return false;
    }

    // Clean untracked files
    $command = 'cd '.DEPLOYMENT_PATH.' && '.GIT_BIN.' clean -fd';
    $result = execCommand($command, GIT_TIMEOUT);

    return $result['success'];
}

/**
 * Install/update Composer dependencies
 */
function updateComposerDependencies()
{
    deployLog('Installing Composer dependencies...');

    $command = 'cd '.DEPLOYMENT_PATH.' && '.COMPOSER_BIN.' install --no-dev --optimize-autoloader --no-interaction --prefer-dist';
    $result = execCommand($command, COMPOSER_TIMEOUT);

    return $result['success'];
}

/**
 * Install/update NPM dependencies and build assets
 */
function buildAssets()
{
    deployLog('Installing NPM dependencies...');

    // Clean npm cache and node_modules
    $command = 'cd '.DEPLOYMENT_PATH.' && rm -rf node_modules package-lock.json';
    execCommand($command, 60);

    // Install dependencies
    $command = 'cd '.DEPLOYMENT_PATH.' && '.NPM_BIN.' install --production=false';
    $result = execCommand($command, NPM_TIMEOUT);

    if (! $result['success']) {
        return false;
    }

    deployLog('Building production assets...');

    // Build assets
    $command = 'cd '.DEPLOYMENT_PATH.' && '.NPM_BIN.' run build';
    $result = execCommand($command, NPM_TIMEOUT);

    if (! $result['success']) {
        return false;
    }

    // Verify build output
    if (! file_exists(DEPLOYMENT_PATH.'/public/build/manifest.json')) {
        deployLog('Build manifest not found', 'ERROR');

        return false;
    }

    deployLog('Assets built successfully');

    return true;
}

/**
 * Run database migrations
 */
function runMigrations()
{
    deployLog('Running database migrations...');

    $command = 'cd '.DEPLOYMENT_PATH.' && '.PHP_BIN.' artisan migrate --force --no-interaction';
    $result = execCommand($command, MIGRATION_TIMEOUT);

    return $result['success'];
}

/**
 * Clear and cache Laravel configurations
 */
function optimizeLaravel()
{
    deployLog('Optimizing Laravel...');

    $commands = [
        'config:clear',
        'cache:clear',
        'route:clear',
        'view:clear',
        'event:clear',
        'config:cache',
        'route:cache',
        'view:cache',
        'event:cache',
    ];

    foreach ($commands as $cmd) {
        $command = 'cd '.DEPLOYMENT_PATH.' && '.PHP_BIN." artisan {$cmd}";
        $result = execCommand($command, 60);

        if (! $result['success']) {
            deployLog("Failed to execute: {$cmd}", 'WARNING');
        }
    }

    return true;
}

/**
 * Set proper file permissions
 */
function setPermissions()
{
    deployLog('Setting file permissions...');

    $commands = [
        'chmod -R 755 '.DEPLOYMENT_PATH,
        'chmod -R 775 '.DEPLOYMENT_PATH.'/storage',
        'chmod -R 775 '.DEPLOYMENT_PATH.'/bootstrap/cache',
        'find '.DEPLOYMENT_PATH.'/storage -type f -exec chmod 664 {} \\;',
        'find '.DEPLOYMENT_PATH.'/bootstrap/cache -type f -exec chmod 664 {} \\;',
    ];

    foreach ($commands as $command) {
        execCommand($command, 60);
    }

    return true;
}

/**
 * Create storage link
 */
function createStorageLink()
{
    deployLog('Creating storage link...');

    $command = 'cd '.DEPLOYMENT_PATH.' && '.PHP_BIN.' artisan storage:link';
    $result = execCommand($command, 30);

    return $result['success'];
}

/**
 * Send deployment notification
 */
function sendDeploymentNotification($success, $duration, $errors = [])
{
    $status = $success ? 'SUCCESS' : 'FAILED';
    $color = $success ? 'green' : 'red';

    deployLog("========== DEPLOYMENT {$status} ==========");
    deployLog("Duration: {$duration} seconds");

    if (! empty($errors)) {
        deployLog('Errors encountered:', 'ERROR');
        foreach ($errors as $error) {
            deployLog("  - {$error}", 'ERROR');
        }
    }
}

/**
 * Rollback deployment
 */
function rollbackDeployment()
{
    deployLog('Rolling back deployment...', 'WARNING');

    // Find latest backup
    $backups = glob(BACKUP_PATH.'/backup_*.tar.gz');

    if (empty($backups)) {
        deployLog('No backups found for rollback', 'ERROR');

        return false;
    }

    usort($backups, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latestBackup = $backups[0];
    deployLog('Restoring from: '.basename($latestBackup));

    $command = 'cd '.DEPLOYMENT_PATH." && tar -xzf {$latestBackup}";
    $result = execCommand($command, 300);

    if ($result['success']) {
        deployLog('Rollback completed successfully');
        optimizeLaravel();

        return true;
    }

    deployLog('Rollback failed', 'ERROR');

    return false;
}

// ============================================================================
// MAIN DEPLOYMENT PROCESS
// ============================================================================

// Verify request is authorized
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret = $_POST['secret'] ?? '';
    $expectedSecret = hash_hmac('sha256', date('YmdH'), DEPLOY_SECRET);

    if ($secret !== $expectedSecret) {
        deployLog('Unauthorized deployment request', 'ERROR');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

// Start deployment
$startTime = time();
$errors = [];
$success = true;

deployLog('========================================');
deployLog('STARTING DEPLOYMENT PROCESS');
deployLog('========================================');
deployLog('Timestamp: '.date('Y-m-d H:i:s'));
deployLog('Branch: '.BRANCH);
deployLog('Path: '.DEPLOYMENT_PATH);

try {
    // Step 1: Create backup
    if (! createBackup()) {
        $errors[] = 'Backup creation failed';
        deployLog('Continuing without backup...', 'WARNING');
    }

    // Step 2: Enable maintenance mode
    if (! enableMaintenanceMode()) {
        $errors[] = 'Failed to enable maintenance mode';
    }

    // Step 3: Pull latest code
    if (! pullLatestCode()) {
        $errors[] = 'Failed to pull latest code';
        $success = false;
        throw new Exception('Git pull failed');
    }

    // Step 4: Update Composer dependencies
    if (! updateComposerDependencies()) {
        $errors[] = 'Failed to update Composer dependencies';
        $success = false;
        throw new Exception('Composer install failed');
    }

    // Step 5: Build assets
    if (! buildAssets()) {
        $errors[] = 'Failed to build assets';
        $success = false;
        throw new Exception('Asset build failed');
    }

    // Step 6: Run migrations
    if (! runMigrations()) {
        $errors[] = 'Failed to run migrations';
        // Don't fail deployment for migration errors
    }

    // Step 7: Optimize Laravel
    optimizeLaravel();

    // Step 8: Set permissions
    setPermissions();

    // Step 9: Create storage link
    createStorageLink();

    // Step 10: Disable maintenance mode
    disableMaintenanceMode();

} catch (Exception $e) {
    deployLog('Deployment failed: '.$e->getMessage(), 'ERROR');
    $success = false;

    // Attempt rollback
    if (ENABLE_BACKUP) {
        rollbackDeployment();
    }

    disableMaintenanceMode();
}

// Calculate duration
$duration = time() - $startTime;

// Send notification
sendDeploymentNotification($success, $duration, $errors);

// Return response
http_response_code($success ? 200 : 500);
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'duration' => $duration,
    'errors' => $errors,
    'timestamp' => date('c'),
]);
