<?php
/**
 * ============================================================================
 * GITHUB WEBHOOK RECEIVER FOR CPANEL SHARED HOSTING
 * ============================================================================
 * 
 * This script receives GitHub webhook events and triggers automated deployment
 * Designed for cPanel shared hosting without SSH access
 * 
 * Setup Instructions:
 * 1. Upload this file to: public/webhook.php
 * 2. Set webhook URL: https://yourdomain.com/webhook.php
 * 3. Configure secret in GitHub webhook settings
 * 4. Update WEBHOOK_SECRET below with the same secret
 * 5. Update DEPLOYMENT_PATH to your actual path
 * 
 * @version 2.0
 * @author Parish Management System
 */

// CRITICAL: Exit immediately - don't load Laravel
if (php_sapi_name() !== 'cli') {
    // We're in web context, handle webhook
    // Don't load anything else from Laravel
}

// ============================================================================
// CONFIGURATION - UPDATE THESE VALUES
// ============================================================================

// CRITICAL: Set a strong secret key (must match GitHub webhook secret)
define('WEBHOOK_SECRET', '7c405eeeaca081e63d0fd443c7f564b1719bf0594169601aa2a706cbea276a8a');

// Deployment path - where your Laravel application is installed
define('DEPLOYMENT_PATH', '/home2/shemidig/parish_system');

// PHP version for artisan commands
define('PHP_VERSION', '82'); // 82 = PHP 8.2, 83 = PHP 8.3, etc.

// Git repository details
define('REPO_URL', 'https://github.com/JosephNjoroge8/parish-management-system.git');
define('BRANCH', 'Main'); // Your main branch name

// Email notifications (optional)
define('NOTIFY_EMAIL', 'no_reply@parish.quovadisyouthhub.org');
define('SEND_EMAIL_NOTIFICATIONS', false); // Set to true to enable

// Allowed IP addresses (GitHub webhook IPs) - leave empty to allow all
// GitHub webhook IPs: https://api.github.com/meta
define('ALLOWED_IPS', [
    '140.82.112.0/20',
    '143.55.64.0/20',
    '185.199.108.0/22',
    '192.30.252.0/22',
    '2a0a:a440::/29',
    '2606:50c0::/32',
]);

// Log file location
define('LOG_FILE', DEPLOYMENT_PATH . '/storage/logs/webhook.log');

// ============================================================================
// DO NOT EDIT BELOW THIS LINE
// ============================================================================

// CRITICAL: Stop any Laravel autoloading
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Don't load it - we'll handle everything ourselves
}

// Set error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', DEPLOYMENT_PATH . '/storage/logs/webhook-errors.log');

// Disable any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON header immediately
header('Content-Type: application/json');
header('X-Webhook-Version: 2.0');

/**
 * Log messages with timestamp
 */
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
    
    // Ensure log directory exists
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    
    // Also output for immediate feedback
    if ($level === 'ERROR') {
        error_log($logEntry);
    }
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = []) {
    http_response_code($success ? 200 : 400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c'),
    ]);
    exit;
}

/**
 * Verify GitHub webhook signature
 */
function verifySignature($payload, $signature) {
    if (empty($signature)) {
        return false;
    }
    
    $hash = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($hash, $signature);
}

/**
 * Check if IP is allowed
 */
function isIpAllowed($ip) {
    // If no restrictions, allow all
    if (empty(ALLOWED_IPS)) {
        return true;
    }
    
    // Check if IP is in allowed ranges
    foreach (ALLOWED_IPS as $range) {
        if (strpos($range, '/') === false) {
            // Single IP
            if ($ip === $range) {
                return true;
            }
        } else {
            // CIDR range
            if (cidrMatch($ip, $range)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Check if IP matches CIDR range
 */
function cidrMatch($ip, $range) {
    list($subnet, $bits) = explode('/', $range);
    
    // IPv6
    if (strpos($ip, ':') !== false) {
        return true; // Simplified for IPv6
    }
    
    // IPv4
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask;
    
    return ($ip & $mask) == $subnet;
}

/**
 * Send email notification
 */
function sendEmailNotification($subject, $message) {
    if (!SEND_EMAIL_NOTIFICATIONS || empty(NOTIFY_EMAIL)) {
        return;
    }
    
    $headers = [
        'From: Parish System <noreply@quovadisyouthhub.org>',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: Parish Webhook'
    ];
    
    $htmlMessage = "
    <html>
    <body>
        <h2>{$subject}</h2>
        <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
        <div>{$message}</div>
    </body>
    </html>
    ";
    
    mail(NOTIFY_EMAIL, $subject, $htmlMessage, implode("\r\n", $headers));
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

try {
    logMessage('========== NEW WEBHOOK REQUEST ==========');
    logMessage('Request Method: ' . $_SERVER['REQUEST_METHOD']);
    logMessage('Remote IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    logMessage('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logMessage('Invalid request method', 'ERROR');
        sendResponse(false, 'Only POST requests are accepted');
    }
    
    // Verify IP address
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!isIpAllowed($remoteIp)) {
        logMessage("IP not allowed: {$remoteIp}", 'ERROR');
        sendResponse(false, 'Access denied');
    }
    
    // Get payload
    $payload = file_get_contents('php://input');
    if (empty($payload)) {
        logMessage('Empty payload received', 'ERROR');
        sendResponse(false, 'Empty payload');
    }
    
    // Verify signature
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (!verifySignature($payload, $signature)) {
        logMessage('Invalid signature', 'ERROR');
        sendResponse(false, 'Invalid signature');
    }
    
    logMessage('Signature verified successfully');
    
    // Parse payload
    $data = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage('Invalid JSON payload: ' . json_last_error_msg(), 'ERROR');
        sendResponse(false, 'Invalid JSON');
    }
    
    // Get event type
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown';
    logMessage("GitHub Event: {$event}");
    
    // Only process push events to the main branch
    if ($event !== 'push') {
        logMessage("Ignoring event type: {$event}");
        sendResponse(true, "Event {$event} ignored");
    }
    
    // Check branch
    $ref = $data['ref'] ?? '';
    $pushedBranch = str_replace('refs/heads/', '', $ref);
    logMessage("Branch: {$pushedBranch}");
    
    if ($pushedBranch !== BRANCH) {
        logMessage("Ignoring push to branch: {$pushedBranch}");
        sendResponse(true, "Branch {$pushedBranch} ignored");
    }
    
    // Get commit information
    $commits = $data['commits'] ?? [];
    $commitCount = count($commits);
    $pusher = $data['pusher']['name'] ?? 'unknown';
    $repository = $data['repository']['full_name'] ?? 'unknown';
    
    logMessage("Repository: {$repository}");
    logMessage("Pusher: {$pusher}");
    logMessage("Commits: {$commitCount}");
    
    // Log commit messages
    foreach ($commits as $commit) {
        $message = $commit['message'] ?? 'No message';
        $author = $commit['author']['name'] ?? 'Unknown';
        logMessage("  - {$author}: {$message}");
    }
    
    // Trigger deployment
    logMessage('Starting deployment process...');
    
    // Call the deployment script
    $deployScript = DEPLOYMENT_PATH . '/deploy.php';
    
    if (!file_exists($deployScript)) {
        logMessage('Deployment script not found: ' . $deployScript, 'ERROR');
        sendResponse(false, 'Deployment script not found');
    }
    
    // Execute deployment in background
    $deployUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/deploy.php';
    $deploySecret = hash_hmac('sha256', date('YmdH'), WEBHOOK_SECRET);
    
    // Use curl to trigger deployment asynchronously
    $ch = curl_init($deployUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $deploySecret,
        'branch' => $pushedBranch,
        'commits' => $commitCount,
        'pusher' => $pusher,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        logMessage("Deployment trigger failed: {$curlError}", 'ERROR');
        sendEmailNotification(
            'Deployment Failed',
            "Failed to trigger deployment: {$curlError}"
        );
        sendResponse(false, 'Deployment trigger failed', ['error' => $curlError]);
    }
    
    logMessage("Deployment triggered successfully (HTTP {$httpCode})");
    logMessage("Deployment response: {$response}");
    
    // Send success notification
    sendEmailNotification(
        'Deployment Triggered',
        "Deployment initiated for branch {$pushedBranch}<br>
         Commits: {$commitCount}<br>
         Pusher: {$pusher}"
    );
    
    sendResponse(true, 'Deployment triggered successfully', [
        'branch' => $pushedBranch,
        'commits' => $commitCount,
        'pusher' => $pusher,
    ]);
    
} catch (Exception $e) {
    logMessage('Exception: ' . $e->getMessage(), 'ERROR');
    logMessage('Stack trace: ' . $e->getTraceAsString(), 'ERROR');
    
    sendEmailNotification(
        'Webhook Error',
        "Exception occurred: " . $e->getMessage()
    );
    
    sendResponse(false, 'Internal error', ['error' => $e->getMessage()]);
}

// CRITICAL: Exit here to prevent Laravel from loading
exit(0);
