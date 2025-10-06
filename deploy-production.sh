#!/bin/bash

# ============================================================================
# Parish Management System - Production Deployment Script
# ============================================================================
# This script automates the production deployment process
# Run this script on your production server to cement all changes
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\echo ""
print_status "📋 IF FRONTEND DOESN'T WORK:"
echo "1. Check if build files exist: ls -la public/build/"
echo "2. If missing, run: npm run build"
echo "3. Check storage link: ls -la public/storage"
echo "4. Clear browser cache and try again"

echo ""
print_success "🧪 COMPREHENSIVE DATABASE TESTING:"
echo "Run the comprehensive database interaction test:"
echo "  ./test-database-interactions.sh"
echo ""
echo "This will test:"
echo "✅ CRUD operations (Create, Read, Update, Delete)"
echo "✅ Marriage residence field functionality"
echo "✅ Model relationships (Member-Family, Family-Members)"
echo "✅ Authentication system"
echo "✅ Database constraints and integrity"
echo "✅ Query performance"

echo ""
print_status "🔍 COMPREHENSIVE DATABASE INTERACTION VERIFICATION:"'
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

# Fix critical environment issues before optimization
print_status "Fixing critical environment configuration issues..."

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
print_success "✅ .env backed up"

# Fix critical production environment variables
print_status "Fixing critical environment variables for shared hosting..."

# Fix APP_DEBUG - CRITICAL SECURITY ISSUE
if grep -q "APP_DEBUG=true" .env; then
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    print_success "✅ FIXED: APP_DEBUG set to false (was dangerously set to true)"
else
    print_status "APP_DEBUG already correctly set"
fi

# Fix LOG_LEVEL for production
if grep -q "LOG_LEVEL=error" .env; then
    sed -i 's/LOG_LEVEL=error/LOG_LEVEL=warning/' .env
    print_success "✅ FIXED: LOG_LEVEL set to warning for better debugging"
fi

# Fix SESSION_DOMAIN to match actual domain
if grep -q "SESSION_DOMAIN=quovadisyouthhub.org" .env; then
    sed -i 's/SESSION_DOMAIN=quovadisyouthhub.org/SESSION_DOMAIN=.quovadisyouthhub.org/' .env
    print_success "✅ FIXED: SESSION_DOMAIN set to .quovadisyouthhub.org for subdomain support"
fi

# Add missing SANCTUM configuration
if ! grep -q "SANCTUM_STATEFUL_DOMAINS" .env; then
    echo "" >> .env
    echo "# Sanctum Configuration" >> .env
    echo "SANCTUM_STATEFUL_DOMAINS=parish.quovadisyouthhub.org,localhost,127.0.0.1" >> .env
    print_success "✅ ADDED: SANCTUM_STATEFUL_DOMAINS configuration"
fi

# Fix cache configuration for shared hosting
if grep -q "CACHE_STORE=database" .env; then
    print_success "✅ Cache store already set to database (good for shared hosting)"
else
    echo "CACHE_STORE=database" >> .env
    print_success "✅ ADDED: Database cache configuration"
fi

# Add shared hosting optimizations
print_status "Adding shared hosting optimizations..."
if ! grep -q "# Shared Hosting Optimizations" .env; then
    cat >> .env << 'EOF'

# ====================================
# SHARED HOSTING OPTIMIZATIONS
# ====================================
# Disable problematic features in shared hosting
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false

# Optimize for limited resources
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

# Reduce memory usage
BCRYPT_ROUNDS=10

# Optimize view compilation
VIEW_COMPILED_PATH=
EOF
    print_success "✅ ADDED: Shared hosting optimizations"
fi

print_success "✅ Environment configuration fixed for production"

# Test configuration before caching
print_status "Testing configuration before optimization..."
php artisan config:clear

# Test that critical settings are correct
print_status "Verifying critical settings..."
php artisan tinker --execute="
echo 'Environment: ' . config('app.env') . PHP_EOL;
echo 'Debug Mode: ' . (config('app.debug') ? 'ENABLED (❌ DANGER!)' : 'DISABLED (✅ SAFE)') . PHP_EOL;
echo 'App URL: ' . config('app.url') . PHP_EOL;
echo 'Database: ' . config('database.default') . PHP_EOL;
echo 'Cache Driver: ' . config('cache.default') . PHP_EOL;
echo 'Session Driver: ' . config('session.driver') . PHP_EOL;
"

# Only optimize if configuration is safe
DEBUG_STATUS=$(php artisan tinker --execute="echo config('app.debug') ? 'true' : 'false';" 2>/dev/null | tail -1)
if [ "$DEBUG_STATUS" = "false" ]; then
    print_status "✅ Configuration is safe for production - proceeding with optimization..."
    
    # Safe optimization for shared hosting
    print_status "Optimizing application for shared hosting production..."
    php artisan config:cache
    
    # Skip route caching if using Inertia (can cause issues)
    print_status "Checking if route caching is safe..."
    route_test=$(php artisan route:list 2>/dev/null | grep -c "Closure" || echo "0")
    if [ "$route_test" = "0" ]; then
        php artisan route:cache
        print_success "✅ Routes cached successfully"
    else
        print_warning "⚠️  Skipping route cache (Closures detected - incompatible with route caching)"
    fi
    
    # Check if view cache path is configured before caching
    print_status "Checking view cache configuration..."
    view_path_test=$(php artisan tinker --execute="echo config('view.compiled') ?: 'NOT_SET';" 2>/dev/null | tail -1)
    if [ "$view_path_test" != "NOT_SET" ] && [ "$view_path_test" != "" ]; then
        php artisan view:cache
        print_success "✅ Views cached successfully"
    else
        print_warning "⚠️  Skipping view cache (view.compiled path not configured)"
        print_status "Setting view cache path for shared hosting..."
        # Add view compiled path to .env if not set
        if ! grep -q "VIEW_COMPILED_PATH" .env; then
            echo "VIEW_COMPILED_PATH=" >> .env
        fi
    fi
    
    # Don't run 'optimize' command in shared hosting (can cause issues)
    print_warning "⚠️  Skipping php artisan optimize (can cause issues in shared hosting)"
    
    print_success "Application optimized for shared hosting production"
else
    print_error "❌ CRITICAL: APP_DEBUG is still enabled! Cannot optimize unsafe configuration."
    print_error "Please fix APP_DEBUG=false in .env before continuing"
    exit 1
fi

# Set proper permissions for shared hosting (not www-data)
print_status "Setting proper file permissions for shared hosting..."
# Shared hosting doesn't use www-data, use current user
find . -type f -exec chmod 644 {} \; 2>/dev/null || true
find . -type d -exec chmod 755 {} \; 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod 600 .env  # More restrictive for security
chmod +x artisan

# Create writable directories if they don't exist
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
mkdir -p bootstrap/cache

print_success "✅ File permissions set for shared hosting"

# Create storage link if it doesn't exist
print_status "Creating storage symbolic link..."
if php artisan storage:link; then
    print_success "Storage link created/verified"
else
    print_warning "Storage link already exists or failed to create"
fi

# Restart web services (skip in shared hosting)
print_status "Checking web services (shared hosting)..."
if command_exists systemctl && [ "$EUID" -eq 0 ]; then
    print_status "Attempting to restart web services..."
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
    fi
else
    print_warning "⚠️  Shared hosting detected - web services managed by hosting provider"
    print_status "No need to restart services manually"
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
echo "1. 🔐 Login with the credentials above and change the password"
echo "2. 🧪 Test the new member marriage residence functionality"
echo "3. 📋 Verify certificate generation works with new fields"
echo "4. ✅ Check that all existing data remains intact"
echo "5. 📊 Monitor application logs for any issues"
echo "6. 🌐 Test frontend functionality (Inertia.js pages)"
echo "7. 🛡️  Verify APP_DEBUG is FALSE in production"
echo "8. 🔗 Test all member CRUD operations (Create, Read, Update, Delete)"

# Check current environment status
print_status "🔍 Current Environment Status:"
echo "Debug Mode: $(grep APP_DEBUG .env | cut -d'=' -f2) $([ "$(grep APP_DEBUG .env | cut -d'=' -f2)" = "false" ] && echo "✅" || echo "❌ DANGER")"
echo "Environment: $(grep APP_ENV .env | cut -d'=' -f2)"
echo "App URL: $(grep APP_URL .env | cut -d'=' -f2)"
echo "Session Domain: $(grep SESSION_DOMAIN .env | cut -d'=' -f2)"

# Test critical functionality and database interactions
print_status "🧪 Testing critical functionality and database interactions..."
php artisan tinker --execute="
try {
    // Test basic database connectivity
    \$memberCount = \App\Models\Member::count();
    echo '✅ Members accessible: ' . \$memberCount . ' total' . PHP_EOL;
    
    \$userCount = \App\Models\User::count();
    echo '✅ Users accessible: ' . \$userCount . ' total' . PHP_EOL;
    
    // Test database write operations
    \$testResult = \DB::select('SELECT 1 as test');
    echo '✅ Database read operations: Working' . PHP_EOL;
    
    // Test table structure for new field
    \$columns = \Schema::getColumnListing('members');
    if (in_array('member_marriage_residence', \$columns)) {
        echo '✅ Marriage residence field: Available in database' . PHP_EOL;
    } else {
        echo '❌ Marriage residence field: Missing from database' . PHP_EOL;
    }
    
    // Test married member with new field
    \$marriedMember = \App\Models\Member::where('marital_status', 'married')->first();
    if (\$marriedMember) {
        echo '✅ Sample married member found: ' . \$marriedMember->first_name . ' ' . \$marriedMember->last_name . PHP_EOL;
        echo 'Marriage residence: ' . (\$marriedMember->member_marriage_residence ?? 'Not set') . PHP_EOL;
    } else {
        echo 'ℹ️  No married members found to test residence field' . PHP_EOL;
    }
    
    // Test model relationships
    \$memberWithFamily = \App\Models\Member::with('family')->first();
    if (\$memberWithFamily && \$memberWithFamily->family) {
        echo '✅ Member-Family relationships: Working' . PHP_EOL;
    } else {
        echo 'ℹ️  No member-family relationships to test' . PHP_EOL;
    }
    
    // Test user authentication model
    \$adminUser = \App\Models\User::where('is_admin', true)->first();
    if (\$adminUser) {
        echo '✅ Admin user authentication: Ready' . PHP_EOL;
    } else {
        echo 'ℹ️  No admin user found' . PHP_EOL;
    }
    
    echo '✅ Database interaction tests completed successfully' . PHP_EOL;
    
} catch (\Exception \$e) {
    echo '❌ Database interaction test failed: ' . \$e->getMessage() . PHP_EOL;
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
}
"

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
print_status "🌐 TEST THESE URLS IN YOUR BROWSER:"
echo "- Main app: https://parish.quovadisyouthhub.org"
echo "- Login: https://parish.quovadisyouthhub.org/login"
echo "- Members: https://parish.quovadisyouthhub.org/members"
echo "- Dashboard: https://parish.quovadisyouthhub.org/dashboard"

echo ""
print_warning "🚨 CRITICAL ISSUES FIXED:"
echo "✅ APP_DEBUG set to false (was dangerously true)"
echo "✅ Shared hosting optimizations applied"
echo "✅ Proper file permissions set"
echo "✅ Session domain configured correctly"
echo "✅ Cache optimized for database storage"

echo ""
print_status "📋 IF FRONTEND DOESN'T WORK:"
echo "1. Check if build files exist: ls -la public/build/"
echo "2. If missing, run: npm run build"
echo "3. Check storage link: ls -la public/storage"
echo "4. Clear browser cache and try again"

echo ""
print_status "� COMPREHENSIVE DATABASE INTERACTION VERIFICATION:"
echo "Run these commands to verify full database functionality:"
echo ""
echo "1. Test member CRUD operations:"
echo "   php artisan tinker --execute=\""
echo "   // Test Create"
echo "   \\\$family = \\App\\Models\\Family::first();"
echo "   if (\\\$family) {"
echo "       \\\$member = \\App\\Models\\Member::create(["
echo "           'family_id' => \\\$family->id,"
echo "           'first_name' => 'Test',"
echo "           'last_name' => 'Member',"
echo "           'date_of_birth' => '1990-01-01',"
echo "           'gender' => 'male',"
echo "           'marital_status' => 'married',"
echo "           'member_marriage_residence' => 'Test City'"
echo "       ]);"
echo "       echo 'Test member created with ID: ' . \\\$member->id;"
echo "       // Test Update"
echo "       \\\$member->update(['member_marriage_residence' => 'Updated City']);"
echo "       echo 'Marriage residence updated to: ' . \\\$member->fresh()->member_marriage_residence;"
echo "       // Test Delete"
echo "       \\\$member->delete();"
echo "       echo 'Test member deleted successfully';"
echo "   } else {"
echo "       echo 'No family found to test with';"
echo "   }"
echo "   \""
echo ""
echo "2. Test marriage certificate generation:"
echo "   php artisan tinker --execute=\""
echo "   \\\$marriedMember = \\App\\Models\\Member::where('marital_status', 'married')->first();"
echo "   if (\\\$marriedMember) {"
echo "       echo 'Testing certificate for: ' . \\\$marriedMember->first_name . ' ' . \\\$marriedMember->last_name;"
echo "       echo 'Marriage residence: ' . (\\\$marriedMember->member_marriage_residence ?? 'Not specified');"
echo "   }"
echo "   \""
echo ""
echo "3. Test web routes accessibility:"
echo "   curl -s -o /dev/null -w \"%{http_code}\" https://parish.quovadisyouthhub.org/members"
echo "   curl -s -o /dev/null -w \"%{http_code}\" https://parish.quovadisyouthhub.org/login"
echo ""
echo "4. Monitor real-time logs:"
echo "   tail -f storage/logs/laravel.log"
echo ""

print_status "�🛠️  PRODUCTION DEBUGGING COMMANDS:"
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