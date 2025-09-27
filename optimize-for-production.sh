#!/bin/bash

# =============================================================================
# PARISH MANAGEMENT SYSTEM - QUICK OPTIMIZATION SCRIPT
# =============================================================================
# Run this script to quickly optimize the system for production
# This is a lighter version that can be run on existing deployments
# =============================================================================

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 Parish Management System - Production Optimization${NC}"
echo "============================================================="

# Get the project directory (current directory by default)
PROJECT_DIR=${1:-$(pwd)}

if [ ! -f "$PROJECT_DIR/artisan" ]; then
    echo -e "${YELLOW}⚠️  Error: Not a Laravel project directory${NC}"
    echo "Usage: $0 [project_directory]"
    exit 1
fi

cd "$PROJECT_DIR"

echo -e "${GREEN}📁 Working in: $PROJECT_DIR${NC}"

# =============================================================================
# 1. ENVIRONMENT CHECK
# =============================================================================
echo -e "\n${BLUE}🔍 Step 1: Environment Check${NC}"

if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  .env file not found, copying from .env.example${NC}"
    cp .env.example .env
fi

# Check if APP_KEY is set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

echo "✅ Environment configuration checked"

# =============================================================================
# 2. DEPENDENCIES & ASSETS
# =============================================================================
echo -e "\n${BLUE}📦 Step 2: Dependencies & Assets${NC}"

# Install/update composer dependencies
echo "🎵 Installing/updating Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-scripts

# Install/update npm dependencies and build assets
if [ -f "package.json" ]; then
    echo "📦 Installing npm dependencies..."
    npm ci --production=false
    
    echo "🏗️  Building production assets..."
    npm run build
    
    echo "🧹 Cleaning development dependencies..."
    npm prune --production
fi

echo "✅ Dependencies and assets optimized"

# =============================================================================
# 3. DATABASE OPTIMIZATION
# =============================================================================
echo -e "\n${BLUE}🗄️  Step 3: Database Optimization${NC}"

# Run migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Optimize database (SQLite specific)
if grep -q "DB_CONNECTION=sqlite" .env; then
    DB_PATH=$(grep "DB_DATABASE=" .env | cut -d'=' -f2)
    if [ -f "$DB_PATH" ]; then
        echo "🗜️  Optimizing SQLite database..."
        sqlite3 "$DB_PATH" "VACUUM;"
        sqlite3 "$DB_PATH" "PRAGMA optimize;"
    fi
fi

echo "✅ Database optimized"

# =============================================================================
# 4. APPLICATION OPTIMIZATION
# =============================================================================
echo -e "\n${BLUE}⚡ Step 4: Application Optimization${NC}"

# Clear all caches first
echo "🧹 Clearing existing caches..."
php artisan optimize:clear

# Generate optimized configurations
echo "⚙️  Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Generate application optimization
echo "🏎️  Optimizing application..."
php artisan optimize

echo "✅ Application optimized"

# =============================================================================
# 5. FILE PERMISSIONS & SECURITY
# =============================================================================
echo -e "\n${BLUE}🔒 Step 5: Permissions & Security${NC}"

# Set proper permissions
echo "🔐 Setting secure file permissions..."

# General file permissions
find . -type f -not -path "./storage/*" -not -path "./bootstrap/cache/*" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Storage and cache directories
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Environment file
chmod 600 .env

# Database file (SQLite)
if grep -q "DB_CONNECTION=sqlite" .env; then
    DB_PATH=$(grep "DB_DATABASE=" .env | cut -d'=' -f2)
    if [ -f "$DB_PATH" ]; then
        chmod 600 "$DB_PATH"
    fi
fi

echo "✅ Permissions secured"

# =============================================================================
# 6. PERFORMANCE MONITORING
# =============================================================================
echo -e "\n${BLUE}📊 Step 6: Performance Check${NC}"

# Check PHP configuration
echo "🔍 Checking PHP configuration..."
php -m | grep -E "(opcache|redis|mysql)" || echo "⚠️  Some PHP extensions might be missing"

# Check disk space
DISK_USAGE=$(df . | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo -e "${YELLOW}⚠️  Disk usage is high: ${DISK_USAGE}%${NC}"
else
    echo "✅ Disk usage is acceptable: ${DISK_USAGE}%"
fi

# Check application responsiveness
if curl -f -s http://localhost/health > /dev/null 2>&1; then
    echo "✅ Application is responding"
else
    echo -e "${YELLOW}⚠️  Application health check failed${NC}"
fi

echo "✅ Performance check completed"

# =============================================================================
# 7. CLEANUP
# =============================================================================
echo -e "\n${BLUE}🧹 Step 7: Cleanup${NC}"

# Clean temporary files
echo "🗑️  Cleaning temporary files..."
find storage/logs -name "*.log" -mtime +30 -delete 2>/dev/null || true
find storage/framework/cache -name "*" -mtime +7 -type f -delete 2>/dev/null || true

# Clean old composer cache
composer clear-cache

echo "✅ Cleanup completed"

# =============================================================================
# OPTIMIZATION COMPLETE
# =============================================================================
echo -e "\n${GREEN}🎉 OPTIMIZATION COMPLETE!${NC}"
echo "============================================================="

echo -e "\n${BLUE}📋 Optimization Summary:${NC}"
echo "✅ Environment configuration checked"
echo "✅ Dependencies optimized"
echo "✅ Database optimized"
echo "✅ Application caches generated"
echo "✅ File permissions secured"
echo "✅ Performance checked"
echo "✅ Temporary files cleaned"

echo -e "\n${BLUE}🛠️  Recommended Next Steps:${NC}"
echo "1. Set up a reverse proxy (Nginx) with SSL"
echo "2. Configure automated backups"
echo "3. Set up monitoring and alerting"
echo "4. Configure log rotation"
echo "5. Test the application thoroughly"

echo -e "\n${BLUE}📁 Important Commands:${NC}"
echo "- View logs: tail -f storage/logs/laravel.log"
echo "- Clear cache: php artisan optimize:clear"
echo "- Run migrations: php artisan migrate"
echo "- Check health: curl http://localhost/health"

echo -e "\n${GREEN}✨ Your Parish Management System is now production-ready!${NC}"