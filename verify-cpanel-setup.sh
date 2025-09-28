#!/bin/bash

# Parish Management System - cPanel Environment Verification
# Run this in your cPanel environment to verify everything is set up correctly

echo "🏛️  Parish Management System - Environment Verification"
echo "====================================================="

# Check if we're in Laravel directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Are you in the Laravel project directory?"
    exit 1
fi

echo "✅ Laravel project detected"

# Check .env file
if [ -f ".env" ]; then
    echo "✅ .env file exists (preserved existing configuration)"
    
    # Check critical variables
    if grep -q "APP_KEY=" .env && grep -q "DB_DATABASE=" .env; then
        echo "✅ Essential environment variables found"
    else
        echo "⚠️  Some essential variables might be missing in .env"
        echo "   Please check ENV_REFERENCE.md for required variables"
    fi
else
    echo "❌ No .env file found"
    echo "   You need to create a .env file with your cPanel configuration"
    exit 1
fi

# Check database connection
echo "🔍 Testing database connection..."
php artisan migrate:status > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Database connection successful"
else
    echo "❌ Database connection failed"
    echo "   Please check your database credentials in .env"
fi

# Check if tables exist
echo "🗄️  Checking database tables..."
php artisan migrate:status | grep -q "users"
if [ $? -eq 0 ]; then
    echo "✅ Core tables exist"
else
    echo "⚠️  Some tables might be missing - consider running migrations"
fi

# Check authentication system
echo "🔐 Verifying authentication system..."
USER_COUNT=$(php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null | tail -1)
if [ ! -z "$USER_COUNT" ] && [ "$USER_COUNT" -gt 0 ]; then
    echo "✅ Authentication system working ($USER_COUNT users found)"
else
    echo "⚠️  Authentication system needs setup"
    echo "   Run: php artisan db:seed --class=RolePermissionSeeder --force"
fi

# Check file permissions
echo "📁 Checking file permissions..."
if [ -w "storage" ] && [ -w "bootstrap/cache" ]; then
    echo "✅ Write permissions OK"
else
    echo "⚠️  Permission issues detected"
    echo "   Run: chmod -R 755 storage bootstrap/cache"
fi

# Check if storage is linked
if [ -L "public/storage" ]; then
    echo "✅ Storage link exists"
else
    echo "⚠️  Storage link missing"
    echo "   Run: php artisan storage:link"
fi

# Performance optimization check
if [ -f "bootstrap/cache/config.php" ]; then
    echo "✅ Configuration cached for performance"
else
    echo "ℹ️  Consider caching config for better performance"
    echo "   Run: php artisan config:cache"
fi

echo ""
echo "🎯 System Status Summary:"
echo "========================"
echo "✅ = Good to go"
echo "ℹ️  = Optional improvement"
echo "⚠️  = Needs attention"
echo "❌ = Critical issue"
echo ""
echo "For detailed setup instructions, see DEPLOYMENT_GUIDE.md"
echo "For environment variables reference, see ENV_REFERENCE.md"