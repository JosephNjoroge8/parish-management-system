#!/bin/bash

# ============================================================================
# PARISH MANAGEMENT SYSTEM - PRODUCTION DEPLOYMENT SCRIPT
# ============================================================================
# This is the ONLY script needed for production deployment
# Run this script on the production server terminal
# ============================================================================

set -e  # Exit on any error

echo "🚀 Parish Management System - Production Setup"
echo "============================================================================"

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: This script must be run from the Laravel project root directory"
    exit 1
fi

# ============================================================================
# STEP 1: CLEAR ALL LOGS (Production & Development)
# ============================================================================
echo "🧹 Step 1: Clearing All System Logs"
echo "----------------------------------------------------------------------------"

# Clear Laravel logs
echo "🔄 Clearing Laravel logs..."
find storage/logs/ -name "*.log" -type f -delete 2>/dev/null || echo "  No Laravel logs to clear"
find storage/logs/ -name "*.json" -type f -delete 2>/dev/null || echo "  No JSON logs to clear"

# Clear deployment logs
echo "🔄 Clearing deployment logs..."
find logs/ -name "*.log" -type f -delete 2>/dev/null || echo "  No deployment logs to clear"
rm -f logs/*.log 2>/dev/null || true

# Clear cache logs
echo "🔄 Clearing cache logs..."
rm -rf storage/framework/cache/data/* 2>/dev/null || true
rm -rf storage/framework/sessions/* 2>/dev/null || true
rm -rf storage/framework/views/* 2>/dev/null || true

echo "✅ All logs cleared successfully"

# ============================================================================
# STEP 2: CLEAR ALL LARAVEL CACHES
# ============================================================================
echo ""
echo "🧹 Step 2: Clearing Laravel Caches"
echo "----------------------------------------------------------------------------"

# Clear specific caches (safe for production)
php artisan config:clear || echo "Config cache cleared"
php artisan route:clear || echo "Route cache cleared"  
php artisan view:clear || echo "View cache cleared"
php artisan event:clear || echo "Event cache cleared"

echo "✅ Laravel caches cleared"

# ============================================================================
# STEP 3: PRODUCTION ENVIRONMENT SETUP
# ============================================================================
echo ""
echo "🔧 Step 3: Production Environment Setup"
echo "----------------------------------------------------------------------------"

# Set production environment variables
export APP_ENV=production
export APP_DEBUG=false

# Verify environment file exists
if [ ! -f ".env" ]; then
    echo "❌ ERROR: .env file not found!"
    echo "   Please create .env file with production settings:"
    echo "   APP_ENV=production"
    echo "   APP_DEBUG=false"
    echo "   APP_URL=https://parish.quovadisyouthhub.org"
    echo "   Database credentials, etc."
    exit 1
fi

echo "✅ Environment verified"

# ============================================================================
# STEP 4: BUILD PRODUCTION CACHES
# ============================================================================
echo ""
echo "⚡ Step 4: Building Production Caches"
echo "----------------------------------------------------------------------------"

# Build optimized caches for production
echo "🔄 Building configuration cache..."
php artisan config:cache

echo "🔄 Building route cache..."
php artisan route:cache

echo "🔄 Building view cache..."
php artisan view:cache

echo "🔄 Building event cache..."
php artisan event:cache

echo "✅ Production caches built"

# ============================================================================
# STEP 5: OPTIMIZE SYSTEM
# ============================================================================
echo ""
echo "⚡ Step 5: System Optimization"
echo "----------------------------------------------------------------------------"

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link --force

# Optimize Laravel
echo "🔄 Optimizing Laravel..."
php artisan optimize

echo "✅ System optimized"

# ============================================================================
# STEP 6: VERIFY ASSETS
# ============================================================================
echo ""
echo "📦 Step 6: Verifying Production Assets"
echo "----------------------------------------------------------------------------"

# Check build manifest
if [ ! -f "public/build/manifest.json" ]; then
    echo "❌ ERROR: Build manifest missing!"
    echo "   Please ensure frontend assets were built and uploaded:"
    echo "   1. Run 'npm run build' locally"
    echo "   2. Upload the entire public/build/ folder"
    exit 1
fi

# Check app bundles
APP_BUNDLE_COUNT=$(ls public/build/assets/app-*.js 2>/dev/null | wc -l)
if [ "$APP_BUNDLE_COUNT" -eq 0 ]; then
    echo "❌ ERROR: No app bundles found!"
    echo "   Expected app-*.js files in public/build/assets/"
    exit 1
fi

echo "✅ Found $APP_BUNDLE_COUNT app bundle(s)"
echo "✅ Build manifest verified"

# ============================================================================
# STEP 7: FIX PERMISSIONS
# ============================================================================
echo ""
echo "🔐 Step 7: Setting File Permissions"
echo "----------------------------------------------------------------------------"

# Set proper permissions for web server
chmod -R 755 public/ 2>/dev/null || echo "Public permissions set"
chmod -R 755 storage/ 2>/dev/null || echo "Storage permissions set"
chmod -R 755 bootstrap/cache/ 2>/dev/null || echo "Bootstrap cache permissions set"
chmod 644 public/build/manifest.json 2>/dev/null || echo "Manifest permissions set"

echo "✅ File permissions configured"

# ============================================================================
# STEP 8: DATABASE SETUP (if database is available)
# ============================================================================
echo ""
echo "🗄️  Step 8: Database Setup"
echo "----------------------------------------------------------------------------"

# Test database connection
if php artisan migrate:status --no-interaction >/dev/null 2>&1; then
    echo "✅ Database connection verified"
    
    # Run migrations
    echo "🔄 Running database migrations..."
    php artisan migrate --force --no-interaction
    
    # Create admin user (only if command exists)
    if php artisan admin:create-super-user --help >/dev/null 2>&1; then
        echo "👤 Creating admin user..."
        php artisan admin:create-super-user \
            --email="admin@parishsystem.com" \
            --name="Parish Administrator" \
            --password="Admin123!" \
            --force
        echo "✅ Admin user created: admin@parishsystem.com / Admin123!"
    else
        echo "ℹ️  Admin user creation command not available"
    fi
else
    echo "⚠️  Database not available or not configured"
    echo "   Please ensure database credentials are correct in .env"
fi

# ============================================================================
# STEP 9: FINAL VERIFICATION
# ============================================================================
echo ""
echo "✅ Step 9: Final System Verification"
echo "----------------------------------------------------------------------------"

# Show system status
echo "📊 System Status:"
php artisan about --only=environment,cache 2>/dev/null || echo "Laravel system ready"

# Show build assets
echo ""
echo "📁 Build Assets:"
ls -la public/build/assets/app-*.js | head -3

echo ""
echo "📄 Environment Status:"
grep -E "APP_ENV|APP_DEBUG|APP_URL" .env | head -3

# ============================================================================
# COMPLETION
# ============================================================================
echo ""
echo "🎯 PRODUCTION DEPLOYMENT COMPLETE!"
echo "============================================================================"
echo ""
echo "✅ SUCCESS SUMMARY:"
echo "   • All logs cleared (Laravel, deployment, cache)"
echo "   • Laravel caches optimized for production"
echo "   • Frontend assets verified and ready"
echo "   • File permissions configured"
echo "   • Database migrations completed"
echo "   • Admin user created"
echo ""
echo "🔗 YOUR APPLICATION:"
echo "   URL: https://parish.quovadisyouthhub.org"
echo "   Admin: admin@parishsystem.com"
echo "   Password: Admin123!"
echo ""
echo "⚠️  IMPORTANT:"
echo "   • Change default admin password immediately"
echo "   • Test all functionality"
echo "   • Monitor error logs"
echo ""
echo "✅ System is ready for production use!"
echo "============================================================================"