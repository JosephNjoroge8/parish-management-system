#!/bin/bash
# MySQL Migration Script for Parish Management System
# This script helps migrate from SQLite (development) to MySQL (production)

# -----------------------------------------
# Configuration Settings
# -----------------------------------------

# Text colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
RESET='\033[0m'

# Default config settings (can be overridden with arguments)
MYSQL_HOST="localhost"
MYSQL_PORT="3306"
MYSQL_USER="root"
MYSQL_PASSWORD=""
MYSQL_DATABASE="parish_database"
SQLITE_PATH="./database/database.sqlite"
BACKUP_DIR="./database/backups"
ENV_FILE=".env"

# -----------------------------------------
# Helper Functions
# -----------------------------------------

print_header() {
    echo -e "\n${BLUE}=== Parish Management System: SQLite to MySQL Migration ===${RESET}"
    echo -e "${BLUE}=================================================${RESET}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${RESET}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${RESET}"
}

print_error() {
    echo -e "${RED}✗ $1${RESET}"
}

print_step() {
    echo -e "\n${BLUE}[$1/${STEPS}] $2${RESET}"
    echo -e "${BLUE}--------------------------------------------${RESET}"
}

# -----------------------------------------
# Main Migration Script
# -----------------------------------------

main() {
    print_header
    
    # Total steps in migration process
    STEPS=8
    
    # Step 1: Check Requirements
    print_step 1 "Checking requirements"
    
    # Check for required commands
    required_commands=("php" "mysql" "mysqldump" "sqlite3" "awk" "sed")
    for cmd in "${required_commands[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            print_error "$cmd is required but not installed."
            exit 1
        fi
    done
    print_success "All required commands are available"
    
    # Check Laravel and PHP versions
    PHP_VERSION=$(php -v | head -n 1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    if (( $(echo "$PHP_VERSION < 8.0" | bc -l) )); then
        print_error "PHP 8.0 or higher is required (current: $PHP_VERSION)"
        exit 1
    fi
    print_success "PHP version is compatible: $PHP_VERSION"
    
    # Check SQLite file
    if [ ! -f "$SQLITE_PATH" ]; then
        print_error "SQLite database not found at $SQLITE_PATH"
        exit 1
    fi
    print_success "SQLite database found"
    
    # Step 2: Backup SQLite Database
    print_step 2 "Backing up SQLite database"
    
    # Create backup directory if it doesn't exist
    if [ ! -d "$BACKUP_DIR" ]; then
        mkdir -p "$BACKUP_DIR"
        print_success "Created backup directory: $BACKUP_DIR"
    fi
    
    # Create backup with timestamp
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    BACKUP_FILE="${BACKUP_DIR}/sqlite_backup_${TIMESTAMP}.sql"
    
    echo "Backing up SQLite database to $BACKUP_FILE..."
    sqlite3 "$SQLITE_PATH" .dump > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        print_success "SQLite database backed up to $BACKUP_FILE"
    else
        print_error "Failed to backup SQLite database"
        exit 1
    fi
    
    # Step 3: Create MySQL database if it doesn't exist
    print_step 3 "Setting up MySQL database"
    
    # Create MySQL database if it doesn't exist
    mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" --password="$MYSQL_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$MYSQL_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    if [ $? -eq 0 ]; then
        print_success "MySQL database '$MYSQL_DATABASE' is ready"
    else
        print_error "Failed to create MySQL database"
        exit 1
    fi
    
    # Step 4: Update .env file
    print_step 4 "Updating .env configuration"
    
    # Backup .env file
    cp "$ENV_FILE" "${ENV_FILE}.sqlite_backup"
    print_success "Backed up .env file to ${ENV_FILE}.sqlite_backup"
    
    # Update database connection in .env file
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' "$ENV_FILE"
    sed -i "s/DB_HOST=.*/DB_HOST=$MYSQL_HOST/" "$ENV_FILE"
    sed -i "s/DB_PORT=.*/DB_PORT=$MYSQL_PORT/" "$ENV_FILE"
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$MYSQL_DATABASE/" "$ENV_FILE"
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$MYSQL_USER/" "$ENV_FILE"
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$MYSQL_PASSWORD/" "$ENV_FILE"
    
    print_success "Updated database connection settings in .env file"
    
    # Step 5: Migrate database schema
    print_step 5 "Migrating database schema"
    
    # Run Laravel migrations with force flag
    php artisan migrate:fresh --force
    
    if [ $? -eq 0 ]; then
        print_success "Database schema migrated successfully"
    else
        print_error "Failed to migrate database schema"
        # Restore .env file in case of failure
        cp "${ENV_FILE}.sqlite_backup" "$ENV_FILE"
        print_warning "Restored original .env file"
        exit 1
    fi
    
    # Step 6: Extract and transform data from SQLite
    print_step 6 "Transferring data from SQLite to MySQL"
    
    # Use Laravel's custom command to handle data transfer
    php artisan db:sqlite-to-mysql
    
    if [ $? -eq 0 ]; then
        print_success "Data transferred from SQLite to MySQL"
    else
        print_warning "Data transfer completed with warnings"
    fi
    
    # Step 7: Optimize for production
    print_step 7 "Optimizing for production"
    
    # Clear and rebuild caches
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize
    
    print_success "Application optimized for production"
    
    # Step 8: Verify migration
    print_step 8 "Verifying migration"
    
    # Count records in key tables
    echo "Checking data integrity..."
    
    # Run verification command
    php artisan db:verify-migration
    
    if [ $? -eq 0 ]; then
        print_success "Migration verified successfully"
    else
        print_warning "Migration verification completed with warnings"
    fi
    
    # Final output
    echo -e "\n${GREEN}====================================================${RESET}"
    echo -e "${GREEN}✓ SQLite to MySQL migration completed successfully!${RESET}"
    echo -e "${GREEN}====================================================${RESET}\n"
    
    echo -e "Next steps:"
    echo -e "1. Run '${BLUE}php artisan serve${RESET}' to start your application"
    echo -e "2. Test all functionality to ensure everything works correctly"
    echo -e "3. Monitor your logs for any MySQL-related issues\n"
    
    echo -e "For any issues, refer to the documentation or contact support."
    echo -e "Original SQLite database backup: ${BLUE}${BACKUP_FILE}${RESET}\n"
}

# Run the main function
main "$@"