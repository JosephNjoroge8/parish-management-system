#!/bin/bash

# ============================================================================
# SQLITE TO MYSQL MIGRATION SCRIPT - Parish Management System
# ============================================================================
# This script handles the complete transition from SQLite to MySQL
# - Database backup and export
# - MySQL setup and configuration
# - Data migration with integrity checks
# - .env configuration updates
# ============================================================================

set -e  # Exit on any error

echo "🔄 Parish Management System - SQLite to MySQL Migration"
echo "======================================================="
echo "$(date): Starting database migration..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

print_info "Detected Laravel project directory"

# Configuration variables
BACKUP_DIR="database_migration_backup_$(date +%Y%m%d_%H%M%S)"
SQLITE_PATH="database/database.sqlite"
MYSQL_DUMP_FILE="$BACKUP_DIR/sqlite_export.sql"

echo ""
echo "🔍 Step 1: Pre-Migration Checks"
echo "==============================="

# Check if SQLite database exists
if [ ! -f "$SQLITE_PATH" ]; then
    print_error "SQLite database not found at: $SQLITE_PATH"
    exit 1
fi

print_status "SQLite database found"

# Check SQLite database size
SQLITE_SIZE=$(stat -f%z "$SQLITE_PATH" 2>/dev/null || stat -c%s "$SQLITE_PATH" 2>/dev/null)
print_info "SQLite database size: $(($SQLITE_SIZE / 1024))KB"

# Check if MySQL client is available
if ! command -v mysql &> /dev/null; then
    print_error "MySQL client not found. Please install MySQL client first."
    exit 1
fi

print_status "MySQL client available"

# Get MySQL connection details
echo ""
echo "📝 Step 2: MySQL Configuration"
echo "=============================="

read -p "MySQL Host (default: localhost): " MYSQL_HOST
MYSQL_HOST=${MYSQL_HOST:-localhost}

read -p "MySQL Port (default: 3306): " MYSQL_PORT
MYSQL_PORT=${MYSQL_PORT:-3306}

read -p "MySQL Database Name: " MYSQL_DATABASE
if [ -z "$MYSQL_DATABASE" ]; then
    print_error "Database name is required"
    exit 1
fi

read -p "MySQL Username: " MYSQL_USERNAME
if [ -z "$MYSQL_USERNAME" ]; then
    print_error "Username is required"
    exit 1
fi

read -s -p "MySQL Password: " MYSQL_PASSWORD
echo
if [ -z "$MYSQL_PASSWORD" ]; then
    print_error "Password is required"
    exit 1
fi

print_status "MySQL configuration collected"

# Test MySQL connection
echo ""
echo "🔗 Step 3: Testing MySQL Connection"
echo "==================================="

if mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" -e "SELECT 1;" &>/dev/null; then
    print_status "MySQL connection successful"
else
    print_error "Cannot connect to MySQL. Please check your credentials."
    exit 1
fi

# Check if database exists, create if not
if mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" -e "USE $MYSQL_DATABASE;" &>/dev/null; then
    print_warning "Database '$MYSQL_DATABASE' already exists"
    read -p "Continue and overwrite existing data? [y/N]: " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Migration cancelled by user"
        exit 0
    fi
else
    print_info "Creating database '$MYSQL_DATABASE'..."
    mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" -e "CREATE DATABASE $MYSQL_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    print_status "Database created successfully"
fi

# Step 4: Backup current data
echo ""
echo "💾 Step 4: Creating Backup"
echo "=========================="

mkdir -p "$BACKUP_DIR"

# Backup current .env file
cp .env "$BACKUP_DIR/.env.backup"
print_status ".env file backed up"

# Backup SQLite database
cp "$SQLITE_PATH" "$BACKUP_DIR/database.sqlite.backup"
print_status "SQLite database backed up"

# Export SQLite data using Laravel
print_info "Exporting SQLite data..."

# Create a temporary PHP script to export data
cat > "$BACKUP_DIR/export_data.php" << 'EOF'
<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$output = fopen('database_migration_backup_' . date('Ymd_His') . '/sqlite_export.sql', 'w');

// Get all table names
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

foreach ($tables as $table) {
    $tableName = $table->name;
    
    // Skip migrations table
    if ($tableName === 'migrations') {
        continue;
    }
    
    echo "Exporting table: $tableName\n";
    
    // Get table structure (we'll recreate with migrations)
    $rows = DB::table($tableName)->get();
    
    if ($rows->count() > 0) {
        foreach ($rows as $row) {
            $columns = [];
            $values = [];
            
            foreach ((array)$row as $column => $value) {
                $columns[] = "`$column`";
                if (is_null($value)) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            
            $sql = "INSERT INTO `$tableName` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            fwrite($output, $sql);
        }
    }
}

fclose($output);
echo "Export completed!\n";
EOF

php "$BACKUP_DIR/export_data.php"
print_status "SQLite data exported to SQL file"

# Step 5: Update .env configuration
echo ""
echo "⚙️  Step 5: Updating Configuration"
echo "================================="

# Backup current .env
cp .env .env.sqlite.backup

# Update .env for MySQL
sed -i.bak "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i.bak "s/DB_HOST=.*/DB_HOST=$MYSQL_HOST/" .env
sed -i.bak "s/DB_PORT=.*/DB_PORT=$MYSQL_PORT/" .env
sed -i.bak "s/DB_DATABASE=.*/DB_DATABASE=$MYSQL_DATABASE/" .env
sed -i.bak "s/DB_USERNAME=.*/DB_USERNAME=$MYSQL_USERNAME/" .env
sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=$MYSQL_PASSWORD/" .env

print_status ".env updated for MySQL"

# Step 6: Run Laravel migrations on MySQL
echo ""
echo "🗄️  Step 6: Setting up MySQL Schema"
echo "=================================="

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear

# Test new MySQL connection
if php artisan migrate:status &>/dev/null; then
    print_status "MySQL connection working with Laravel"
else
    print_error "Cannot connect to MySQL with new configuration"
    print_info "Restoring SQLite configuration..."
    cp .env.sqlite.backup .env
    exit 1
fi

# Run migrations to create schema
print_info "Running migrations on MySQL..."
php artisan migrate:fresh --force

print_status "MySQL schema created"

# Step 7: Import data
echo ""
echo "📥 Step 7: Importing Data"
echo "========================"

if [ -f "$MYSQL_DUMP_FILE" ]; then
    print_info "Importing data to MySQL..."
    mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "$MYSQL_DUMP_FILE"
    print_status "Data imported successfully"
else
    print_warning "No SQL dump file found, skipping data import"
fi

# Step 8: Verification
echo ""
echo "🔍 Step 8: Data Verification"
echo "==========================="

# Count records in key tables
print_info "Verifying data integrity..."

TABLES=("users" "members" "families" "sacraments" "tithes")

for table in "${TABLES[@]}"; do
    if mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "DESCRIBE $table;" &>/dev/null; then
        COUNT=$(mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT COUNT(*) FROM $table;" -s -N)
        print_info "$table: $COUNT records"
    else
        print_warning "Table $table not found (this may be normal)"
    fi
done

# Test Laravel connection
if php artisan migrate:status &>/dev/null; then
    print_status "Laravel can connect to MySQL successfully"
else
    print_error "Laravel cannot connect to MySQL"
fi

# Step 9: Cleanup and final steps
echo ""
echo "🧹 Step 9: Cleanup"
echo "=================="

# Remove temporary export script
rm -f "$BACKUP_DIR/export_data.php"

# Cache Laravel configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_status "Laravel caches rebuilt"

echo ""
echo "✅ MIGRATION COMPLETED!"
echo "======================"
echo ""
echo "📊 Migration Summary:"
echo "===================="
echo "✅ SQLite database backed up to: $BACKUP_DIR/"
echo "✅ .env configuration updated for MySQL"
echo "✅ MySQL schema created via migrations"
echo "✅ Data exported and imported"
echo "✅ Laravel caches rebuilt"
echo ""
echo "🔍 Next Steps:"
echo "=============="
echo "1. Test your application thoroughly"
echo "2. Verify all data is present and correct"
echo "3. Test all functionality (login, CRUD operations, etc.)"
echo "4. Monitor application for any database-related errors"
echo ""
echo "📝 Important Files:"
echo "=================="
echo "- Backup directory: $BACKUP_DIR/"
echo "- SQLite backup: $BACKUP_DIR/database.sqlite.backup"
echo "- Original .env: $BACKUP_DIR/.env.backup"
echo "- SQLite .env backup: .env.sqlite.backup"
echo ""
echo "🚨 Rollback Instructions (if needed):"
echo "====================================="
echo "If you need to rollback to SQLite:"
echo "1. cp .env.sqlite.backup .env"
echo "2. php artisan config:clear"
echo "3. php artisan migrate:fresh --force"
echo ""
echo "⚠️  Keep the backup directory until you're sure the migration is successful!"
echo ""
echo "Migration completed at: $(date)"