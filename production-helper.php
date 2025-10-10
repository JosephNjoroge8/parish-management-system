<?php
/**
 * PARISH MANAGEMENT SYSTEM - PRODUCTION DEPLOYMENT HELPER
 * 
 * Upload this file to your production server public/ directory
 * Visit: https://parish.quovadisyouthhub.org/production-helper.php
 * 
 * This script helps with deployment tasks when terminal access is limited
 * DELETE THIS FILE after successful deployment!
 */

// Security check - only run in production environment
if (php_sapi_name() === 'cli') {
    die("This script should only be run via web browser\n");
}

// Configuration
$basePath = dirname(__DIR__);
$publicPath = __DIR__;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parish Management System - Production Helper</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .success { color: #059669; background: #ecfdf5; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .error { color: #dc2626; background: #fef2f2; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .warning { color: #d97706; background: #fffbeb; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .info { color: #2563eb; background: #eff6ff; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .section { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0; }
        button { background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; margin: 5px; }
        button:hover { background: #2563eb; }
        .danger { background: #dc2626; }
        .danger:hover { background: #b91c1c; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
    </style>
</head>
<body>
    <h1>🚀 Parish Management System - Production Helper</h1>
    
    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong> Delete this file immediately after deployment completion!
    </div>

    <?php
    // Handle form actions
    $action = $_GET['action'] ?? '';
    $output = '';

    if ($action && file_exists($basePath . '/artisan')) {
        chdir($basePath);
        
        switch ($action) {
            case 'generate_key':
                if (!file_exists('.env')) {
                    $output = "<div class='error'>❌ .env file not found. Please create it first.</div>";
                } else {
                    exec('php artisan key:generate 2>&1', $result, $returnCode);
                    if ($returnCode === 0) {
                        $output = "<div class='success'>✅ Application key generated successfully!</div>";
                    } else {
                        $output = "<div class='error'>❌ Failed to generate key: " . implode('<br>', $result) . "</div>";
                    }
                }
                break;
                
            case 'migrate':
                exec('php artisan migrate --force 2>&1', $result, $returnCode);
                if ($returnCode === 0) {
                    $output = "<div class='success'>✅ Database migrations completed successfully!</div>";
                } else {
                    $output = "<div class='error'>❌ Migration failed: " . implode('<br>', $result) . "</div>";
                }
                break;
                
            case 'optimize':
                $commands = [
                    'config:clear' => 'Clear configuration cache',
                    'route:clear' => 'Clear route cache', 
                    'view:clear' => 'Clear view cache',
                    'cache:clear' => 'Clear application cache',
                    'config:cache' => 'Build configuration cache',
                    'route:cache' => 'Build route cache',
                    'view:cache' => 'Build view cache',
                    'optimize' => 'Optimize application'
                ];
                
                $output = "<div class='info'><h3>🔧 Running optimization commands...</h3>";
                foreach ($commands as $cmd => $desc) {
                    exec("php artisan $cmd 2>&1", $result, $returnCode);
                    if ($returnCode === 0) {
                        $output .= "<div class='success'>✅ $desc</div>";
                    } else {
                        $output .= "<div class='warning'>⚠️ $desc: " . implode(' ', $result) . "</div>";
                    }
                }
                $output .= "</div>";
                break;
                
            case 'storage_link':
                exec('php artisan storage:link 2>&1', $result, $returnCode);
                if ($returnCode === 0) {
                    $output = "<div class='success'>✅ Storage link created successfully!</div>";
                } else {
                    $output = "<div class='error'>❌ Storage link failed: " . implode('<br>', $result) . "</div>";
                }
                break;
        }
    }

    if ($output) echo $output;
    ?>

    <div class="grid">
        <!-- System Status -->
        <div class="card">
            <h2>📊 System Status</h2>
            
            <h3>PHP Configuration</h3>
            <ul>
                <li>PHP Version: <?php echo PHP_VERSION; ?></li>
                <li>Memory Limit: <?php echo ini_get('memory_limit'); ?></li>
                <li>Max Execution Time: <?php echo ini_get('max_execution_time'); ?>s</li>
                <li>Upload Max Size: <?php echo ini_get('upload_max_filesize'); ?></li>
            </ul>

            <h3>Laravel Status</h3>
            <?php if (file_exists($basePath . '/artisan')): ?>
                <div class="success">✅ Laravel detected</div>
                
                <?php if (file_exists($basePath . '/.env')): ?>
                    <div class="success">✅ .env file exists</div>
                <?php else: ?>
                    <div class="error">❌ .env file missing</div>
                <?php endif; ?>
                
                <?php if (is_writable($basePath . '/storage')): ?>
                    <div class="success">✅ Storage directory writable</div>
                <?php else: ?>
                    <div class="error">❌ Storage directory not writable</div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="error">❌ Laravel not detected</div>
            <?php endif; ?>
        </div>

        <!-- Asset Status -->
        <div class="card">
            <h2>📁 Asset Status</h2>
            
            <?php if (is_dir($publicPath . '/build')): ?>
                <div class="success">✅ Build directory exists</div>
                
                <?php
                $assetCount = iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicPath . '/build', RecursiveDirectoryIterator::SKIP_DOTS)));
                ?>
                <p><strong>Total assets:</strong> <?php echo $assetCount; ?> files</p>
                
                <?php if (file_exists($publicPath . '/build/manifest.json')): ?>
                    <div class="success">✅ Manifest file exists</div>
                    <?php
                    $manifestSize = filesize($publicPath . '/build/manifest.json');
                    echo "<p><strong>Manifest size:</strong> " . number_format($manifestSize) . " bytes</p>";
                    ?>
                <?php else: ?>
                    <div class="error">❌ Manifest file missing</div>
                <?php endif; ?>
                
                <?php
                $cssFiles = glob($publicPath . '/build/assets/app-*.css');
                $jsFiles = glob($publicPath . '/build/assets/app-*.js');
                ?>
                
                <?php if (!empty($cssFiles)): ?>
                    <div class="success">✅ CSS assets found (<?php echo count($cssFiles); ?>)</div>
                <?php else: ?>
                    <div class="error">❌ CSS assets missing</div>
                <?php endif; ?>
                
                <?php if (!empty($jsFiles)): ?>
                    <div class="success">✅ JavaScript assets found (<?php echo count($jsFiles); ?>)</div>
                <?php else: ?>
                    <div class="error">❌ JavaScript assets missing</div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="error">❌ Build directory missing</div>
                <p>Please upload the /public/build/ directory from your local build</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Deployment Actions -->
    <?php if (file_exists($basePath . '/artisan')): ?>
    <div class="section">
        <h2>🔧 Deployment Actions</h2>
        <p>Click the buttons below to perform common deployment tasks:</p>
        
        <a href="?action=generate_key"><button>🔑 Generate APP_KEY</button></a>
        <a href="?action=migrate"><button>🗃️ Run Migrations</button></a>
        <a href="?action=optimize"><button>🚀 Optimize Application</button></a>
        <a href="?action=storage_link"><button>🔗 Create Storage Link</button></a>
        
        <div class="warning">
            <strong>Note:</strong> These actions will be performed on the production server. Make sure you have proper backups before proceeding.
        </div>
    </div>
    <?php endif; ?>

    <!-- Environment Check -->
    <div class="section">
        <h2>⚙️ Environment Configuration</h2>
        
        <?php if (file_exists($basePath . '/.env')): ?>
            <?php
            $envContent = file_get_contents($basePath . '/.env');
            $envLines = explode("\n", $envContent);
            $envConfig = [];
            
            foreach ($envLines as $line) {
                if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                    [$key, $value] = explode('=', $line, 2);
                    $envConfig[trim($key)] = trim($value);
                }
            }
            ?>
            
            <h3>Critical Settings</h3>
            <ul>
                <li>APP_ENV: <strong><?php echo $envConfig['APP_ENV'] ?? 'not set'; ?></strong>
                    <?php if (($envConfig['APP_ENV'] ?? '') === 'production'): ?>
                        <span style="color: #059669;">✅</span>
                    <?php else: ?>
                        <span style="color: #dc2626;">❌</span>
                    <?php endif; ?>
                </li>
                
                <li>APP_DEBUG: <strong><?php echo $envConfig['APP_DEBUG'] ?? 'not set'; ?></strong>
                    <?php if (($envConfig['APP_DEBUG'] ?? '') === 'false'): ?>
                        <span style="color: #059669;">✅</span>
                    <?php else: ?>
                        <span style="color: #dc2626;">❌</span>
                    <?php endif; ?>
                </li>
                
                <li>APP_KEY: 
                    <?php if (!empty($envConfig['APP_KEY'] ?? '')): ?>
                        <span style="color: #059669;">✅ Set</span>
                    <?php else: ?>
                        <span style="color: #dc2626;">❌ Not set</span>
                    <?php endif; ?>
                </li>
                
                <li>DB_CONNECTION: <strong><?php echo $envConfig['DB_CONNECTION'] ?? 'not set'; ?></strong></li>
                <li>DB_DATABASE: <strong><?php echo $envConfig['DB_DATABASE'] ?? 'not set'; ?></strong></li>
            </ul>
            
        <?php else: ?>
            <div class="error">❌ .env file not found. Please create it based on .env.example</div>
        <?php endif; ?>
    </div>

    <!-- Quick Tests -->
    <div class="section">
        <h2>🧪 Quick Tests</h2>
        
        <h3>Database Connection</h3>
        <?php
        if (file_exists($basePath . '/.env')) {
            try {
                // Load Laravel to test database
                require_once $basePath . '/vendor/autoload.php';
                $app = require_once $basePath . '/bootstrap/app.php';
                
                $db = $app->make('db');
                $connection = $db->connection();
                $connection->getPdo();
                
                echo "<div class='success'>✅ Database connection successful</div>";
                
                // Check if migrations table exists
                $tables = $connection->getDoctrineSchemaManager()->listTableNames();
                if (in_array('migrations', $tables)) {
                    echo "<div class='success'>✅ Migrations table exists</div>";
                } else {
                    echo "<div class='warning'>⚠️ Migrations table not found - run migrations</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Cannot test database - .env file missing</div>";
        }
        ?>
    </div>

    <!-- Cleanup Warning -->
    <div class="section">
        <h2>🧹 Security Cleanup</h2>
        <div class="error">
            <strong>🚨 IMPORTANT:</strong> After successful deployment, delete the following files for security:
            <ul>
                <li>production-helper.php (this file)</li>
                <li>debug-assets.php (if uploaded)</li>
                <li>Any other temporary helper files</li>
            </ul>
        </div>
        
        <a href="?action=delete_self" onclick="return confirm('Are you sure you want to delete this helper file?')">
            <button class="danger">🗑️ Delete This Helper File</button>
        </a>
        
        <?php if ($action === 'delete_self'): ?>
            <?php
            // Self-destruct
            if (unlink(__FILE__)) {
                echo "<script>alert('Helper file deleted successfully!'); window.location.href = '/';</script>";
            } else {
                echo "<div class='error'>❌ Could not delete helper file. Please remove manually.</div>";
            }
            ?>
        <?php endif; ?>
    </div>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; border-top: 1px solid #e5e7eb; color: #6b7280;">
        Parish Management System - Production Deployment Helper<br>
        <small>Generated on <?php echo date('Y-m-d H:i:s'); ?></small>
    </footer>
</body>
</html>