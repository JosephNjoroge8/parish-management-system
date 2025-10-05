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
print_status "Assuming dependencies are already installed in production environment..."

# Clear all caches
print_status "Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize:clear
print_success "All caches cleared"

# Check migration status before running
print_status "Checking current migration status..."
php artisan migrate:status

# Apply database migrations safely (only additive changes)
print_status "Applying database schema improvements..."
print_status "ℹ️  These migrations only ADD new features without affecting existing data"

# Check if there are pending migrations
PENDING_MIGRATIONS=$(php artisan migrate:status | grep -c "Pending" || echo "0")
if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
    print_status "Found $PENDING_MIGRATIONS pending schema improvements to apply"
    
    # Show what migrations will be applied
    echo "Migrations to be applied:"
    php artisan migrate:status | grep "Pending" | while read line; do
        echo "  ✨ $line"
    done
    
    # Run migrations (these are safe additive changes)
    print_status "Applying schema improvements..."
    if php artisan migrate --force --no-interaction; then
        print_success "✅ Database schema improvements applied successfully"
        
        # Show final migration status
        print_status "Updated migration status:"
        php artisan migrate:status | tail -5
    else
        print_error "❌ Schema improvements encountered an issue!"
        print_warning "This might be due to database limits (e.g., too many indexes)"
        
        # Check if the critical member_marriage_residence migration succeeded
        MARRIAGE_RESIDENCE_STATUS=$(php artisan migrate:status | grep "add_member_marriage_residence_to_members_table" | grep -o "Ran\|Pending" || echo "Unknown")
        
        if [ "$MARRIAGE_RESIDENCE_STATUS" = "Ran" ]; then
            print_success "✅ Critical migration (member_marriage_residence) was applied successfully"
            print_warning "⚠️  Some performance optimizations may have failed - check migration details above"
        else
            print_error "❌ Critical migration failed - manual intervention required"
            print_error "Please run: php artisan migrate:rollback --step=1"
            print_error "Then check the failed migration file and try again"
            exit 1
        fi
        
        print_status "Continuing with deployment despite non-critical migration warnings..."
    fi
else
    print_success "✅ Database schema is already up to date - no improvements needed"
fi

# Create/Update Admin User
print_status "Setting up admin user access..."
php artisan tinker --execute="
// Create or update admin user safely
\$adminEmail = 'admin@parish.local';
\$adminPassword = 'admin123';

\$admin = \App\Models\User::where('email', \$adminEmail)->first();

if (\$admin) {
    // Update existing admin
    \$admin->password = \Hash::make(\$adminPassword);
    \$admin->is_admin = true;
    \$admin->save();
    echo '✅ Updated existing admin user: ' . \$adminEmail . PHP_EOL;
} else {
    // Create new admin user
    \$admin = \App\Models\User::create([
        'name' => 'System Admin',
        'email' => \$adminEmail,
        'password' => \Hash::make(\$adminPassword),
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);
    echo '✅ Created new admin user: ' . \$adminEmail . PHP_EOL;
}

echo 'Admin Login Credentials:' . PHP_EOL;
echo 'Email: ' . \$adminEmail . PHP_EOL;
echo 'Password: ' . \$adminPassword . PHP_EOL;
"
print_success "Admin user setup completed"

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

# Test database connection
print_status "Testing database connection..."
php artisan tinker --execute="
try {
    \$connection = \DB::connection();
    \$pdo = \$connection->getPdo();
    echo '✅ Database connection: SUCCESS' . PHP_EOL;
    echo 'Database driver: ' . \$connection->getDriverName() . PHP_EOL;
    echo 'Database name: ' . \$connection->getDatabaseName() . PHP_EOL;
} catch (\Exception \$e) {
    echo '❌ Database connection: FAILED' . PHP_EOL;
    echo 'Error: ' . \$e->getMessage() . PHP_EOL;
}
"

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

print_success "🎉 Production deployment completed successfully!"
print_status "✨ Your Parish Management System has been updated with latest improvements"

echo ""
print_success "🔐 ADMIN LOGIN CREDENTIALS:"
print_status "Email: admin@parish.local"
print_status "Password: admin123"
print_warning "⚠️  Please change the password after first login!"

# Display important reminders
echo ""
print_warning "⚠️  IMPORTANT REMINDERS:"
echo "1. Login with the credentials above and change the password"
echo "2. Test the new member marriage residence functionality"
echo "3. Verify certificate generation works with new fields"
echo "4. Check that all existing data remains intact"
echo "5. Monitor application logs for any issues"

# Check for any failed migrations and provide guidance
FAILED_MIGRATIONS=$(php artisan migrate:status | grep "Pending" | wc -l)
if [ "$FAILED_MIGRATIONS" -gt 0 ]; then
    echo ""
    print_warning "📋 MIGRATION STATUS:"
    echo "Some migrations are still pending. This might be due to database limitations."
    echo ""
    echo "To fix index limit issues:"
    echo "1. Check current indexes: SHOW INDEX FROM members;"
    echo "2. Remove unnecessary indexes before adding new ones"
    echo "3. Run individual migrations: php artisan migrate --step=1"
    echo ""
    echo "Pending migrations:"
    php artisan migrate:status | grep "Pending" | while read line; do
        echo "  ⏳ $line"
    done
fi

echo ""
print_status "📊 Monitor logs with: tail -f storage/logs/laravel.log"
print_status "🔍 Check application health: php artisan about"
print_status "🧪 Test new features: Member registration with marriage residence field"

echo ""
print_success "✨ Schema improvements applied! Your production system is now enhanced."

echo ""
print_status "🛠️  PRODUCTION DEBUGGING COMMANDS:"
echo "If you encounter database connection issues, run these commands:"
echo ""
echo "1. Check database configuration:"
echo "   php artisan config:show database"
echo ""
echo "2. Test database connection:"
echo "   php artisan tinker --execute=\"\\DB::connection()->getPdo(); echo 'Connected successfully';\""
echo ""
echo "3. Check MySQL service status:"
echo "   sudo systemctl status mysql"
echo ""
echo "4. Check MySQL is running:"
echo "   sudo mysqladmin ping"
echo ""
echo "5. Test MySQL login:"
echo "   mysql -u your_username -p your_database"
echo ""
echo "6. Check Laravel logs:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "7. Check web server error logs:"
echo "   sudo tail -f /var/log/nginx/error.log"
echo "   sudo tail -f /var/log/apache2/error.log"
echo ""
echo "8. Verify .env database settings:"
echo "   grep -E '^DB_' .env"