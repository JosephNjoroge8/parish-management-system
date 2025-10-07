#!/bin/bash

# ============================================================================
# PARISH MANAGEMENT SYSTEM - PRODUCTION DEPLOYMENT SCRIPT
# ============================================================================
# This script prepares the system for production deployment via cPanel
# Run this script before pushing to GitHub for production deployment
# ============================================================================

set -e  # Exit on any error

echo "🚀 Parish Management System - Production Deployment Preparation"
echo "============================================================================"

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: This script must be run from the Laravel project root directory"
    exit 1
fi

# ============================================================================
# STEP 1: ENVIRONMENT VALIDATION
# ============================================================================
echo "📋 Step 1: Environment Validation"
echo "----------------------------------------------------------------------------"

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✅ PHP Version: $PHP_VERSION"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi
echo "✅ Composer: $(composer --version --no-ansi | head -n 1)"

# Check Node.js (optional)
if command -v node &> /dev/null; then
    echo "✅ Node.js: $(node --version)"
    echo "✅ NPM: $(npm --version)"
else
    echo "⚠️  Node.js not found (optional for frontend build)"
fi

# ============================================================================
# STEP 2: DEPENDENCY OPTIMIZATION
# ============================================================================
echo ""
echo "📦 Step 2: Optimizing Dependencies"
echo "----------------------------------------------------------------------------"

# Update Composer dependencies for production
echo "🔄 Installing/updating Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Optimize autoloader
echo "🔄 Optimizing Composer autoloader..."
composer dump-autoload --optimize --classmap-authoritative

# Frontend dependencies (if package.json exists)
if [ -f "package.json" ]; then
    echo "🔄 Installing frontend dependencies..."
    npm ci --production --silent
    
    echo "🔄 Building frontend assets..."
    npm run build
else
    echo "⚠️  No package.json found, skipping frontend build"
fi

# ============================================================================
# STEP 3: LARAVEL OPTIMIZATION
# ============================================================================
echo ""
echo "⚡ Step 3: Laravel Performance Optimization"
echo "----------------------------------------------------------------------------"

# Clear all caches
echo "🧹 Clearing all caches..."
php artisan optimize:clear

# Cache configurations for production
echo "🔄 Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize Laravel
echo "🔄 Optimizing Laravel..."
php artisan optimize

# ============================================================================
# STEP 4: DATABASE PREPARATION
# ============================================================================
echo ""
echo "🗄️  Step 4: Database Preparation"
echo "----------------------------------------------------------------------------"

# Run database migrations (in SQLite for testing)
echo "🔄 Running database migrations..."
php artisan migrate --force --no-interaction

# Create test admin user for verification
echo "🔄 Creating test admin user..."
php artisan admin:create-super-user --email="admin@parishsystem.com" --name="System Administrator" --password="Admin123!" --force

# ============================================================================
# STEP 5: SECURITY & FILE PERMISSIONS
# ============================================================================
echo ""
echo "🔒 Step 5: Security Optimization"
echo "----------------------------------------------------------------------------"

# Set proper permissions
echo "🔄 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs

# Create necessary directories
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views
mkdir -p storage/app/public storage/logs

# ============================================================================
# STEP 6: TESTING & VALIDATION
# ============================================================================
echo ""
echo "🧪 Step 6: System Testing & Validation"
echo "----------------------------------------------------------------------------"

# Run tests to ensure everything works
echo "🔄 Running system tests..."
php artisan test --compact

# Check system status
echo "🔄 Checking system status..."
php artisan about --only=environment,cache,config 2>/dev/null || echo "Laravel about command not available"

# ============================================================================
# STEP 7: PRODUCTION FILES CREATION
# ============================================================================
echo ""
echo "📄 Step 7: Creating Production Files"
echo "----------------------------------------------------------------------------"

# Create post-deployment script for production server
cat > post-deployment.sh << 'EOF'
#!/bin/bash
# ============================================================================
# POST-DEPLOYMENT SCRIPT FOR PRODUCTION SERVER
# Run this script on the production server after cPanel deployment
# ============================================================================

echo "🚀 Running post-deployment optimization..."

# Navigate to deployment directory
cd /home2/shemidig/parish_system/

# Create super admin user
echo "👤 Creating super admin user..."
php artisan admin:create-super-user --email="admin@parishsystem.com" --name="Parish Administrator" --force

# Final optimization
echo "⚡ Final optimization..."
php artisan optimize

# Display system information
echo "📊 System Information:"
php artisan about --only=environment,cache,config 2>/dev/null || echo "System ready"

echo "✅ Post-deployment setup complete!"
echo "🔗 Visit: http://parish.quovadisyouthhub.org"
echo "👤 Admin Email: admin@parishsystem.com"
echo "🔑 Check deployment logs for admin password"
EOF

chmod +x post-deployment.sh

# Create README for production deployment
cat > PRODUCTION_DEPLOYMENT.md << 'EOF'
# Parish Management System - Production Deployment Guide

## 🚀 Automated cPanel Deployment

This system is configured for automatic deployment through cPanel Git integration.

### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Git access to the repository

### Deployment Steps

1. **Connect Repository in cPanel**
   - Go to cPanel → Git Version Control
   - Clone this repository to: `/home2/shemidig/parish_system/`

2. **Configure Environment**
   - Copy `.env.example` to `.env`
   - Update database credentials in `.env`
   - Set `APP_ENV=production`

3. **Deploy**
   - Push code changes to the main branch
   - cPanel will automatically run the deployment script

### Post-Deployment

The system automatically creates a super admin user during deployment.
Check the deployment logs for login credentials.

### System URLs
- **Application**: http://parish.quovadisyouthhub.org
- **Admin Login**: http://parish.quovadisyouthhub.org/login

### Support
For technical support, refer to the application logs in the cPanel File Manager.
EOF

# ============================================================================
# STEP 8: FINALIZATION
# ============================================================================
echo ""
echo "🎯 Step 8: Deployment Preparation Complete"
echo "----------------------------------------------------------------------------"

echo "✅ All optimization steps completed successfully!"
echo ""
echo "📋 DEPLOYMENT SUMMARY:"
echo "   • Dependencies optimized for production"
echo "   • Laravel caches generated"
echo "   • Database migrations ready"
echo "   • Security permissions set"
echo "   • Tests passing: $(php artisan test --compact | grep -c "✓")"
echo "   • Admin user created for testing"
echo ""
echo "🚀 NEXT STEPS:"
echo "   1. Review and commit all changes to Git"
echo "   2. Push to production repository"
echo "   3. cPanel will automatically deploy using .cpanel.yml"
echo "   4. Run 'bash post-deployment.sh' on production server if needed"
echo ""
echo "🔗 Production URL: http://parish.quovadisyouthhub.org"
echo "👤 Test Admin: admin@parishsystem.com"
echo "🔑 Test Password: Admin123!"
echo ""
echo "⚠️  Remember to change admin credentials in production!"
echo "============================================================================"