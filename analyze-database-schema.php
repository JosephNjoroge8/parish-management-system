<?php

try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DATABASE SCHEMA ANALYSIS ===\n\n";
    
    // Get all tables
    $tables = $pdo->query('SELECT name FROM sqlite_master WHERE type="table" AND name NOT LIKE "sqlite_%" ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($tables as $table) {
        echo "=== Table: $table ===\n";
        
        // Get columns
        $columns = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns:\n";
        foreach($columns as $col) {
            $nullable = $col['notnull'] ? 'NOT NULL' : 'NULLABLE';
            $default = $col['dflt_value'] ? " DEFAULT {$col['dflt_value']}" : '';
            $pk = $col['pk'] ? ' PRIMARY KEY' : '';
            echo "  - {$col['name']} ({$col['type']}) $nullable$default$pk\n";
        }
        
        // Get indexes
        echo "\nIndexes:\n";
        $indexes = $pdo->query("PRAGMA index_list($table)")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($indexes)) {
            echo "  - No indexes found\n";
        } else {
            foreach($indexes as $idx) {
                $indexInfo = $pdo->query("PRAGMA index_info({$idx['name']})")->fetchAll(PDO::FETCH_ASSOC);
                $columns = array_column($indexInfo, 'name');
                $unique = $idx['unique'] ? ' (UNIQUE)' : '';
                echo "  - {$idx['name']}: " . implode(', ', $columns) . "$unique\n";
            }
        }
        
        echo "\n";
    }
    
    // Check for missing columns based on the optimization report
    echo "=== MISSING COLUMNS ANALYSIS ===\n\n";
    
    $expectedColumns = [
        'tithes' => ['member_id', 'date', 'family_id', 'amount', 'created_at', 'updated_at'],
        'community_groups' => ['name', 'is_active', 'created_at', 'updated_at'],
        'activities' => ['title', 'start_date', 'end_date', 'is_active', 'created_at', 'updated_at'],
        'families' => ['name', 'is_active', 'created_at', 'updated_at'],
        'donations' => ['amount', 'donation_date', 'donor_id', 'created_at', 'updated_at'],
        'events' => ['title', 'event_date', 'is_active', 'created_at', 'updated_at'],
        'sacraments' => ['name', 'date_received', 'member_id', 'created_at', 'updated_at'],
        'ministries' => ['name', 'is_active', 'created_at', 'updated_at'],
        'contributions' => ['amount', 'date', 'member_id', 'created_at', 'updated_at']
    ];
    
    foreach($expectedColumns as $tableName => $expectedCols) {
        // Check if table exists
        if (!in_array($tableName, $tables)) {
            echo "❌ Table '$tableName' does not exist\n";
            continue;
        }
        
        // Get actual columns
        $actualColumns = $pdo->query("PRAGMA table_info($tableName)")->fetchAll(PDO::FETCH_ASSOC);
        $actualColNames = array_column($actualColumns, 'name');
        
        echo "Table: $tableName\n";
        foreach($expectedCols as $expectedCol) {
            if (in_array($expectedCol, $actualColNames)) {
                echo "  ✅ $expectedCol (exists)\n";
            } else {
                echo "  ❌ $expectedCol (MISSING)\n";
            }
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure database/database.sqlite exists and is readable.\n";
}