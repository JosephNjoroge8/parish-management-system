#!/bin/bash

# ============================================================================
# SQLITE TO MYSQL MIGRATION SCRIPT - Parish Management System
# ============================================================================
# This script helps migrate from SQLite to MySQL in production
# 
# IMPORTANT: 
# - Run this script on the production server
# - Ensure you have MySQL credentials ready
# - Backup your SQLite database first
# - Test on staging environment first
# ============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

echo "🔄 SQLite to MySQL Migration - Parish Management System"
echo "======================================================"
echo "$(date): Starting database migration..."

# Check if we're in Laravel directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

print_info "Detected Laravel project directory"

# Step 1: Environment Backup
echo ""
echo "📋 Step 1: Environment Backup"
echo "============================="

if [ -f ".env" ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    print_status "Environment file backed up"
else
    print_error ".env file not found!"
    exit 1
fi

# Step 2: Database Backup
echo ""
echo "💾 Step 2: SQLite Database Backup"
echo "================================="

SQLITE_DB=""
if [ -f "database/database.sqlite" ]; then
    SQLITE_DB="database/database.sqlite"
elif [ -f "database.sqlite" ]; then
    SQLITE_DB="database.sqlite"
else
    print_error "SQLite database not found!"
    exit 1
fi

BACKUP_FILE="database_backup_$(date +%Y%m%d_%H%M%S).sqlite"
cp "$SQLITE_DB" "$BACKUP_FILE"
print_status "SQLite database backed up to: $BACKUP_FILE"

# Step 3: Export Data from SQLite
echo ""
echo "📤 Step 3: Export Data from SQLite"
echo "=================================="

# Create SQL dump
DUMP_FILE="database_export_$(date +%Y%m%d_%H%M%S).sql"

# Export schema and data
sqlite3 "$SQLITE_DB" .dump > "$DUMP_FILE"
print_status "Database exported to: $DUMP_FILE"

# Also create table-specific exports for critical data
mkdir -p exports
sqlite3 "$SQLITE_DB" -header -csv "SELECT * FROM members;" > exports/members.csv 2>/dev/null || print_warning "Members table export failed"
sqlite3 "$SQLITE_DB" -header -csv "SELECT * FROM families;" > exports/families.csv 2>/dev/null || print_warning "Families table export failed"
sqlite3 "$SQLITE_DB" -header -csv "SELECT * FROM tithes;" > exports/tithes.csv 2>/dev/null || print_warning "Tithes table export failed"
sqlite3 "$SQLITE_DB" -header -csv "SELECT * FROM sacraments;" > exports/sacraments.csv 2>/dev/null || print_warning "Sacraments table export failed"
sqlite3 "$SQLITE_DB" -header -csv "SELECT * FROM users;" > exports/users.csv 2>/dev/null || print_warning "Users table export failed"

print_status "Critical data exported to CSV files in exports/ directory"

# Step 4: MySQL Configuration
echo ""
echo "🔧 Step 4: MySQL Configuration"
echo "=============================="

print_info "Please provide MySQL database credentials:"

read -p "MySQL Host (default: localhost): " MYSQL_HOST
MYSQL_HOST=${MYSQL_HOST:-localhost}

read -p "MySQL Port (default: 3306): " MYSQL_PORT
MYSQL_PORT=${MYSQL_PORT:-3306}

read -p "MySQL Database Name: " MYSQL_DATABASE
if [ -z "$MYSQL_DATABASE" ]; then
    print_error "Database name is required!"
    exit 1
fi

read -p "MySQL Username: " MYSQL_USERNAME
if [ -z "$MYSQL_USERNAME" ]; then
    print_error "Username is required!"
    exit 1
fi

read -s -p "MySQL Password: " MYSQL_PASSWORD
echo
if [ -z "$MYSQL_PASSWORD" ]; then
    print_error "Password is required!"
    exit 1
fi

# Test MySQL connection
print_info "Testing MySQL connection..."
mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_status "MySQL connection successful"
else
    print_error "MySQL connection failed! Please check your credentials."
    exit 1
fi

# Step 5: Update Laravel Configuration
echo ""
echo "⚙️  Step 5: Update Laravel Configuration"
echo "======================================="

# Backup current .env
cp .env .env.pre_mysql

# Update .env for MySQL
sed -i.bak "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i.bak "s/DB_HOST=.*/DB_HOST=$MYSQL_HOST/" .env
sed -i.bak "s/DB_PORT=.*/DB_PORT=$MYSQL_PORT/" .env
sed -i.bak "s/DB_DATABASE=.*/DB_DATABASE=$MYSQL_DATABASE/" .env
sed -i.bak "s/DB_USERNAME=.*/DB_USERNAME=$MYSQL_USERNAME/" .env
sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=$MYSQL_PASSWORD/" .env

print_status "Laravel configuration updated for MySQL"

# Step 6: Clear Laravel Caches
echo ""
echo "🧹 Step 6: Clear Laravel Caches"
echo "==============================="

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_status "Laravel caches cleared"

# Step 7: Test MySQL Connection
echo ""
echo "🔍 Step 7: Test Laravel MySQL Connection"
echo "========================================"

if php artisan migrate:status > /dev/null 2>&1; then
    print_status "Laravel can connect to MySQL successfully"
else
    print_error "Laravel cannot connect to MySQL!"
    print_info "Restoring original .env file..."
    cp .env.pre_mysql .env
    exit 1
fi

# Step 8: Run Migrations
echo ""
echo "🗄️  Step 8: Run Database Migrations"
echo "=================================="

print_warning "This will create the database schema in MySQL"
read -p "Continue with migrations? [y/N]: " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    print_status "Database migrations completed"
else
    print_warning "Migrations skipped. You'll need to run them manually later."
fi

# Step 9: Data Import Options
echo ""
echo "📥 Step 9: Data Import Options"
echo "============================="

print_info "Choose data import method:"
echo "1. Use Laravel Seeders (recommended)"
echo "2. Manual CSV import"
echo "3. Custom SQL import (advanced)"
echo "4. Skip for now"

read -p "Select option [1-4]: " -n 1 -r
echo

case $REPLY in
    1)
        print_info "Option 1: Laravel Seeders"
        print_warning "You'll need to create seeders that read from the exported CSV files"
        print_info "Example: php artisan make:seeder ImportMembersSeeder"
        print_info "CSV files are available in the exports/ directory"
        ;;
    2)
        print_info "Option 2: Manual CSV Import"
        print_info "Use phpMyAdmin or MySQL Workbench to import CSV files:"
        ls -la exports/*.csv 2>/dev/null || print_warning "No CSV files found"
        ;;
    3)
        print_info "Option 3: Custom SQL Import"
        print_warning "The SQLite dump may need manual conversion for MySQL compatibility"
        print_info "SQLite dump file: $DUMP_FILE"
        ;;
    4)
        print_warning "Data import skipped. Data must be imported manually."
        ;;
    *)
        print_warning "Invalid option. Data import skipped."
        ;;
esac

# Step 10: Verification
echo ""
echo "✅ Step 10: Migration Verification"
echo "================================="

# Test database connection
print_info "Testing database operations..."

if php artisan migrate:status > /dev/null 2>&1; then
    print_status "✅ Database connection working"
else
    print_error "❌ Database connection failed"
fi

# Check if tables exist
TABLE_COUNT=$(mysql -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USERNAME" -p"$MYSQL_PASSWORD" -D"$MYSQL_DATABASE" -e "SHOW TABLES;" 2>/dev/null | wc -l)
if [ "$TABLE_COUNT" -gt 1 ]; then
    print_status "✅ Database tables created ($((TABLE_COUNT-1)) tables)"
else
    print_warning "⚠️  No tables found in database"
fi

# Final Steps
echo ""
echo "🎯 Step 11: Final Steps & Cleanup"
echo "================================"

print_info "Migration Summary:"
echo "===================="
echo "✅ SQLite database backed up: $BACKUP_FILE"
echo "✅ Data exported: $DUMP_FILE"
echo "✅ CSV exports created in exports/ directory"
echo "✅ Laravel configured for MySQL"
echo "✅ Database migrations run"

echo ""
print_warning "IMPORTANT NEXT STEPS:"
echo "1. Import your data using one of the methods above"
echo "2. Test all application functionality thoroughly"
echo "3. Update any hardcoded SQLite references in your code"
echo "4. Remove SQLite database files once everything is working"
echo "5. Update backup scripts to use MySQL"

echo ""
print_info "ROLLBACK INSTRUCTIONS (if needed):"
echo "1. cp .env.pre_mysql .env"
echo "2. php artisan config:clear"
echo "3. Test application with SQLite"

echo ""
print_status "🎉 SQLite to MySQL migration completed!"
print_warning "Remember to test all functionality before removing SQLite files!"
print_info "Migration completed at: $(date)"