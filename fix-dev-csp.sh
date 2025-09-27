#!/bin/bash

# Development Environment Fix for CSP Issues
# Run this script to fix Content Security Policy issues in development

echo "🔧 Fixing Content Security Policy for Development"
echo "================================================="

# Check current environment
if [ -f ".env" ]; then
    echo "📋 Current environment settings:"
    grep -E "(APP_ENV|CSP_ENABLED)" .env || echo "No CSP settings found"
else
    echo "❌ No .env file found"
    exit 1
fi

echo ""
echo "🛠️  Applying development fixes..."

# Update .env for development
if grep -q "CSP_ENABLED" .env; then
    # Update existing CSP_ENABLED
    sed -i 's/CSP_ENABLED=.*/CSP_ENABLED=false/' .env
    echo "✅ Set CSP_ENABLED=false in .env"
else
    # Add CSP_ENABLED if not present
    echo "" >> .env
    echo "# Development CSP Settings" >> .env
    echo "CSP_ENABLED=false" >> .env
    echo "✅ Added CSP_ENABLED=false to .env"
fi

# Ensure APP_ENV is set to local for development
if grep -q "APP_ENV=local" .env; then
    echo "✅ APP_ENV is already set to local"
else
    if grep -q "APP_ENV=" .env; then
        sed -i 's/APP_ENV=.*/APP_ENV=local/' .env
        echo "✅ Changed APP_ENV to local"
    else
        echo "APP_ENV=local" >> .env
        echo "✅ Added APP_ENV=local to .env"
    fi
fi

echo ""
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "🔍 Checking Vite development server..."
if pgrep -f "vite" > /dev/null; then
    echo "✅ Vite development server is running"
else
    echo "⚠️  Vite development server is not running"
    echo "   Start it with: npm run dev"
fi

echo ""
echo "📋 Updated environment settings:"
grep -E "(APP_ENV|CSP_ENABLED)" .env

echo ""
echo "✅ Development CSP fixes applied!"
echo ""
echo "🌐 Your application should now work without CSP errors."
echo "   If you still see issues, try refreshing your browser and clearing browser cache."
echo ""
echo "🔒 Security Note:"
echo "   CSP is now disabled for development. Remember to enable it in production:"
echo "   CSP_ENABLED=true"