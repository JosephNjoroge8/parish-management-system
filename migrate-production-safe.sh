#!/bin/bash

# ============================================================================
# Safe Production Migration Script
# ============================================================================
# This script provides extra safety when running migrations in production
# Use this for critical database changes or when you want extra control
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to prompt for confirmation
confirm_action() {
    while true; do
        read -p "$(echo -e ${YELLOW}$1${NC}) (y/n): " yn
        case $yn in
            [Yy]* ) return 0;;
            [Nn]* ) return 1;;
            * ) echo "Please answer yes or no.";;
        esac
    done
}

print_status "🔒 Safe Production Migration Tool"
echo "============================================"

# Step 1: Check current status
print_status "Step 1: Checking current migration status..."
php artisan migrate:status

echo ""
PENDING_COUNT=$(php artisan migrate:status | grep -c "Pending" || echo "0")
if [ "$PENDING_COUNT" -eq 0 ]; then
    print_success "✅ No pending migrations found!"
    echo "Your database is already up to date."
    exit 0
fi

print_warning "⚠️  Found $PENDING_COUNT pending migration(s)"

# Step 2: Show what will be migrated
echo ""
print_status "Step 2: Pending migrations to be applied:"
php artisan migrate:status | grep "Pending" | while read line; do
    echo "  📋 $line"
done

# Step 3: Create backup
echo ""
if confirm_action "Step 3: Create database backup before proceeding?"; then
    print_status "Creating database backup..."
    
    BACKUP_DIR="backups"
    mkdir -p "$BACKUP_DIR"
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    
    # Detect database type and create appropriate backup
    DB_CONNECTION=$(php artisan tinker --execute="echo config('database.default');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
    
    case $DB_CONNECTION in
        "mysql")
            print_status "Detected MySQL database"
            DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
            DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
            DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
            DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
            
            BACKUP_FILE="$BACKUP_DIR/mysql_backup_$TIMESTAMP.sql"
            if mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"; then
                print_success "MySQL backup created: $BACKUP_FILE"
            else
                print_error "Failed to create MySQL backup"
                exit 1
            fi
            ;;
        "sqlite")
            print_status "Detected SQLite database"
            BACKUP_FILE="$BACKUP_DIR/sqlite_backup_$TIMESTAMP.sqlite"
            if cp database/database.sqlite "$BACKUP_FILE"; then
                print_success "SQLite backup created: $BACKUP_FILE"
            else
                print_error "Failed to create SQLite backup"
                exit 1
            fi
            ;;
        *)
            print_warning "Unknown database type: $DB_CONNECTION"
            print_warning "Please create manual backup before proceeding"
            if ! confirm_action "Continue without automatic backup?"; then
                exit 1
            fi
            ;;
    esac
else
    print_warning "⚠️  Proceeding without backup"
    if ! confirm_action "Are you sure you want to continue without a backup?"; then
        print_status "Operation cancelled"
        exit 0
    fi
fi

# Step 4: Preview migration content (if possible)
echo ""
if confirm_action "Step 4: Do you want to preview the migration files?"; then
    print_status "Showing recent migration files..."
    
    # Find and show recent migration files
    find database/migrations -name "*.php" -newer database/migrations/2014_10_12_000000_create_users_table.php 2>/dev/null | head -5 | while read migration_file; do
        echo ""
        echo "📄 Migration: $(basename "$migration_file")"
        echo "----------------------------------------"
        # Show the up() method content
        sed -n '/public function up/,/public function down/p' "$migration_file" | head -20
        echo "..."
    done
fi

# Step 5: Run migrations with confirmation
echo ""
print_warning "⚠️  CRITICAL: About to modify production database"
echo "This action will:"
echo "  • Apply $PENDING_COUNT pending migration(s)"
echo "  • Potentially modify table structures"
echo "  • May add/remove columns or constraints"
echo ""

if confirm_action "Step 5: Proceed with migration execution?"; then
    print_status "🚀 Starting migration process..."
    
    # Option 1: Run all at once
    if confirm_action "Run all migrations at once? (Choose 'n' for step-by-step)"; then
        print_status "Running all migrations..."
        if php artisan migrate --force --no-interaction; then
            print_success "✅ All migrations completed successfully!"
        else
            print_error "❌ Migration failed!"
            if [ -n "$BACKUP_FILE" ]; then
                print_error "Restore from backup: $BACKUP_FILE"
            fi
            exit 1
        fi
    else
        # Option 2: Step by step migrations
        print_status "Running migrations step by step..."
        if php artisan migrate --force --no-interaction --step; then
            print_success "✅ Step-by-step migrations completed successfully!"
        else
            print_error "❌ Migration failed!"
            if [ -n "$BACKUP_FILE" ]; then
                print_error "Restore from backup: $BACKUP_FILE"
            fi
            exit 1
        fi
    fi
    
    # Step 6: Verify final status
    echo ""
    print_status "Step 6: Final migration status verification..."
    php artisan migrate:status
    
    FINAL_PENDING=$(php artisan migrate:status | grep -c "Pending" || echo "0")
    if [ "$FINAL_PENDING" -eq 0 ]; then
        print_success "🎉 All migrations applied successfully!"
        print_success "Database is now up to date"
    else
        print_warning "⚠️  Still have $FINAL_PENDING pending migrations"
    fi
    
    # Optional: Clear caches after migration
    if confirm_action "Clear application caches after migration?"; then
        print_status "Clearing caches..."
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        php artisan cache:clear
        print_success "Caches cleared"
    fi
    
else
    print_status "Migration cancelled by user"
    exit 0
fi

echo ""
print_success "🏁 Safe migration process completed!"
if [ -n "$BACKUP_FILE" ]; then
    print_status "💾 Backup available at: $BACKUP_FILE"
fi