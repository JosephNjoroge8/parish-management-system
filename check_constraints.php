<?php

// Check constraints on members table
try {
    $pdo = new PDO('sqlite:'.__DIR__.'/database/database.sqlite');

    echo "=== TABLE CONSTRAINTS ===\n";
    $sql = "SELECT sql FROM sqlite_master WHERE type='table' AND name='members'";
    $result = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo $result['sql']."\n";
    } else {
        echo "No table found\n";
    }

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
