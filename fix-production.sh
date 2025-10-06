#!/bin/bash

# ============================================================================
# Parish Management System - Production Emergency Fix
# ============================================================================
# This script fixes production issues caused by the previous deployment
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

print_status "🔧 Starting Parish Management System Production Emergency Fix..."

# Step 1: Emergency cache clearing
print_status "Step 1: Clearing all caches (emergency procedure)..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
print_success "✅ All caches cleared"

# Step 2: Check current environment issues
print_status "Step 2: Checking current environment configuration..."
echo "Current problematic settings:"
grep -E "APP_ENV|APP_URL|APP_DEBUG" .env || echo "No .env file found!"

# Step 3: Backup current .env
print_status "Step 3: Backing up current .env file..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
print_success "✅ .env backed up"

# Step 4: Fix critical environment variables
print_status "Step 4: Fixing critical environment variables..."

# Check if we're in production (basic check)
if [[ $(pwd) =~ "parish_system" ]]; then
    print_status "Detected production environment, updating settings..."
    
    # Fix APP_ENV
    if grep -q "APP_ENV=local" .env; then
        sed -i 's/APP_ENV=local/APP_ENV=production/' .env
        print_success "✅ Fixed APP_ENV to production"
    fi
    
    # Fix APP_DEBUG
    if grep -q "APP_DEBUG=true" .env; then
        sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
        print_success "✅ Fixed APP_DEBUG to false"
    fi
    
    # Show current APP_URL (user needs to fix this manually)
    current_url=$(grep "APP_URL=" .env | cut -d'=' -f2)
    if [[ "$current_url" == *"localhost"* ]]; then
        print_warning "⚠️  APP_URL is still set to localhost: $current_url"
        print_warning "You need to update this to your actual domain"
        echo "Current: $current_url"
        echo "Should be something like: https://your-domain.com"
        print_status "Please update APP_URL manually after this script completes"
    fi
else
    print_warning "Not in production directory, skipping environment fixes"
fi

# Step 5: Test basic functionality WITHOUT caching
print_status "Step 5: Testing basic functionality (without caching)..."

# Test database connection
print_status "Testing database connection..."
php artisan tinker --execute="
try {
    \$count = \App\Models\Member::count();
    echo '✅ Database connection works. Members count: ' . \$count . PHP_EOL;
} catch (\Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage() . PHP_EOL;
}
" 2>/dev/null || print_error "Database test failed"

# Test routes
print_status "Testing routes..."
route_count=$(php artisan route:list 2>/dev/null | wc -l)
if [ "$route_count" -gt 5 ]; then
    print_success "✅ Routes loaded successfully ($route_count routes)"
else
    print_error "❌ Routes not loading properly"
fi

# Step 6: Check for Inertia/Frontend issues
print_status "Step 6: Checking for frontend issues..."

# Check if public/build exists (Vite build)
if [ -d "public/build" ]; then
    print_success "✅ Frontend build files exist"
else
    print_warning "⚠️  Frontend build files missing - you may need to run 'npm run build'"
fi

# Check if storage link exists
if [ -L "public/storage" ]; then
    print_success "✅ Storage link exists"
else
    print_warning "⚠️  Storage link missing - creating it..."
    php artisan storage:link
fi

# Step 7: Set appropriate permissions for shared hosting
print_status "Step 7: Setting appropriate permissions..."
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chmod 644 .env
print_success "✅ Permissions set for shared hosting"

# Step 8: Show current status
print_status "Step 8: Current system status..."
echo "=== System Status ==="
echo "PHP Version: $(php -v | head -n 1)"
echo "Laravel Version: $(php artisan --version 2>/dev/null || echo 'ERROR')"
echo "Environment: $(grep APP_ENV .env | cut -d'=' -f2)"
echo "Debug Mode: $(grep APP_DEBUG .env | cut -d'=' -f2)"
echo "App URL: $(grep APP_URL .env | cut -d'=' -f2)"

# Step 9: Recommendations
print_status "Step 9: Next steps and recommendations..."
echo ""
print_success "🎉 Emergency fixes completed!"
echo ""
print_warning "⚠️  IMPORTANT: You still need to:"
echo "1. Update APP_URL in .env to your actual domain"
echo "2. Test the application in a web browser"
echo "3. If frontend doesn't work, run 'npm run build'"
echo "4. Monitor logs: tail -f storage/logs/laravel.log"
echo ""

print_status "🧪 Test these URLs in your browser:"
current_url=$(grep APP_URL .env | cut -d'=' -f2)
echo "- Main app: $current_url"
echo "- Members: $current_url/members"
echo "- Login: $current_url/login"
echo ""

print_warning "🚨 DO NOT run config:cache or route:cache until you confirm the app works!"
echo ""
print_status "If everything works, then you can optimize with:"
echo "  php artisan config:cache"
echo "  php artisan view:cache"
echo "  # Skip route:cache for now if using Inertia"

print_success "✨ Production emergency fix completed!"