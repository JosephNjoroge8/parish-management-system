<?php

echo '<h1>🔍 PHP Version Information</h1>';
echo '<h2>Current PHP Version: '.phpversion().'</h2>';
echo '<h3>Version Details:</h3>';
echo '<pre>';
phpinfo(INFO_GENERAL);
echo '</pre>';

echo '<h3>Available PHP Extensions:</h3>';
echo '<pre>';
print_r(get_loaded_extensions());
echo '</pre>';

echo '<h3>Laravel Requirements Check:</h3>';
$requirements = [
    'PHP >= 8.2.0' => version_compare(phpversion(), '8.2.0', '>='),
    'OpenSSL Extension' => extension_loaded('openssl'),
    'PDO Extension' => extension_loaded('pdo'),
    'Mbstring Extension' => extension_loaded('mbstring'),
    'Tokenizer Extension' => extension_loaded('tokenizer'),
    'XML Extension' => extension_loaded('xml'),
    'Ctype Extension' => extension_loaded('ctype'),
    'JSON Extension' => extension_loaded('json'),
    'BCMath Extension' => extension_loaded('bcmath'),
    'Fileinfo Extension' => extension_loaded('fileinfo'),
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='padding: 10px;'>Requirement</th><th style='padding: 10px;'>Status</th></tr>";
foreach ($requirements as $requirement => $met) {
    $status = $met ? '✅ Met' : '❌ Not Met';
    $color = $met ? 'green' : 'red';
    echo "<tr><td style='padding: 10px;'>$requirement</td><td style='padding: 10px; color: $color;'>$status</td></tr>";
}
echo '</table>';

echo '<h3>📋 Recommendations:</h3>';
if (version_compare(phpversion(), '8.2.0', '<')) {
    echo "<p style='color: red;'><strong>❌ PHP Version Too Old:</strong></p>";
    echo '<ul>';
    echo '<li>Current: '.phpversion().'</li>';
    echo '<li>Required: 8.2.0 or higher</li>';
    echo '<li><strong>Action:</strong> Update PHP in cPanel → Select PHP Version</li>';
    echo '</ul>';
} else {
    echo "<p style='color: green;'><strong>✅ PHP Version Compatible!</strong></p>";
}

echo '<p><strong>🗑️ Important:</strong> Delete this file after checking!</p>';
