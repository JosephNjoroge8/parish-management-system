<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $stmt = $db->query('SELECT name FROM sqlite_master WHERE type=\'table\' AND name LIKE \'%permission%\' OR name LIKE \'%role%\' ORDER BY name;');
    echo "Permission-related tables:\n";
    foreach ($stmt as $row) {
        echo '- '.$row['name']."\n";
    }
} catch (PDOException $e) {
    echo 'Error: '.$e->getMessage();
}
