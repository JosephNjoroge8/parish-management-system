#!/bin/bash

# Production Deployment Verification Script
# Run this AFTER deployment to verify everything is working
# Usage: ./post-deploy-check.sh

echo "🔍 Post-Deployment Verification for Parish Management System"
echo "=============================================================="

# Set production paths
DEPLOY_PATH="/home2/shemidig/parish_system"
PHP_PATH="/usr/local/bin/ea-php82"

# Check if we're in production environment
if [ ! -d "$DEPLOY_PATH" ]; then
    echo "❌ Production deployment path not found: $DEPLOY_PATH"
    echo "💡 This script should be run on the production server"
    exit 1
fi

cd "$DEPLOY_PATH" || exit 1

echo "📍 Current directory: $(pwd)"
echo "📅 Check started at: $(date)"
echo ""

# Test 1: Laravel Framework
echo "🔧 Test 1: Laravel Framework Check"
if [ -f "artisan" ]; then
    LARAVEL_VERSION=$($PHP_PATH artisan --version 2>/dev/null)
    if [ $? -eq 0 ]; then
        echo "✅ Laravel is working: $LARAVEL_VERSION"
    else
        echo "❌ Laravel artisan command failed"
    fi
else
    echo "❌ artisan file not found"
fi
echo ""

# Test 2: Environment Configuration
echo "🔧 Test 2: Environment Configuration"
if [ -f ".env" ]; then
    echo "✅ .env file exists"
    
    # Check database configuration
    if grep -q "DB_CONNECTION=mysql" .env 2>/dev/null; then
        echo "✅ Database set to MySQL"
    else
        echo "⚠️  Database connection not set to MySQL"
    fi
    
    # Check app key
    if grep -q "APP_KEY=base64:" .env 2>/dev/null; then
        echo "✅ Application key configured"
    else
        echo "⚠️  Application key missing or invalid"
    fi
    
    # Show database name (masked for security)
    DB_NAME=$(grep "DB_DATABASE=" .env | cut -d'=' -f2 | sed 's/./*/g')
    echo "✅ Database name: $DB_NAME"
else
    echo "❌ .env file missing!"
fi
echo ""

# Test 3: Database Connection
echo "🔧 Test 3: Database Connection Test"
DB_TEST_RESULT=$($PHP_PATH artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    \$version = \$pdo->query('SELECT VERSION()')->fetchColumn();
    echo 'SUCCESS: MySQL ' . \$version;
} catch (Exception \$e) {
    echo 'FAILED: ' . \$e->getMessage();
    exit(1);
}
" 2>&1)

if echo "$DB_TEST_RESULT" | grep -q "SUCCESS"; then
    echo "✅ Database Connection: $DB_TEST_RESULT"
else
    echo "❌ Database Connection Failed: $DB_TEST_RESULT"
fi
echo ""

# Test 4: Database Tables
echo "🔧 Test 4: Database Schema Check"
TABLE_COUNT=$($PHP_PATH artisan tinker --execute="
try {
    \$tables = DB::select('SHOW TABLES');
    echo count(\$tables);
} catch (Exception \$e) {
    echo '0';
}
" 2>/dev/null)

if [ "$TABLE_COUNT" -gt 0 ]; then
    echo "✅ Database tables found: $TABLE_COUNT tables"
else
    echo "❌ No database tables found - migrations may not have run"
fi
echo ""

# Test 5: Seeded Data Check
echo "🔧 Test 5: Seeder Data Verification"

# Check for roles
ROLE_COUNT=$($PHP_PATH artisan tinker --execute="
try {
    echo DB::table('roles')->count();
} catch (Exception \$e) {
    echo '0';
}
" 2>/dev/null)

if [ "$ROLE_COUNT" -gt 0 ]; then
    echo "✅ Roles seeded: $ROLE_COUNT roles found"
else
    echo "⚠️  No roles found - RolePermissionSeeder may not have run"
fi

# Check for permissions
PERMISSION_COUNT=$($PHP_PATH artisan tinker --execute="
try {
    echo DB::table('permissions')->count();
} catch (Exception \$e) {
    echo '0';
}
" 2>/dev/null)

if [ "$PERMISSION_COUNT" -gt 0 ]; then
    echo "✅ Permissions seeded: $PERMISSION_COUNT permissions found"
else
    echo "⚠️  No permissions found - RolePermissionSeeder may not have run"
fi

# Check for admin user
USER_COUNT=$($PHP_PATH artisan tinker --execute="
try {
    echo DB::table('users')->count();
} catch (Exception \$e) {
    echo '0';
}
" 2>/dev/null)

if [ "$USER_COUNT" -gt 0 ]; then
    echo "✅ Users seeded: $USER_COUNT users found"
else
    echo "⚠️  No users found - ProductionSeeder may not have run"
fi
echo ""

# Test 6: File Permissions
echo "🔧 Test 6: Critical File Permissions"
for dir in "storage" "bootstrap/cache"; do
    if [ -d "$dir" ]; then
        PERMS=$(stat -c "%a" "$dir" 2>/dev/null || stat -f "%A" "$dir" 2>/dev/null)
        if [ -w "$dir" ]; then
            echo "✅ $dir (permissions: $PERMS) - writable"
        else
            echo "❌ $dir (permissions: $PERMS) - not writable"
        fi
    else
        echo "❌ $dir directory missing"
    fi
done
echo ""

# Test 7: Laravel Optimization Status
echo "🔧 Test 7: Laravel Cache Status"
CACHE_FILES=("bootstrap/cache/config.php" "bootstrap/cache/routes-v7.php" "bootstrap/cache/services.php")
for cache_file in "${CACHE_FILES[@]}"; do
    if [ -f "$cache_file" ]; then
        echo "✅ Cache exists: $cache_file"
    else
        echo "⚠️  Cache missing: $cache_file"
    fi
done
echo ""

# Test 8: Web Server Test
echo "🔧 Test 8: Web Accessibility Check"
if command -v curl >/dev/null 2>&1; then
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://parish.quovadisyouthhub.org/" 2>/dev/null || echo "000")
    if [ "$HTTP_STATUS" = "200" ]; then
        echo "✅ Website accessible: HTTP $HTTP_STATUS"
    else
        echo "⚠️  Website response: HTTP $HTTP_STATUS"
    fi
else
    echo "ℹ️  curl not available - manual web test required"
fi
echo ""

# Summary
echo "📋 POST-DEPLOYMENT SUMMARY"
echo "=========================="
echo "🌐 Parish Management System URL: http://parish.quovadisyouthhub.org/"
echo "📅 Verification completed at: $(date)"
echo ""

# Quick action commands
echo "🔧 Quick Action Commands:"
echo "   Re-run migrations: $PHP_PATH artisan migrate --force"
echo "   Re-run seeders: $PHP_PATH artisan db:seed --force"
echo "   Clear all cache: $PHP_PATH artisan config:clear && $PHP_PATH artisan cache:clear"
echo "   Check Laravel status: $PHP_PATH artisan --version"
echo ""

echo "💡 If any tests failed, check the deployment logs in cPanel Git Version Control"