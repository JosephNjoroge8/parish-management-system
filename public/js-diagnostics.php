<?php
// Quick diagnostic script for JavaScript MIME type issues
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>JavaScript MIME Type Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffeaea; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e8f0ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 JavaScript MIME Type Diagnostics</h1>
    
    <div class="success">
        <h2>✅ PHP Status: Working!</h2>
        <p>This page confirms PHP is executing properly.</p>
    </div>
    
    <div class="test-section">
        <h3>📋 Server Information</h3>
        <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
    </div>
    
    <div class="test-section">
        <h3>📁 Asset File Check</h3>
        <?php
        $assetFiles = [
            'build/manifest.json',
            'build/assets/app-DQJFvidy.js',
            'build/assets/app-Dhstvbpm.js', 
            'build/assets/vendor-DtTjrbJw.js',
            'build/assets/inertia-PIF0PBcz.js',
        ];
        
        foreach ($assetFiles as $file) {
            $exists = file_exists($file);
            $class = $exists ? 'success' : 'error';
            $status = $exists ? '✅ Found' : '❌ Missing';
            $size = $exists ? ' (' . number_format(filesize($file)) . ' bytes)' : '';
            echo "<p class=\"{$class}\">{$status} {$file}{$size}</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h3>🔧 .htaccess Check</h3>
        <?php if (file_exists('.htaccess')): ?>
            <p class="success">✅ .htaccess file exists</p>
            <p><strong>Size:</strong> <?php echo number_format(filesize('.htaccess')); ?> bytes</p>
            <p><strong>Last modified:</strong> <?php echo date('Y-m-d H:i:s', filemtime('.htaccess')); ?></p>
        <?php else: ?>
            <p class="error">❌ .htaccess file missing</p>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h3>🚨 Issue Analysis</h3>
        <div class="info">
            <p><strong>Problem:</strong> JavaScript files showing as "text/html" instead of "application/javascript"</p>
            <p><strong>Cause:</strong> Web server MIME type configuration</p>
            <p><strong>Solution:</strong> Update .htaccess with proper MIME type directives</p>
        </div>
    </div>
    
    <div class="test-section">
        <h3>🔧 Manual Fix Commands</h3>
        <pre>
# If issues persist, run these commands on production:

# 1. Replace .htaccess with simple version:
cp .htaccess.simple .htaccess

# 2. Clear browser cache and test:
# - Hard refresh (Ctrl+F5 or Cmd+Shift+R)
# - Clear browser cache completely

# 3. Check if files are being served correctly:
curl -I https://parish.quovadisyouthhub.org/build/assets/app-DQJFvidy.js

# 4. If still broken, contact hosting provider about:
# - MIME type configuration for .js files
# - mod_mime Apache module
# - .htaccess processing
        </pre>
    </div>
    
    <div class="test-section">
        <h3>📞 Next Steps</h3>
        <ol>
            <li>Upload the updated .htaccess file to production</li>
            <li>Clear browser cache completely</li>
            <li>Test the site again</li>
            <li>If still broken, use .htaccess.simple as backup</li>
        </ol>
    </div>
</body>
</html>