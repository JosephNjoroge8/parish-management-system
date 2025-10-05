#!/bin/bash

# ============================================================================
# Parish Management System - Production Deployment Script
# ============================================================================
# This script automates the production deployment process
# Run this script on your production server to cement all changes
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
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

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

print_status "Starting Parish Management System Production Deployment..."

# Check required commands
print_status "Checking required dependencies..."
if ! command_exists php; then
    print_error "PHP is not installed!"
    exit 1
fi

if ! command_exists composer; then
    print_error "Composer is not installed!"
    exit 1
fi

if ! command_exists npm; then
    print_error "NPM is not installed!"
    exit 1
fi

print_success "All dependencies found!"

# Pull latest changes from Git
print_status "Pulling latest changes from repository..."
if git pull origin main; then
    print_success "Successfully pulled latest changes"
else
    print_warning "Git pull failed or no changes to pull"
fi

# Update PHP dependencies
print_status "Installing PHP dependencies (production mode)..."
if composer install --no-dev --optimize-autoloader --no-interaction; then
    print_success "PHP dependencies installed successfully"
else
    print_error "Failed to install PHP dependencies"
    exit 1
fi

# Install and build frontend assets
print_status "Installing and building frontend assets..."
if npm ci --production --silent; then
    print_success "NPM dependencies installed"
else
    print_error "Failed to install NPM dependencies"
    exit 1
fi

print_status "Building frontend assets for production..."
if npm run build; then
    print_success "Frontend assets built successfully"
else
    print_error "Failed to build frontend assets"
    exit 1
fi

# Clear all caches
print_status "Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize:clear
print_success "All caches cleared"

# Backup database before migrations
print_status "Creating database backup before migrations..."
BACKUP_DIR="backups"
BACKUP_FILE="${BACKUP_DIR}/db_backup_$(date +%Y%m%d_%H%M%S).sql"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Create database backup (adjust based on your database type)
if command_exists mysqldump; then
    # For MySQL
    DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
    DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
    DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
    DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" 2>/dev/null | grep -v "Psy Shell" | tail -1)
    
    if [ -n "$DB_NAME" ] && [ "$DB_NAME" != "null" ]; then
        if mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"; then
            print_success "Database backup created: $BACKUP_FILE"
        else
            print_warning "Failed to create database backup"
        fi
    fi
elif [ -f "database/database.sqlite" ]; then
    # For SQLite
    if cp database/database.sqlite "$BACKUP_DIR/database_backup_$(date +%Y%m%d_%H%M%S).sqlite"; then
        print_success "SQLite database backup created"
    else
        print_warning "Failed to backup SQLite database"
    fi
else
    print_warning "Could not determine database type for backup"
fi

# Check migration status before running
print_status "Checking migration status..."
php artisan migrate:status

# Run database migrations safely
print_status "Running database migrations..."
print_warning "⚠️  About to run migrations. This may modify your database structure."
echo "Migration status above shows what will be applied."

# Check if there are pending migrations
PENDING_MIGRATIONS=$(php artisan migrate:status | grep -c "Pending" || echo "0")
if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
    print_warning "Found $PENDING_MIGRATIONS pending migrations"
    
    # Run migrations with safety checks
    if php artisan migrate --force --no-interaction --step; then
        print_success "Database migrations completed successfully"
        
        # Show final migration status
        print_status "Final migration status:"
        php artisan migrate:status
    else
        print_error "Database migrations failed!"
        print_error "Please check the error above and restore from backup if needed:"
        print_error "Backup location: $BACKUP_FILE"
        exit 1
    fi
else
    print_success "No pending migrations found - database is up to date"
fi

# Optimize for production
print_status "Optimizing application for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
print_success "Application optimized for production"

# Set proper permissions (if running as root or with sudo)
if [ "$EUID" -eq 0 ]; then
    print_status "Setting proper file permissions..."
    chown -R www-data:www-data .
    chmod -R 755 .
    chmod -R 775 storage bootstrap/cache
    chmod 644 .env
    print_success "File permissions set"
else
    print_warning "Not running as root - please set proper permissions manually:"
    echo "  sudo chown -R www-data:www-data ."
    echo "  sudo chmod -R 755 ."
    echo "  sudo chmod -R 775 storage bootstrap/cache"
    echo "  sudo chmod 644 .env"
fi

# Create storage link if it doesn't exist
print_status "Creating storage symbolic link..."
if php artisan storage:link; then
    print_success "Storage link created/verified"
else
    print_warning "Storage link already exists or failed to create"
fi

# Restart web services (if available)
print_status "Attempting to restart web services..."
if command_exists systemctl; then
    if systemctl is-active --quiet nginx; then
        if systemctl restart nginx; then
            print_success "Nginx restarted"
        else
            print_warning "Failed to restart Nginx"
        fi
    fi
    
    if systemctl is-active --quiet php8.2-fpm; then
        if systemctl restart php8.2-fpm; then
            print_success "PHP-FPM restarted"
        else
            print_warning "Failed to restart PHP-FPM"
        fi
    elif systemctl is-active --quiet php8.1-fpm; then
        if systemctl restart php8.1-fpm; then
            print_success "PHP-FPM restarted"
        else
            print_warning "Failed to restart PHP-FPM"
        fi
    fi
else
    print_warning "systemctl not available - please restart web services manually"
fi

# Final health check
print_status "Running application health check..."
echo "=== Application Status ==="
echo "PHP Version: $(php -v | head -n 1)"
echo "Laravel Version: $(php artisan --version)"
echo "Environment: $(php artisan env)"
echo "Database Migrations: $(php artisan migrate:status | grep -c 'Ran')"
echo "Storage Link: $(ls -la public/storage 2>/dev/null && echo 'OK' || echo 'MISSING')"

# Check if .env exists and is readable
if [ -r .env ]; then
    echo "Environment File: OK"
    # Check critical environment variables
    if grep -q "APP_KEY=" .env && [ -n "$(grep "APP_KEY=" .env | cut -d'=' -f2)" ]; then
        echo "Application Key: SET"
    else
        print_warning "Application key not set! Run: php artisan key:generate"
    fi
else
    print_error "Environment file (.env) not found or not readable!"
fi

echo "=== End Status Check ==="

print_success "🎉 Deployment completed successfully!"
print_status "Your Parish Management System is now ready for production use."

# Display important reminders
echo ""
print_warning "⚠️  IMPORTANT REMINDERS:"
echo "1. Verify all environment variables in .env are correct"
echo "2. Test certificate generation functionality"
echo "3. Check that SSL certificate is properly configured"
echo "4. Backup your database regularly"
echo "5. Monitor application logs for any issues"
echo ""
print_status "📊 Monitor logs with: tail -f storage/logs/laravel.log"
print_status "🔍 Check application health: php artisan about"

echo ""
print_success "✨ Deployment script completed! Your changes are now cemented in production."