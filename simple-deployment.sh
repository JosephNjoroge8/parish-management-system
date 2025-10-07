#!/bin/bash

# =====================================================================================
# Parish Management System - Simple Deployment Script
# =====================================================================================
# 
# This is a simplified deployment script for shared hosting environments
# where you may not have full control over system packages.
#
# Requirements from hosting provider:
# - PHP 8.2+ with standard extensions (PDO, MySQL, mbstring, etc.)
# - MySQL database access
# - File upload/FTP access
# 
# Usage:
#   chmod +x simple-deployment.sh
#   ./simple-deployment.sh
#
# =====================================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="simple-deploy-${TIMESTAMP}.log"

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS:${NC} $1" | tee -a "$LOG_FILE"
}

echo -e "${CYAN}"
echo "════════════════════════════════════════════════════════════════════"
echo "                  PARISH MANAGEMENT SYSTEM"
echo "                   Simple Deployment Script"
echo "                  (Shared Hosting Friendly)"
echo "════════════════════════════════════════════════════════════════════"
echo -e "${NC}"

echo -e "${BLUE}This script is designed for shared hosting environments.${NC}"
echo -e "${YELLOW}If you have missing PHP extensions, contact your hosting provider.${NC}"
echo ""

# Basic PHP check
if ! command -v php >/dev/null 2>&1; then
    error "PHP is not available. Contact your hosting provider."
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
log "PHP version: $PHP_VERSION"

if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0);"; then
    error "PHP 8.2+ required. Current: $PHP_VERSION. Contact your hosting provider to upgrade."
fi

# Check for essential extensions
ESSENTIAL_EXTENSIONS=("pdo" "json" "mbstring")
MISSING_EXTENSIONS=()

for ext in "${ESSENTIAL_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
    error "Missing essential PHP extensions: ${MISSING_EXTENSIONS[*]}. Contact your hosting provider."
fi

log "Essential PHP extensions are available"

# Environment setup
log "Setting up environment..."

if [ ! -f .env ]; then
    if [ -f .env.production ]; then
        cp .env.production .env
        log "Copied .env.production to .env"
    elif [ -f .env.example ]; then
        cp .env.example .env
        log "Copied .env.example to .env"
    else
        error "No .env template found"
    fi
fi

# Generate app key if needed
if ! grep -q "APP_KEY=base64:" .env; then
    log "Generating application key..."
    php artisan key:generate --force || error "Failed to generate app key"
fi

# Database configuration
echo -e "${YELLOW}Database Configuration:${NC}"
read -p "Database host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Database name: " DB_DATABASE
[ -z "$DB_DATABASE" ] && error "Database name required"

read -p "Database username: " DB_USERNAME
[ -z "$DB_USERNAME" ] && error "Database username required"

read -s -p "Database password: " DB_PASSWORD
echo

# Update .env
sed -i "s/^APP_ENV=.*/APP_ENV=production/" .env
sed -i "s/^APP_DEBUG=.*/APP_DEBUG=false/" .env
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=$DB_HOST/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env

log "Environment configured"

# Install dependencies
log "Installing PHP dependencies..."

if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
elif [ -f composer.phar ]; then
    php composer.phar install --no-dev --optimize-autoloader --no-interaction
else
    warning "Composer not found. You may need to upload vendor/ folder manually."
fi

# Database setup
log "Setting up database..."

if ! php artisan migrate:status >/dev/null 2>&1; then
    error "Database connection failed. Check your credentials."
fi

php artisan migrate:fresh --force || error "Migration failed"
php artisan db:seed --force || warning "Database seeding failed (non-critical)"

log "Database setup completed"

# Check for frontend assets
if [ ! -d "public/build" ] || [ -z "$(ls -A public/build 2>/dev/null)" ]; then
    warning "No frontend assets found in public/build"
    echo -e "${YELLOW}To build frontend assets:${NC}"
    echo -e "${CYAN}1. Run locally: npm install && npm run build${NC}"
    echo -e "${CYAN}2. Upload the public/build folder to your server${NC}"
    echo ""
    read -p "Continue without frontend assets? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "Frontend assets are required"
    fi
else
    log "Frontend assets found"
fi

# Optimization
log "Optimizing application..."

php artisan config:cache || warning "Config cache failed"
php artisan route:cache || warning "Route cache failed" 
php artisan view:cache || warning "View cache failed"

# Set basic permissions
chmod -R 755 . 2>/dev/null || warning "Could not set directory permissions"
chmod -R 775 storage bootstrap/cache 2>/dev/null || warning "Could not set storage permissions"
chmod 600 .env 2>/dev/null || warning "Could not secure .env file"

# Create admin user
log "Creating admin user..."
php artisan tinker --execute="
if (\\App\\Models\\User::where('email', 'admin@parish.com')->doesntExist()) {
    \\App\\Models\\User::create([
        'name' => 'Parish Administrator',
        'email' => 'admin@parish.com',
        'password' => bcrypt('admin123'),
        'is_admin' => true,
        'is_active' => true,
        'email_verified_at' => now()
    ]);
    echo 'Admin user created\n';
}
" || warning "Admin user creation failed"

# Final verification
log "Running verification..."
php artisan --version >/dev/null || error "Application cannot boot"

success "Deployment completed!"

echo -e "${CYAN}"
echo "════════════════════════════════════════════════════════════════════"
echo "                     DEPLOYMENT COMPLETED!"
echo "════════════════════════════════════════════════════════════════════"
echo -e "${NC}"

echo -e "${GREEN}✅ Parish Management System deployed successfully!${NC}"
echo ""
echo -e "${YELLOW}IMPORTANT NEXT STEPS:${NC}"
echo "1. Point your domain to the 'public' folder"
echo "2. Set up SSL certificate (recommended)"
echo "3. Change admin password after first login"
echo ""
echo -e "${GREEN}ACCESS INFORMATION:${NC}"
echo "👤 Admin Email: admin@parish.com"
echo "🔑 Admin Password: admin123"
echo "⚠️  Change password immediately after login!"
echo ""
echo -e "${BLUE}If you need frontend assets:${NC}"
echo "• Run locally: npm install && npm run build"
echo "• Upload public/build/ folder to your server"
echo ""
echo -e "${RED}SUPPORT:${NC}"
echo "📧 GitHub: https://github.com/JosephNjoroge8/parish-management-system"
echo ""

log "Simple deployment completed. Log: $LOG_FILE"