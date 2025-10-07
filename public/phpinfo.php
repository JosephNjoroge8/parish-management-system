<?php

// Temporary PHP info file for hosting diagnostics
// DELETE THIS FILE after checking your hosting configuration

echo '<h1>PHP Configuration Check</h1>';
echo '<h2>PHP Version: '.PHP_VERSION.'</h2>';

echo '<h3>Required Extensions Status:</h3>';
$required_extensions = [
    'pdo', 'pdo_mysql', 'mbstring', 'tokenizer', 'xml',
    'ctype', 'json', 'bcmath', 'openssl', 'fileinfo',
    'gd', 'zip', 'dom',
];

echo "<table border='1' style='border-collapse: collapse;'>";
echo '<tr><th>Extension</th><th>Status</th></tr>';

foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅ Loaded' : '❌ Missing';
    $color = extension_loaded($ext) ? 'green' : 'red';
    echo "<tr><td>$ext</td><td style='color: $color;'>$status</td></tr>";
}

echo '</table>';

echo '<h3>All Loaded Extensions:</h3>';
echo '<p>'.implode(', ', get_loaded_extensions()).'</p>';

echo '<hr>';
echo '<p><strong>Upload this info to your hosting provider if extensions are missing.</strong></p>';
echo '<p><strong>⚠️ DELETE THIS FILE after checking!</strong></p>';

// Uncomment the line below to see full PHP info
// phpinfo();
