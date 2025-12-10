<?php
/**
 * Webhook Diagnostic Script
 * Test GitHub webhook connectivity without security checks
 */

define('LOG_FILE', __DIR__ . '/../storage/logs/webhook-diagnostic.log');

function logDiag($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    echo $logEntry;
}

header('Content-Type: application/json');

logDiag('========== WEBHOOK DIAGNOSTIC ==========');
logDiag('Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
logDiag('Remote IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
logDiag('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

// Log all headers
logDiag('--- Headers ---');
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        logDiag("{$key}: {$value}");
    }
}

// Log payload
$payload = file_get_contents('php://input');
logDiag('--- Payload ---');
logDiag('Payload length: ' . strlen($payload));
logDiag('First 200 chars: ' . substr($payload, 0, 200));

// Try to decode
$data = json_decode($payload, true);
if ($data) {
    logDiag('--- Parsed Data ---');
    logDiag('Event: ' . ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown'));
    logDiag('Ref: ' . ($data['ref'] ?? 'unknown'));
    logDiag('Repository: ' . ($data['repository']['full_name'] ?? 'unknown'));
    
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    logDiag('Signature present: ' . ($signature ? 'YES' : 'NO'));
    if ($signature) {
        logDiag('Signature: ' . substr($signature, 0, 20) . '...');
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Diagnostic complete - check storage/logs/webhook-diagnostic.log',
    'timestamp' => date('c')
]);
