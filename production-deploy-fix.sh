#!/bin/bash

# Production Deployment Script for Parish Management System
# This script should be run on the PRODUCTION SERVER after assets are built locally

echo "🚀 Parish Management System - Production Deployment"
echo "=================================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

# Set proper permissions first
echo "📂 Setting proper permissions..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Set ownership for web server directories
echo "👤 Setting ownership for web server..."
if [ -d "storage" ]; then
    chmod -R 775 storage
    if command -v chown &> /dev/null; then
        sudo chown -R $USER:www-data storage 2>/dev/null || echo "Note: Could not change ownership of storage (this may be normal)"
    fi
fi

if [ -d "bootstrap/cache" ]; then
    chmod -R 775 bootstrap/cache
    if command -v chown &> /dev/null; then
        sudo chown -R $USER:www-data bootstrap/cache 2>/dev/null || echo "Note: Could not change ownership of bootstrap/cache (this may be normal)"
    fi
fi

if [ -d "public/build" ]; then
    chmod -R 755 public/build
fi

# Clear all caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear 2>/dev/null || echo "Config clear failed (may be normal)"
php artisan cache:clear 2>/dev/null || echo "Cache clear failed (may be normal)"
php artisan route:clear 2>/dev/null || echo "Route clear failed (may be normal)"
php artisan view:clear 2>/dev/null || echo "View clear failed (may be normal)"
php artisan event:clear 2>/dev/null || echo "Event clear failed (may be normal)"

# Install/update composer dependencies for production
echo "📦 Installing/updating Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
    echo "✅ Composer dependencies updated"
else
    echo "⚠️  Composer not found. Please install dependencies manually."
fi

# Check if build assets exist (they should be deployed from local build)
echo "🔍 Checking build assets..."
if [ ! -d "public/build" ] || [ ! -f "public/build/manifest.json" ]; then
    echo "❌ Build assets not found!"
    echo "Please run these commands LOCALLY and then upload the files:"
    echo "   npm install"
    echo "   npm run build"
    echo "   Upload the entire public/build/ directory to production"
    echo ""
    echo "Or use the build-and-deploy.sh script locally"
    exit 1
else
    echo "✅ Build assets found"
    echo "📊 Asset summary:"
    ls -la public/build/assets/ | head -10
fi

# Create storage link if it doesn't exist
echo "🔗 Creating storage link..."
if [ -L "public/storage" ]; then
    echo "✅ Storage link already exists"
elif [ -d "public/storage" ]; then
    echo "⚠️  public/storage exists as directory, removing..."
    rm -rf public/storage
    php artisan storage:link 2>/dev/null && echo "✅ Storage link created" || echo "❌ Failed to create storage link"
else
    php artisan storage:link 2>/dev/null && echo "✅ Storage link created" || echo "❌ Failed to create storage link"
fi

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache && echo "✅ Config cached" || echo "❌ Config cache failed"
php artisan route:cache && echo "✅ Routes cached" || echo "❌ Route cache failed"
php artisan view:cache && echo "✅ Views cached" || echo "❌ View cache failed"

# Run database migrations if needed
echo "🗄️  Checking database..."
if php artisan migrate:status &> /dev/null; then
    echo "✅ Database connection successful"
    read -p "Do you want to run database migrations? [y/N]: " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan migrate --force && echo "✅ Migrations completed" || echo "❌ Migration failed"
    fi
else
    echo "⚠️  Cannot connect to database. Check your .env configuration."
fi

echo ""
echo "✅ Production deployment completed!"
echo ""
echo "🔍 Post-deployment checklist:"
echo "1. ✅ Verify website loads: https://parish.quovadisyouthhub.org"
echo "2. ✅ Check browser console for JavaScript errors"
echo "3. ✅ Test login functionality"
echo "4. ✅ Verify assets are loading (no 404 errors)"
echo "5. ✅ Check web server error logs if issues persist"
echo ""
echo "📝 If assets still don't load:"
echo "   - Check .htaccess is being processed"
echo "   - Verify public/build/ directory exists and has proper permissions"
echo "   - Check web server MIME type configuration"
echo "   - Review web server error logs"