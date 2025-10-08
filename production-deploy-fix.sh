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

# Fix .htaccess for PHP processing
echo "� Fixing .htaccess for PHP processing..."
cat > public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    # CRITICAL: Force PHP processing
    AddHandler application/x-httpd-php .php
    AddType application/x-httpd-php .php

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# MIME types for assets
<IfModule mod_mime.c>
    AddType application/javascript .js
    AddType text/css .css
    AddType image/svg+xml .svg
    AddType application/json .json
    AddType font/woff .woff
    AddType font/woff2 .woff2
</IfModule>
EOF

# Create PHP test file
cat > public/php-test.php << 'EOF'
<?php
echo "SUCCESS: PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "Current time: " . date('Y-m-d H:i:s');
?>
EOF

# Check PHP execution
echo "🔍 Testing PHP execution..."
if php -v > /dev/null 2>&1; then
    echo "✅ PHP CLI is working"
    echo "✅ PHP test file created: https://parish.quovadisyouthhub.org/php-test.php"
    
    # Test if web server processes PHP
    if [ -f "public/diagnostics.php" ]; then
        echo "✅ Diagnostics script available at: https://parish.quovadisyouthhub.org/diagnostics.php"
    fi
    
    echo "✅ .htaccess file updated with PHP handlers"
else
    echo "❌ PHP CLI not working"
fi

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
    asset_count=$(ls -1 public/build/assets/ | wc -l)
    echo "   📁 Total asset files: $asset_count"
    echo "   📄 CSS files: $(ls -1 public/build/assets/*.css 2>/dev/null | wc -l)"
    echo "   📄 JS files: $(ls -1 public/build/assets/*.js 2>/dev/null | wc -l)"
    echo "   📋 Manifest size: $(ls -lh public/build/manifest.json | awk '{print $5}')"
    
    if [ "$asset_count" -lt 10 ]; then
        echo "⚠️  WARNING: Only $asset_count asset files found. Expected 80+ files."
        echo "   This suggests the build assets weren't uploaded completely."
        echo "   Please check if the entire public/build/ directory was uploaded."
    else
        echo "✅ Asset count looks good ($asset_count files)"
    fi
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
echo "1. ✅ Test PHP processing: https://parish.quovadisyouthhub.org/php-test.php"
echo "2. ✅ Run diagnostics: https://parish.quovadisyouthhub.org/diagnostics.php"
echo "3. ✅ Verify main site: https://parish.quovadisyouthhub.org"
echo "4. ✅ Check browser console for JavaScript errors"
echo "5. ✅ Test login functionality"
echo "6. ✅ Verify assets are loading (no 404 errors)"
echo ""
echo "🚨 If PHP code shows as text instead of executing:"
echo "   1. Contact your hosting provider"
echo "   2. Ask them to enable PHP processing for .php files"
echo "   3. Ask them to enable mod_rewrite Apache module"
echo "   4. Ask them to allow .htaccess overrides"
echo ""
echo "📞 Hosting provider checklist:"
echo "   - 'Please enable PHP processing for .php files'"
echo "   - 'Please enable Apache mod_rewrite module'"
echo "   - 'Please allow .htaccess files to override server settings'"
echo "   - 'Our .htaccess needs AddHandler and RewriteEngine directives'"
echo ""
echo "📝 Technical details for hosting support:"
echo "   - Framework: Laravel (requires PHP and URL rewriting)"
echo "   - .htaccess location: public/.htaccess"
echo "   - Document root should point to: public/ directory"
echo "   - PHP version: $(php --version | head -n1)"
echo ""
echo "� Manual fixes if automated deployment fails:"
echo "   - Upload fresh public/build/ directory from local machine"
echo "   - Verify .htaccess file exists in public/ directory"
echo "   - Check file permissions: 644 for files, 755 for directories"
echo "   - Contact hosting provider if PHP code shows as plain text"