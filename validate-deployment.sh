#!/bin/bash
# Parish Management System - Deployment Validation Script
# Use this script to test and validate your cPanel deployment
# Usage: bash validate-deployment.sh

echo "🔍 Parish Management System - Deployment Validation"
echo "=================================================="

# Variables
PHP_PATH="/usr/local/bin/ea-php82"
DEPLOY_PATH="/home2/shemidig/parish_system"

echo "📊 System Information:"
echo "   PHP Path: $PHP_PATH"
echo "   Deploy Path: $DEPLOY_PATH"
echo "   Current Date: $(date)"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Are you in the Laravel project directory?"
    exit 1
fi

echo "✅ Laravel project detected"
echo ""

# Test 1: Check PHP version
echo "🔧 Test 1: PHP Version Check"
if command -v $PHP_PATH >/dev/null 2>&1; then
    PHP_VERSION=$($PHP_PATH --version | head -n1)
    echo "✅ PHP Available: $PHP_VERSION"
else
    echo "❌ PHP not found at $PHP_PATH"
fi
echo ""

# Test 2: Laravel Artisan Check
echo "🔧 Test 2: Laravel Artisan Check"
if $PHP_PATH artisan --version >/dev/null 2>&1; then
    LARAVEL_VERSION=$($PHP_PATH artisan --version)
    echo "✅ Laravel Working: $LARAVEL_VERSION"
else
    echo "❌ Laravel Artisan not working"
fi
echo ""

# Test 3: Environment File Check
echo "🔧 Test 3: Environment Configuration"
if [ -f ".env" ]; then
    echo "✅ .env file exists"
    
    # Check critical environment variables
    if grep -q "DB_CONNECTION=mysql" .env; then
        echo "✅ MySQL database configured"
    else
        echo "⚠️  Database connection not set to MySQL"
    fi
    
    if grep -q "APP_KEY=base64:" .env; then
        echo "✅ Application key configured"
    else
        echo "⚠️  Application key not set"
    fi
    
    if grep -q "DB_DATABASE=" .env; then
        DB_NAME=$(grep "DB_DATABASE=" .env | cut -d'=' -f2)
        echo "✅ Database name: $DB_NAME"
    else
        echo "⚠️  Database name not configured"
    fi
else
    echo "❌ .env file missing"
fi
echo ""

# Test 4: Database Connection
echo "🔧 Test 4: Database Connection Test"
DB_TEST=$($PHP_PATH artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    \$version = \$pdo->query('SELECT VERSION()')->fetchColumn();
    echo 'SUCCESS: MySQL ' . \$version;
} catch (Exception \$e) {
    echo 'FAILED: ' . \$e->getMessage();
}
" 2>&1)

if echo "$DB_TEST" | grep -q "SUCCESS"; then
    echo "✅ Database Connection: $DB_TEST"
else
    echo "❌ Database Connection Failed: $DB_TEST"
fi
echo ""

# Test 5: Required Directories and Permissions
echo "🔧 Test 5: Directory Permissions"
DIRS_TO_CHECK=("storage" "bootstrap/cache" "public")

for dir in "${DIRS_TO_CHECK[@]}"; do
    if [ -d "$dir" ]; then
        PERMS=$(stat -c "%a" "$dir" 2>/dev/null || stat -f "%A" "$dir" 2>/dev/null)
        if [ -w "$dir" ]; then
            echo "✅ $dir (permissions: $PERMS) - writable"
        else
            echo "⚠️  $dir (permissions: $PERMS) - not writable"
        fi
    else
        echo "❌ $dir - directory missing"
    fi
done
echo ""

# Test 6: Composer Dependencies
echo "🔧 Test 6: Dependencies Check"
if [ -f "vendor/autoload.php" ]; then
    echo "✅ Composer dependencies installed"
else
    echo "❌ Composer dependencies missing"
fi

if [ -f "public/build/manifest.json" ] || [ -f "public/js/app.js" ]; then
    echo "✅ Frontend assets built"
else
    echo "⚠️  Frontend assets may need building"
fi
echo ""

# Test 7: Laravel Caches
echo "🔧 Test 7: Laravel Cache Status"
CACHE_FILES=("bootstrap/cache/config.php" "bootstrap/cache/routes-v7.php" "bootstrap/cache/services.php")

for cache_file in "${CACHE_FILES[@]}"; do
    if [ -f "$cache_file" ]; then
        echo "✅ Cache exists: $cache_file"
    else
        echo "ℹ️  Cache missing: $cache_file (will be generated)"
    fi
done
echo ""

# Test 8: Database Tables Check
echo "🔧 Test 8: Database Schema Check"
TABLES_CHECK=$($PHP_PATH artisan tinker --execute="
try {
    \$tables = DB::select('SHOW TABLES');
    echo 'Tables found: ' . count(\$tables);
    if (count(\$tables) > 10) {
        echo ' (Schema appears complete)';
    } else {
        echo ' (May need migrations)';
    }
} catch (Exception \$e) {
    echo 'Failed to check tables: ' . \$e->getMessage();
}
" 2>&1)
echo "📊 $TABLES_CHECK"
echo ""

# Test 9: Seeder Data Check
echo "🔧 Test 9: Seeder Data Verification"
USERS_CHECK=$($PHP_PATH artisan tinker --execute="
try {
    \$userCount = DB::table('users')->count();
    \$roleCount = DB::table('roles')->count();
    echo 'Users: ' . \$userCount . ', Roles: ' . \$roleCount;
} catch (Exception \$e) {
    echo 'Failed to check seeder data: ' . \$e->getMessage();
}
" 2>&1)
echo "👥 $USERS_CHECK"
echo ""

# Summary and Recommendations
echo "📋 VALIDATION SUMMARY"
echo "===================="

# Count issues
ISSUES=0

if ! command -v $PHP_PATH >/dev/null 2>&1; then
    ((ISSUES++))
fi

if ! $PHP_PATH artisan --version >/dev/null 2>&1; then
    ((ISSUES++))
fi

if [ ! -f ".env" ]; then
    ((ISSUES++))
fi

if ! echo "$DB_TEST" | grep -q "SUCCESS"; then
    ((ISSUES++))
fi

if [ ! -f "vendor/autoload.php" ]; then
    ((ISSUES++))
fi

if [ $ISSUES -eq 0 ]; then
    echo "🎉 All tests passed! Your deployment should work perfectly."
    echo ""
    echo "🚀 Ready to deploy! Your .cpanel.yml should work without issues."
    echo ""
    echo "📝 Next steps:"
    echo "   1. Commit and push your changes to trigger deployment"
    echo "   2. Monitor the deployment logs in cPanel Git Version Control"
    echo "   3. Test your application at: http://parish.quovadisyouthhub.org/"
else
    echo "⚠️  $ISSUES issues found. Please resolve them before deployment:"
    echo ""
    echo "🔧 Common fixes:"
    echo "   - Ensure .env file exists with correct database settings"
    echo "   - Run: composer install (if dependencies missing)"
    echo "   - Check database credentials and connectivity"
    echo "   - Verify file permissions on storage directories"
fi

echo ""
echo "🔗 For support, check the deployment logs in cPanel Git Version Control"
echo "📅 Validation completed at: $(date)"