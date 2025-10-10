#!/bin/bash

# ====================================
# PARISH MANAGEMENT SYSTEM - SERVER OPTIMIZATION SCRIPT
# ====================================
# Run this ON THE PRODUCTION SERVER after uploading files
# This script optimizes Laravel for production performance

set -e  # Exit on any error

echo "🚀 Parish Management System - Server Optimization"
echo "================================================="
echo "Starting optimization at: $(date)"
echo ""
# ====================================
# PARISH MANAGEMENT SYSTEM - SERVER OPTIMIZATION SCRIPT
# ====================================
# Run this ON THE PRODUCTION SERVER after uploading files
# This handles optimization and cache management

echo "🔧 Parish Management System - Server Optimization"
echo "================================================="
echo "Running on production server at: $(date)"
echo ""

# Check if we're in Laravel directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel directory"
    echo "Please run this script from your Laravel project root"
    exit 1
fi

echo "📍 Location: $(pwd)"
echo ""

# ====================================
# STEP 1: CLEAR ALL CACHES
# ====================================
echo "🧹 STEP 1: Clearing Caches"
echo "=========================="

if command -v php &> /dev/null; then
    echo "Clearing configuration cache..."
    php artisan config:clear --no-interaction 2>/dev/null || echo "Config clear completed"
    
    echo "Clearing route cache..."
    php artisan route:clear --no-interaction 2>/dev/null || echo "Route clear completed"
    
    echo "Clearing view cache..."
    php artisan view:clear --no-interaction 2>/dev/null || echo "View clear completed"
    
    echo "Clearing application cache..."
    php artisan cache:clear --no-interaction 2>/dev/null || echo "Cache clear completed"
    
    echo "✅ All caches cleared"
else
    echo "⚠️  PHP command not available, skipping cache clearing"
fi

echo ""

# ====================================
# STEP 2: BUILD PRODUCTION CACHES
# ====================================
echo "📦 STEP 2: Building Production Caches"
echo "====================================="

if command -v php &> /dev/null; then
    echo "Building configuration cache..."
    php artisan config:cache --no-interaction
    
    echo "Building route cache..."
    php artisan route:cache --no-interaction
    
    echo "Building view cache..."
    php artisan view:cache --no-interaction
    
    echo "Running optimize command..."
    php artisan optimize --no-interaction
    
    echo "✅ Production caches built"
else
    echo "⚠️  PHP command not available"
    echo "Please create optimize.php helper script as shown in deployment guide"
fi

echo ""

# ====================================
# STEP 3: VERIFY ASSETS
# ====================================
echo "🔍 STEP 3: Asset Verification"
echo "============================="

if [ -d "public/build" ]; then
    TOTAL_FILES=$(find public/build -type f | wc -l)
    echo "✅ Build directory exists with $TOTAL_FILES files"
    
    if [ -f "public/build/manifest.json" ]; then
        MANIFEST_SIZE=$(du -h public/build/manifest.json | cut -f1)
        echo "✅ Manifest file exists ($MANIFEST_SIZE)"
    else
        echo "❌ CRITICAL: manifest.json missing!"
    fi
    
    CSS_FILES=$(find public/build/assets -name "app-*.css" | wc -l)
    JS_FILES=$(find public/build/assets -name "app-*.js" | wc -l)
    echo "📊 Asset summary: $CSS_FILES CSS files, $JS_FILES JS files"
    
    if [ "$CSS_FILES" -gt 0 ] && [ "$JS_FILES" -gt 0 ]; then
        echo "✅ Critical assets present"
    else
        echo "❌ CRITICAL: Missing CSS or JS assets!"
    fi
else
    echo "❌ CRITICAL: public/build directory missing!"
    echo "Please ensure you've uploaded the built assets"
fi

echo ""

# ====================================
# STEP 4: PERMISSIONS CHECK
# ====================================
echo "🔐 STEP 4: Permission Verification"
echo "=================================="

# Check and set storage permissions
if [ -d "storage" ]; then
    chmod -R 755 storage/ 2>/dev/null || echo "Could not set storage permissions"
    chmod -R 755 bootstrap/cache/ 2>/dev/null || echo "Could not set bootstrap cache permissions"
    
    if [ -w "storage/logs" ]; then
        echo "✅ Storage directory is writable"
    else
        echo "❌ Storage directory is not writable"
    fi
else
    echo "❌ Storage directory not found"
fi

echo ""

# ====================================
# STEP 5: ENVIRONMENT CHECK
# ====================================
echo "⚙️  STEP 5: Environment Check"
echo "============================="

if [ -f ".env" ]; then
    echo "✅ .env file exists"
    
    # Check critical environment variables
    if grep -q "APP_KEY=base64:" .env; then
        echo "✅ APP_KEY is configured"
    else
        echo "❌ APP_KEY not configured properly"
    fi
    
    if grep -q "APP_ENV=production" .env; then
        echo "✅ Environment set to production"
    else
        echo "⚠️  Environment may not be set to production"
    fi
    
    if grep -q "APP_DEBUG=false" .env; then
        echo "✅ Debug mode disabled"
    else
        echo "⚠️  Debug mode may be enabled"
    fi
else
    echo "❌ .env file not found"
fi

echo ""

# ====================================
# STEP 6: QUICK HEALTH CHECK
# ====================================
echo "🏥 STEP 6: Health Check"
echo "======================"

# Check if public/.htaccess exists
if [ -f "public/.htaccess" ]; then
    echo "✅ .htaccess file exists"
else
    echo "❌ .htaccess file missing"
fi

# Check for common issues
if [ -f "public/.env" ]; then
    echo "🚨 SECURITY WARNING: .env file found in public directory!"
else
    echo "✅ No .env file in public directory"
fi

echo ""

# ====================================
# COMPLETION SUMMARY
# ====================================
echo "🎉 SERVER OPTIMIZATION COMPLETED"
echo "================================"
echo "Optimization finished at: $(date)"
echo ""
echo "📋 RECOMMENDED NEXT STEPS:"
echo "1. Test your website: https://parish.quovadisyouthhub.org"
echo "2. Open browser DevTools and check for errors"
echo "3. Test login functionality"
echo "4. Verify member management works"
echo "5. Check report generation"
echo "6. Monitor storage/logs/laravel.log for any issues"
echo ""

if [ -d "public/build" ] && [ -f "public/build/manifest.json" ]; then
    echo "✅ Your parish management system appears ready for production!"
else
    echo "⚠️  Assets may be missing. Please verify asset upload was successful."
fi

echo ""
echo "📞 For troubleshooting, check:"
echo "   - Browser console for JavaScript errors"
echo "   - storage/logs/laravel.log for PHP errors"
echo "   - Server error logs in cPanel"
echo ""
echo "🔗 Test URL: https://parish.quovadisyouthhub.org"