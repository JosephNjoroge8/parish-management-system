<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parish System - Asset Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f3f4f6;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 4px solid #3b82f6;
        }
        .section h2 {
            color: #374151;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .check-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .status.success { background: #10b981; }
        .status.error { background: #ef4444; }
        .status.warning { background: #f59e0b; }
        .status.pending { background: #6b7280; }
        .info {
            flex: 1;
        }
        .label {
            font-weight: 600;
            color: #374151;
        }
        .value {
            color: #6b7280;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }
        .error-detail {
            color: #dc2626;
            font-size: 14px;
            margin-top: 5px;
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin: 5px;
            transition: background 0.2s;
        }
        .button:hover {
            background: #2563eb;
        }
        .actions {
            margin-top: 20px;
        }
        .code-block {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Parish System - Asset Diagnostic</h1>
        <p class="subtitle">Comprehensive system check for production deployment</p>

        <?php
        // Configuration
        $buildDir = __DIR__.'/build';
        $manifestFile = $buildDir.'/manifest.json';
        $checks = [];
        $hasErrors = false;

        // Check 1: Build directory exists
        $checks[] = [
            'label' => 'Build Directory',
            'status' => is_dir($buildDir) ? 'success' : 'error',
            'value' => is_dir($buildDir) ? 'Exists at: '.$buildDir : 'NOT FOUND',
            'error' => ! is_dir($buildDir) ? 'Run: npm run build' : null,
        ];

        // Check 2: Manifest file exists
        $manifestExists = file_exists($manifestFile);
        $checks[] = [
            'label' => 'Manifest File',
            'status' => $manifestExists ? 'success' : 'error',
            'value' => $manifestExists ? 'Found' : 'NOT FOUND',
            'error' => ! $manifestExists ? 'Run: npm run build to generate manifest.json' : null,
        ];

        // Check 3: Manifest is valid JSON
        $manifestValid = false;
        $manifestData = null;
        if ($manifestExists) {
            $manifestContent = file_get_contents($manifestFile);
            $manifestData = json_decode($manifestContent, true);
            $manifestValid = json_last_error() === JSON_ERROR_NONE;

            $checks[] = [
                'label' => 'Manifest Valid JSON',
                'status' => $manifestValid ? 'success' : 'error',
                'value' => $manifestValid ? 'Valid ('.count($manifestData).' entries)' : 'INVALID',
                'error' => ! $manifestValid ? 'Manifest is corrupted. Run: npm run build' : null,
            ];
        }

        // Check 4: Main entry points
        if ($manifestValid && $manifestData) {
            $mainJs = $manifestData['resources/js/app.tsx']['file'] ?? null;
            $mainCss = $manifestData['resources/css/app.css']['file'] ?? null;

            if ($mainJs) {
                $mainJsPath = $buildDir.'/'.$mainJs;
                $jsExists = file_exists($mainJsPath);
                $checks[] = [
                    'label' => 'Main JavaScript',
                    'status' => $jsExists ? 'success' : 'error',
                    'value' => $jsExists ? $mainJs.' ('.filesize($mainJsPath).' bytes)' : 'MISSING: '.$mainJs,
                    'error' => ! $jsExists ? 'File referenced in manifest but not found on disk' : null,
                ];
                if (! $jsExists) {
                    $hasErrors = true;
                }
            }

            if ($mainCss) {
                $mainCssPath = $buildDir.'/'.$mainCss;
                $cssExists = file_exists($mainCssPath);
                $checks[] = [
                    'label' => 'Main CSS',
                    'status' => $cssExists ? 'success' : 'error',
                    'value' => $cssExists ? $mainCss.' ('.filesize($mainCssPath).' bytes)' : 'MISSING: '.$mainCss,
                    'error' => ! $cssExists ? 'File referenced in manifest but not found on disk' : null,
                ];
                if (! $cssExists) {
                    $hasErrors = true;
                }
            }
        }

        // Check 5: Assets directory
        $assetsDir = $buildDir.'/assets';
        $assetsDirExists = is_dir($assetsDir);
        $assetCount = 0;
        if ($assetsDirExists) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetsDir));
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $assetCount++;
                }
            }
        }

        $checks[] = [
            'label' => 'Assets Directory',
            'status' => $assetsDirExists && $assetCount > 0 ? 'success' : 'error',
            'value' => $assetsDirExists ? "Contains $assetCount files" : 'NOT FOUND',
            'error' => ! $assetsDirExists ? 'Assets directory missing' : null,
        ];

        // Check 6: File permissions
        $manifestReadable = is_readable($manifestFile);
        $checks[] = [
            'label' => 'File Permissions',
            'status' => $manifestReadable ? 'success' : 'error',
            'value' => $manifestReadable ? 'Readable' : 'NOT READABLE',
            'error' => ! $manifestReadable ? 'Fix permissions: chmod -R 755 public/build' : null,
        ];

        // Check 7: .htaccess exists
        $htaccessFile = __DIR__.'/.htaccess';
        $htaccessExists = file_exists($htaccessFile);
        $checks[] = [
            'label' => '.htaccess File',
            'status' => $htaccessExists ? 'success' : 'error',
            'value' => $htaccessExists ? 'Exists' : 'MISSING',
            'error' => ! $htaccessExists ? 'Copy from repository or regenerate' : null,
        ];

        // Check 8: PHP Version
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.2', '>=');
        $checks[] = [
            'label' => 'PHP Version',
            'status' => $phpOk ? 'success' : 'warning',
            'value' => $phpVersion,
            'error' => ! $phpOk ? 'PHP 8.2+ recommended' : null,
        ];

        // Display checks
        ?>

        <div class="section">
            <h2>System Checks</h2>
            <?php foreach ($checks as $check) { ?>
                <div class="check-item">
                    <div class="status <?php echo $check['status']; ?>"></div>
                    <div class="info">
                        <div class="label"><?php echo $check['label']; ?></div>
                        <div class="value"><?php echo $check['value']; ?></div>
                        <?php if ($check['error']) { ?>
                            <div class="error-detail">⚠️ <?php echo $check['error']; ?></div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php if ($manifestValid && $manifestData) { ?>
        <div class="section">
            <h2>Build Information</h2>
            <div class="check-item">
                <div class="info">
                    <div class="label">Total Assets</div>
                    <div class="value"><?php echo count($manifestData); ?> entries in manifest</div>
                </div>
            </div>
            <div class="check-item">
                <div class="info">
                    <div class="label">Build Directory Size</div>
                    <div class="value">
                        <?php
                        $totalSize = 0;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($buildDir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                }
            }
            echo number_format($totalSize / 1024 / 1024, 2).' MB';
            ?>
                    </div>
                </div>
            </div>
            <div class="check-item">
                <div class="info">
                    <div class="label">Last Build Time</div>
                    <div class="value">
                        <?php echo date('Y-m-d H:i:s', filemtime($manifestFile)); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        <div class="section">
            <h2>Environment Information</h2>
            <div class="check-item">
                <div class="info">
                    <div class="label">Server Software</div>
                    <div class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                </div>
            </div>
            <div class="check-item">
                <div class="info">
                    <div class="label">Document Root</div>
                    <div class="value"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></div>
                </div>
            </div>
            <div class="check-item">
                <div class="info">
                    <div class="label">Current URL</div>
                    <div class="value"><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']; ?></div>
                </div>
            </div>
        </div>

        <?php if ($hasErrors || ! $manifestExists) { ?>
        <div class="section" style="border-left-color: #ef4444;">
            <h2>🚨 Action Required</h2>
            <p style="margin-bottom: 15px;">Your assets are not properly built. Follow these steps:</p>
            
            <div class="code-block">
                <div># 1. Install dependencies (if needed)</div>
                <div>npm install</div>
                <div style="margin-top: 10px;"># 2. Build assets for production</div>
                <div>npm run build</div>
                <div style="margin-top: 10px;"># 3. Verify the build</div>
                <div>bash verify-vite-assets.sh</div>
                <div style="margin-top: 10px;"># 4. Fix permissions (if needed)</div>
                <div>chmod -R 755 public/build</div>
            </div>
        </div>
        <?php } else { ?>
        <div class="section" style="border-left-color: #10b981;">
            <h2>✅ All Checks Passed!</h2>
            <p>Your assets are properly built and should be working. If you're still experiencing issues, check:</p>
            <ul style="margin: 15px 0 15px 25px;">
                <li>Browser console for specific error messages</li>
                <li>Network tab to see which assets fail to load</li>
                <li>Laravel logs in storage/logs/</li>
            </ul>
        </div>
        <?php } ?>

        <div class="actions">
            <a href="/" class="button">← Back to Application</a>
            <a href="?refresh=1" class="button">🔄 Refresh Check</a>
        </div>
    </div>
</body>
</html>
