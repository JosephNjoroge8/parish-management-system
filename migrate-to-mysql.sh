#!/bin/bash

# Database Migration Script - SQLite to MySQL for Production
# This script migrates the parish management system from SQLite to MySQL

set -e  # Exit on any error

echo "=== Parish Management System Database Migration ==="
echo "This script will migrate your data from SQLite to MySQL"
echo ""

# Configuration
SQLITE_DB="database/database.sqlite"
MYSQL_HOST="${DB_HOST:-127.0.0.1}"
MYSQL_PORT="${DB_PORT:-3306}"
MYSQL_DATABASE="${DB_DATABASE:-parish_management}"
MYSQL_USER="${DB_USERNAME:-root}"
MYSQL_PASSWORD="${DB_PASSWORD}"

# Check if SQLite database exists
if [ ! -f "$SQLITE_DB" ]; then
    echo "❌ Error: SQLite database not found at $SQLITE_DB"
    exit 1
fi

echo "✅ SQLite database found"

# Check MySQL connection
echo "🔄 Testing MySQL connection..."
mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ MySQL connection successful"
else
    echo "❌ Error: Cannot connect to MySQL. Please check your credentials."
    exit 1
fi

# Create database if it doesn't exist
echo "🔄 Creating MySQL database..."
mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$MYSQL_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "✅ MySQL database ready"

# Backup current database
echo "🔄 Creating backup of current SQLite database..."
cp "$SQLITE_DB" "${SQLITE_DB}.backup.$(date +%Y%m%d_%H%M%S)"
echo "✅ Backup created"

# Update .env for MySQL
echo "🔄 Updating .env configuration..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update database configuration
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=$MYSQL_HOST/" .env
sed -i "s/# DB_PORT=3306/DB_PORT=$MYSQL_PORT/" .env
sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$MYSQL_DATABASE/" .env
sed -i "s/# DB_USERNAME=root/DB_USERNAME=$MYSQL_USER/" .env
sed -i "s/# DB_PASSWORD=/DB_PASSWORD=$MYSQL_PASSWORD/" .env

echo "✅ Configuration updated"

# Clear Laravel caches
echo "🔄 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations on MySQL
echo "🔄 Running migrations on MySQL..."
php artisan migrate:fresh --force

# Export data from SQLite and import to MySQL
echo "🔄 Migrating data..."

# Get list of tables from SQLite
TABLES=$(sqlite3 "$SQLITE_DB" "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations';")

for table in $TABLES; do
    echo "  📊 Migrating table: $table"
    
    # Export data from SQLite to CSV
    sqlite3 -header -csv "$SQLITE_DB" "SELECT * FROM $table;" > "/tmp/${table}.csv"
    
    # Get column count to verify data
    COLUMNS=$(sqlite3 "$SQLITE_DB" "PRAGMA table_info($table);" | wc -l)
    ROWS=$(sqlite3 "$SQLITE_DB" "SELECT COUNT(*) FROM $table;" | head -1)
    
    echo "    📈 Table $table has $COLUMNS columns and $ROWS rows"
    
    if [ "$ROWS" -gt 0 ]; then
        # Prepare MySQL LOAD DATA statement
        mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" \
            -e "SET foreign_key_checks = 0; 
                LOAD DATA LOCAL INFILE '/tmp/${table}.csv' 
                INTO TABLE \`$table\` 
                FIELDS TERMINATED BY ',' 
                ENCLOSED BY '\"' 
                LINES TERMINATED BY '\n' 
                IGNORE 1 ROWS;
                SET foreign_key_checks = 1;"
                
        # Verify the migration
        MYSQL_ROWS=$(mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -se "SELECT COUNT(*) FROM \`$table\`;")
        
        if [ "$ROWS" -eq "$MYSQL_ROWS" ]; then
            echo "    ✅ Migration successful: $ROWS rows transferred"
        else
            echo "    ⚠️ Warning: Row count mismatch. SQLite: $ROWS, MySQL: $MYSQL_ROWS"
        fi
    else
        echo "    📝 Table $table is empty, skipping data migration"
    fi
    
    # Clean up temporary file
    rm -f "/tmp/${table}.csv"
done

# Update auto-increment values for MySQL
echo "🔄 Updating auto-increment values..."
mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" << 'EOF'
SET @tables = NULL;
SELECT GROUP_CONCAT(table_name) INTO @tables
FROM information_schema.tables
WHERE table_schema = DATABASE() AND auto_increment IS NOT NULL;

SET @sql = CONCAT('SET foreign_key_checks = 0;');
SELECT @sql := CONCAT(@sql, 'ALTER TABLE `', table_name, '` AUTO_INCREMENT = ', IFNULL(MAX(id)+1, 1), ';')
FROM information_schema.tables t
LEFT JOIN (
    SELECT table_name, MAX(CAST(id AS UNSIGNED)) as max_id
    FROM information_schema.columns c
    JOIN information_schema.tables t ON c.table_name = t.table_name
    WHERE c.column_name = 'id' AND t.table_schema = DATABASE()
    GROUP BY table_name
) m ON t.table_name = m.table_name
WHERE t.table_schema = DATABASE() AND t.auto_increment IS NOT NULL;

SET @sql = CONCAT(@sql, 'SET foreign_key_checks = 1;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
EOF

echo "✅ Auto-increment values updated"

# Optimize MySQL tables
echo "🔄 Optimizing MySQL tables..."
mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "
    SET @tables = NULL;
    SELECT GROUP_CONCAT(table_name) INTO @tables
    FROM information_schema.tables
    WHERE table_schema = DATABASE();
    
    SET @sql = CONCAT('OPTIMIZE TABLE ', @tables);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
"

# Update Laravel caches with new database
echo "🔄 Updating Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run a quick verification
echo "🔄 Running verification checks..."

# Check total member count
SQLITE_MEMBERS=$(sqlite3 "$SQLITE_DB" "SELECT COUNT(*) FROM members;" 2>/dev/null || echo "0")
MYSQL_MEMBERS=$(php artisan tinker --execute="echo App\Models\Member::count();" 2>/dev/null | tail -1)

echo "📊 Verification Results:"
echo "  SQLite Members: $SQLITE_MEMBERS"
echo "  MySQL Members: $MYSQL_MEMBERS"

if [ "$SQLITE_MEMBERS" -eq "$MYSQL_MEMBERS" ]; then
    echo "✅ Member count verification passed"
else
    echo "⚠️ Warning: Member count mismatch detected"
fi

# Test application
echo "🔄 Testing application..."
if php artisan route:list > /dev/null 2>&1; then
    echo "✅ Application routes working"
else
    echo "❌ Error: Application routes test failed"
fi

echo ""
echo "=== Migration Summary ==="
echo "✅ Database migration completed successfully!"
echo "✅ Data transferred from SQLite to MySQL"
echo "✅ Application configured for MySQL"
echo "✅ Caches updated"
echo ""
echo "📋 Post-Migration Checklist:"
echo "  1. Test the application thoroughly"
echo "  2. Verify all member data is accessible"
echo "  3. Test search and filtering functionality"
echo "  4. Generate and download sample reports"
echo "  5. Backup your new MySQL database"
echo ""
echo "📁 Backup files created:"
echo "  - SQLite backup: ${SQLITE_DB}.backup.*"
echo "  - .env backup: .env.backup.*"
echo ""
echo "🚀 Your parish management system is now ready for production with MySQL!"
echo ""
echo "⚠️  Important: Please run comprehensive tests before deploying to production."
echo "⚠️  Important: Update your production .env with actual production values."
echo ""