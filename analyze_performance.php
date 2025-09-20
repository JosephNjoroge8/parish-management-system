<?php

require 'vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🔍 PARISH SYSTEM PERFORMANCE ANALYSIS\n";
echo "=====================================\n\n";

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? 'parish_system';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✅ Database connection successful\n\n";
    
    // 1. ANALYZE TABLE SIZES AND RECORD COUNTS
    echo "📊 TABLE ANALYSIS\n";
    echo "-----------------\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $tableSizes = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $tableSizes[$table] = $count;
        
        $prefix = $count > 1000 ? "⚠️ " : ($count > 100 ? "📈" : "✅");
        echo "$prefix $table: " . number_format($count) . " records\n";
    }
    
    // 2. CHECK FOR MISSING INDEXES
    echo "\n🔍 INDEX ANALYSIS\n";
    echo "-----------------\n";
    
    $indexIssues = [];
    
    // Check key tables for proper indexing
    $criticalTables = [
        'users' => ['email', 'is_active', 'created_by'],
        'members' => ['family_id', 'is_active', 'phone', 'email'],
        'families' => ['is_active'],
        'activities' => ['start_date', 'end_date', 'is_active'],
        'activity_participants' => ['activity_id', 'member_id'],
        'community_groups' => ['is_active'],
        'group_members' => ['group_id', 'member_id'],
        'tithes' => ['member_id', 'date', 'family_id'],
        'baptism_records' => ['member_id', 'baptism_date'],
        'marriage_records' => ['husband_id', 'wife_id', 'marriage_date'],
        'model_has_roles' => ['model_id', 'model_type', 'role_id'],
        'model_has_permissions' => ['model_id', 'model_type', 'permission_id']
    ];
    
    foreach ($criticalTables as $table => $columns) {
        if (in_array($table, $tables)) {
            // Get existing indexes
            $stmt = $pdo->query("SHOW INDEX FROM `$table`");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $indexedColumns = array_column($indexes, 'Column_name');
            
            echo "\n📋 $table indexes:\n";
            foreach ($columns as $column) {
                // Check if column exists first
                $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                $columnExists = $stmt->fetch() !== false;
                
                if ($columnExists) {
                    if (in_array($column, $indexedColumns)) {
                        echo "  ✅ $column (indexed)\n";
                    } else {
                        echo "  ❌ $column (NOT indexed) - PERFORMANCE ISSUE\n";
                        $indexIssues[] = "ALTER TABLE `$table` ADD INDEX idx_{$table}_{$column} (`$column`);";
                    }
                } else {
                    echo "  ⚠️  $column (column doesn't exist)\n";
                }
            }
        } else {
            echo "⚠️  Table $table not found\n";
        }
    }
    
    // 3. ANALYZE SLOW QUERIES POTENTIAL
    echo "\n⚡ POTENTIAL SLOW QUERY ISSUES\n";
    echo "------------------------------\n";
    
    // Check for tables without primary keys
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = 'PRIMARY'");
        $hasPrimary = $stmt->fetch() !== false;
        
        if (!$hasPrimary) {
            echo "❌ $table: No primary key found\n";
        }
    }
    
    // 4. STORAGE ENGINE ANALYSIS
    echo "\n🗄️  STORAGE ENGINE ANALYSIS\n";
    echo "---------------------------\n";
    
    $stmt = $pdo->query("
        SELECT TABLE_NAME, ENGINE, TABLE_ROWS, 
               ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'SIZE_MB'
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = '$dbname'
        ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
    ");
    
    $tableStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tableStats as $stat) {
        $engine = $stat['ENGINE'] ?: 'Unknown';
        $size = $stat['SIZE_MB'] ?: '0.00';
        $rows = number_format($stat['TABLE_ROWS'] ?: 0);
        
        $prefix = $engine !== 'InnoDB' ? "⚠️ " : "✅";
        echo "$prefix {$stat['TABLE_NAME']}: $engine, $rows rows, {$size}MB\n";
    }
    
    // 5. GENERATE OPTIMIZATION RECOMMENDATIONS
    echo "\n🚀 OPTIMIZATION RECOMMENDATIONS\n";
    echo "===============================\n";
    
    if (!empty($indexIssues)) {
        echo "\n📝 Missing Indexes (Copy and run these SQL commands):\n";
        foreach ($indexIssues as $sql) {
            echo "$sql\n";
        }
    }
    
    // Check for large tables
    $largeTables = array_filter($tableSizes, function($count) { return $count > 1000; });
    if (!empty($largeTables)) {
        echo "\n📈 Large Tables Optimization:\n";
        foreach ($largeTables as $table => $count) {
            echo "- $table (" . number_format($count) . " records): Consider partitioning or archiving\n";
        }
    }
    
    echo "\n✨ General Recommendations:\n";
    echo "- Enable query caching in MySQL\n";
    echo "- Use Redis for session storage\n";
    echo "- Implement database connection pooling\n";
    echo "- Add composite indexes for multi-column queries\n";
    echo "- Consider read replicas for reporting queries\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}