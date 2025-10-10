<?php
/**
 * PARISH MANAGEMENT SYSTEM - PRODUCTION DEPLOYMENT HELPER
 * Upload this to your production server and visit: https://parish.quovadisyouthhub.org/production-helper.php
 * DELETE THIS FILE after successful deployment!
 */

if (php_sapi_name() === 'cli') {
    die("This script should only be run via web browser\n");
}

$basePath = dirname(__DIR__);
$action = $_GET['action'] ?? '';
$output = '';

if ($action && file_exists($basePath . '/artisan')) {
    chdir($basePath);
    
    switch ($action) {
        case 'generate_key':
            if (!file_exists('.env')) {
                $output = "<div style='color: red;'>❌ .env file not found. Please create it first.</div>";
            } else {
                exec('php artisan key:generate 2>&1', $result, $returnCode);
                if ($returnCode === 0) {
                    $output = "<div style='color: green;'>✅ Application key generated successfully!</div>";
                } else {
                    $output = "<div style='color: red;'>❌ Failed to generate key: " . implode('<br>', $result) . "</div>";
                }
            }
            break;
            
        case 'migrate':
            exec('php artisan migrate --force 2>&1', $result, $returnCode);
            if ($returnCode === 0) {
                $output = "<div style='color: green;'>✅ Database migrations completed successfully!</div>";
            } else {
                $output = "<div style='color: red;'>❌ Migration failed: " . implode('<br>', $result) . "</div>";
            }
            break;
            
        case 'optimize':
            $commands = ['config:cache', 'route:cache', 'view:cache', 'optimize'];
            $output = "<div><h3>🔧 Running optimization commands...</h3>";
            foreach ($commands as $cmd) {
                exec("php artisan $cmd 2>&1", $result, $returnCode);
                if ($returnCode === 0) {
                    $output .= "<div style='color: green;'>✅ $cmd completed</div>";
                } else {
                    $output .= "<div style='color: orange;'>⚠️ $cmd: " . implode(' ', $result) . "</div>";
                }
            }
            $output .= "</div>";
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parish Management System - Production Helper</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; margin: 10px 5px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .danger { background: #dc3545; }
        .danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>🚀 Parish Management System - Production Helper</h1>
    
    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong> Delete this file immediately after deployment completion!
    </div>

    <?php if ($output): ?>
        <div class="success"><?php echo $output; ?></div>
    <?php endif; ?>

    <h2>System Information</h2>
    <ul>
        <li>PHP Version: <?php echo PHP_VERSION; ?></li>
        <li>Laravel Detected: <?php echo file_exists($basePath . '/artisan') ? '✅ Yes' : '❌ No'; ?></li>
        <li>.env File: <?php echo file_exists($basePath . '/.env') ? '✅ Present' : '❌ Missing'; ?></li>
        <li>Storage Writable: <?php echo is_writable($basePath . '/storage') ? '✅ Yes' : '❌ No'; ?></li>
    </ul>

    <h2>Build Assets Status</h2>
    <?php if (is_dir(__DIR__ . '/build')): ?>
        <?php $assetCount = iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/build', RecursiveDirectoryIterator::SKIP_DOTS))); ?>
        <ul>
            <li>Build Directory: ✅ Present</li>
            <li>Total Assets: <?php echo $assetCount; ?> files</li>
            <li>Manifest: <?php echo file_exists(__DIR__ . '/build/manifest.json') ? '✅ Present' : '❌ Missing'; ?></li>
        </ul>
    <?php else: ?>
        <div style="color: red;">❌ Build directory missing! Please upload /public/build/ from your build.</div>
    <?php endif; ?>

    <?php if (file_exists($basePath . '/artisan')): ?>
    <h2>Deployment Actions</h2>
    <p>Click buttons to perform deployment tasks:</p>
    
    <a href="?action=generate_key"><button>🔑 Generate APP_KEY</button></a>
    <a href="?action=migrate"><button>🗃️ Run Migrations</button></a>
    <a href="?action=optimize"><button>🚀 Optimize Application</button></a>
    
    <br><br>
    <a href="?action=delete_self" onclick="return confirm('Delete this helper file?')">
        <button class="danger">🗑️ Delete This Helper</button>
    </a>
    <?php endif; ?>

    <?php if ($_GET['action'] === 'delete_self'): ?>
        <?php
        if (unlink(__FILE__)) {
            echo "<script>alert('Helper deleted!'); window.location.href = '/';</script>";
        } else {
            echo "<div style='color: red;'>❌ Could not delete. Remove manually.</div>";
        }
        ?>
    <?php endif; ?>

    <footer style="margin-top: 50px; text-align: center; color: #666;">
        Parish Management System Production Helper - <?php echo date('Y-m-d H:i:s'); ?>
    </footer>
</body>
</html>
