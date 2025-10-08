<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>Asset Debug</title></head>
<body>
<h1>🔍 Asset Debugging</h1>

<h2>Manifest File Status</h2>
<?php if (file_exists('build/manifest.json')): ?>
    <p style="color: green;">✅ Manifest exists</p>
    <p><strong>Size:</strong> <?php echo number_format(filesize('build/manifest.json')); ?> bytes</p>
    <p><strong>Modified:</strong> <?php echo date('Y-m-d H:i:s', filemtime('build/manifest.json')); ?></p>
<?php else: ?>
    <p style="color: red;">❌ Manifest missing</p>
<?php endif; ?>

<h2>Asset Files</h2>
<?php
if (is_dir('build/assets')) {
    $files = glob('build/assets/*');
    echo "<p><strong>Total files:</strong> " . count($files) . "</p>";
    echo "<ul>";
    foreach (array_slice($files, 0, 20) as $file) {
        $size = number_format(filesize($file));
        echo "<li>" . basename($file) . " ({$size} bytes)</li>";
    }
    if (count($files) > 20) {
        echo "<li>... and " . (count($files) - 20) . " more files</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Assets directory missing</p>";
}
?>

<h2>Expected Files</h2>
<?php
$expected = [
    'build/assets/app-DFNb4TD2.js',
    'build/assets/vendor-BJRZWs4n.js', 
    'build/assets/inertia-3kzqnxf1.js',
    'build/assets/app-BGfyocw-.css'
];

foreach ($expected as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file</p>";
    } else {
        echo "<p style='color: red;'>❌ $file</p>";
    }
}
?>
</body>
</html>
