<?php
// EMERGENCY PHP TEST - Check if PHP is working
echo "✅ SUCCESS: PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "<br>";
echo "<br><strong>If you see this message, PHP processing is working!</strong>";
?>