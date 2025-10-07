#!/bin/bash

# =====================================================================================
# Parish Management System - Production Deployment Script
# =====================================================================================
# 
# This script provides a complete production deployment solution for the
# Parish Management System built with Laravel 12 + Inertia.js + React
#
# Author: Parish Management Development Team
# Version: 1.0.0
# Date: October 6, 2025
# 
# Requirements:
# - PHP 8.4+ with required extensions
# - Node.js 18+ with npm
# - MySQL 8.0+ or PostgreSQL 13+
# - Web server (Apache/Nginx)
# - Composer 2.x
# - SSL certificate (recommended)
# 
# Usage:
#   chmod +x production-deployment.sh
#   ./production-deployment.sh
#
# =====================================================================================

set -e  # Exit on any error

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="parish-management-system"
PHP_VERSION="8.4"
NODE_VERSION="18"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="logs/deployment_${TIMESTAMP}.log"

# Global flags for system capabilities
NODE_AVAILABLE=true
COMPOSER_CMD="composer"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Ensure logs directory exists
mkdir -p logs

# Logging function
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

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO:${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS:${NC} $1" | tee -a "$LOG_FILE"
}

# Banner
echo -e "${CYAN}"
echo "════════════════════════════════════════════════════════════════════"
echo "                  PARISH MANAGEMENT SYSTEM"
echo "                  Production Deployment Script"
echo "                         Version 1.1.0"
echo "                    (Hosting-Provider Friendly)"
echo "════════════════════════════════════════════════════════════════════"
echo -e "${NC}"

echo -e "${BLUE}📋 HOSTING PROVIDER REQUIREMENTS:${NC}"
echo -e "${YELLOW}Your hosting provider should provide:${NC}"
echo -e "${CYAN}• PHP 8.2+ with extensions: PDO, MySQLi, mbstring, XML, JSON, etc.${NC}"
echo -e "${CYAN}• MySQL/PostgreSQL database access${NC}"
echo -e "${CYAN}• SSL certificate (recommended)${NC}"
echo -e "${CYAN}• File permissions for storage/bootstrap directories${NC}"
echo ""
echo -e "${YELLOW}Optional (can work around if missing):${NC}"
echo -e "${CYAN}• Node.js/npm (for frontend builds)${NC}"
echo -e "${CYAN}• Composer in PATH (can use composer.phar)${NC}"
echo ""

log "Starting production deployment for Parish Management System"

# =====================================================================================
# 1. SYSTEM REQUIREMENTS CHECK
# =====================================================================================

check_requirements() {
    log "Checking system requirements..."
    
    # Check PHP version
    if command -v php >/dev/null 2>&1; then
        PHP_CURRENT=$(php -r "echo PHP_VERSION;")
        log "PHP version: $PHP_CURRENT"
        
        # Flexible PHP version check (8.2+ acceptable, 8.4+ recommended)
        if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0);"; then
            error "PHP 8.2+ is required. Current version: $PHP_CURRENT"
        elif php -r "exit(version_compare(PHP_VERSION, '8.4.0', '<') ? 1 : 0);"; then
            warning "PHP 8.4+ is recommended. Current version: $PHP_CURRENT (acceptable)"
        fi
    else
        error "PHP is not installed"
    fi
    
    # Check required PHP extensions with helpful guidance
    local required_extensions=("pdo" "pdo_mysql" "mbstring" "tokenizer" "xml" "ctype" "json" "bcmath" "openssl" "fileinfo" "gd" "zip" "dom")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done
    
    if [ ${#missing_extensions[@]} -gt 0 ]; then
        error "Missing PHP extensions: ${missing_extensions[*]}"
        echo -e "${YELLOW}SOLUTION: Contact your hosting provider to enable these extensions OR:${NC}"
        echo -e "${CYAN}• Check your hosting control panel (cPanel/Plesk) for PHP extension settings${NC}"
        echo -e "${CYAN}• For VPS/Dedicated servers, install with: sudo dnf/yum/apt install php-[extension-name]${NC}"
        echo -e "${CYAN}• Create phpinfo.php in public folder to verify available extensions${NC}"
        exit 1
    fi
    
    log "All required PHP extensions are available"
    
    # Check Composer (with helpful installation guidance)
    if command -v composer >/dev/null 2>&1; then
        COMPOSER_VERSION=$(composer --version 2>/dev/null | cut -d' ' -f3)
        log "Composer version: $COMPOSER_VERSION"
        COMPOSER_CMD="composer"
    else
        warning "Composer not found in PATH. Checking for local composer.phar..."
        if [ -f "composer.phar" ]; then
            log "Found local composer.phar - will use ./composer.phar for commands"
            COMPOSER_CMD="php composer.phar"
        else
            error "Composer is required. Install from https://getcomposer.org or contact your hosting provider"
        fi
    fi
    
    # Check Node.js and npm (with alternative suggestions)
    if command -v node >/dev/null 2>&1; then
        NODE_CURRENT=$(node --version)
        log "Node.js version: $NODE_CURRENT"
        
        if command -v npm >/dev/null 2>&1; then
            NPM_VERSION=$(npm --version)
            log "npm version: $NPM_VERSION"
        else
            warning "npm not found. Will attempt to use npx or contact hosting provider"
        fi
    else
        warning "Node.js not found. Will skip frontend build or use pre-built assets"
        echo -e "${YELLOW}OPTIONS:${NC}"
        echo -e "${CYAN}• Contact hosting provider to install Node.js${NC}"
        echo -e "${CYAN}• Build assets locally and upload public/build folder${NC}"
        echo -e "${CYAN}• Use hosting provider's build tools if available${NC}"
        read -p "Continue without Node.js? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
        NODE_AVAILABLE=false
    fi
    
    success "All system requirements are met"
}

# =====================================================================================
# 2. ENVIRONMENT CONFIGURATION
# =====================================================================================

setup_environment() {
    log "Setting up production environment..."
    
    # Create production .env file if it doesn't exist
    if [ ! -f .env ]; then
        if [ -f .env.production ]; then
            cp .env.production .env
            log "Copied .env.production to .env"
        elif [ -f .env.example ]; then
            cp .env.example .env
            log "Copied .env.example to .env"
        else
            error ".env file not found and no template available"
        fi
    fi
    
    # Generate application key if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        log "Generating application key..."
        php artisan key:generate --force
    fi
    
    # Prompt for database configuration
    info "Database configuration required..."
    
    read -p "Database type (mysql/pgsql/sqlite) [mysql]: " DB_CONNECTION
    DB_CONNECTION=${DB_CONNECTION:-mysql}
    
    if [ "$DB_CONNECTION" != "sqlite" ]; then
        read -p "Database host [localhost]: " DB_HOST
        DB_HOST=${DB_HOST:-localhost}
        
        read -p "Database port [3306]: " DB_PORT
        DB_PORT=${DB_PORT:-3306}
        
        read -p "Database name: " DB_DATABASE
        if [ -z "$DB_DATABASE" ]; then
            error "Database name is required"
        fi
        
        read -p "Database username: " DB_USERNAME
        if [ -z "$DB_USERNAME" ]; then
            error "Database username is required"
        fi
        
        read -s -p "Database password: " DB_PASSWORD
        echo
        
        # Update .env file
        sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=$DB_CONNECTION/" .env
        sed -i "s/^DB_HOST=.*/DB_HOST=$DB_HOST/" .env
        sed -i "s/^DB_PORT=.*/DB_PORT=$DB_PORT/" .env
        sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
        sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env
        sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
    else
        log "Using SQLite database"
        sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/" .env
        # Create SQLite database file
        touch database/database.sqlite
    fi
    
    # Set production environment
    sed -i "s/^APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/^APP_DEBUG=.*/APP_DEBUG=false/" .env
    
    # Configure session and cache
    sed -i "s/^SESSION_DRIVER=.*/SESSION_DRIVER=database/" .env
    sed -i "s/^CACHE_STORE=.*/CACHE_STORE=database/" .env
    
    success "Environment configuration completed"
}

# =====================================================================================
# 3. DEPENDENCIES INSTALLATION
# =====================================================================================

install_dependencies() {
    log "Installing PHP dependencies..."
    
    # Install PHP dependencies optimized for production
    $COMPOSER_CMD install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    
    # Install Node.js dependencies if Node.js is available
    if [ "${NODE_AVAILABLE:-true}" = "true" ] && command -v npm >/dev/null 2>&1; then
        log "Installing Node.js dependencies..."
        
        # Clean install Node dependencies
        rm -rf node_modules package-lock.json 2>/dev/null || true
        npm ci --production=false || npm install
    else
        warning "Skipping Node.js dependencies - Node.js not available or disabled"
        warning "You may need to build assets locally and upload the public/build folder"
    fi
    
    success "Dependencies installation completed"
}

# =====================================================================================
# 4. DATABASE SETUP
# =====================================================================================

setup_database() {
    log "Setting up database..."
    
    # Test database connection
    if ! php artisan migrate:status >/dev/null 2>&1; then
        error "Database connection failed. Please check your database configuration."
    fi
    
    # Check MySQL version and configuration for production compatibility
    if [ "${DB_CONNECTION:-mysql}" = "mysql" ] || [ "${DB_CONNECTION:-mysql}" = "production" ]; then
        log "Optimizing MySQL configuration for migration compatibility..."
        
        # Test MySQL connection and configure session variables
        php -r "
        try {
            \$host = '${DB_HOST:-localhost}';
            \$port = '${DB_PORT:-3306}';
            \$dbname = '${DB_DATABASE:-parish_management}';
            \$username = '${DB_USERNAME:-root}';
            \$password = '${DB_PASSWORD:-}';
            
            \$pdo = new PDO(\"mysql:host=\$host;port=\$port;dbname=\$dbname\", \$username, \$password);
            \$version = \$pdo->query('SELECT VERSION()')->fetchColumn();
            echo \"MySQL version: \$version\n\";
            
            // Set session variables for better compatibility
            \$pdo->exec('SET sql_mode = \"STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION\"');
            \$pdo->exec('SET innodb_strict_mode = 0');
            \$pdo->exec('SET innodb_large_prefix = 1');
            \$pdo->exec('SET innodb_file_format = Barracuda');
            echo \"MySQL session optimized for Laravel migrations\n\";
        } catch (Exception \$e) {
            echo \"Warning: \" . \$e->getMessage() . \"\n\";
        }
        " || warning "Could not optimize MySQL session settings"
    fi
    
    # Ask user about migration strategy
    info "Choose migration strategy:"
    echo "1. Fresh migration (destroys existing data - recommended for new installations)"
    echo "2. Run pending migrations only (preserves existing data)"
    read -p "Choose option (1/2) [2]: " MIGRATION_CHOICE
    MIGRATION_CHOICE=${MIGRATION_CHOICE:-2}
    
    if [ "$MIGRATION_CHOICE" = "1" ]; then
        log "Running fresh migrations (this will destroy existing data)..."
        php artisan migrate:fresh --force
    else
        log "Running pending migrations with enhanced error handling..."
        if ! php artisan migrate --force --verbose; then
            warning "Some migrations failed. Checking for specific issues..."
            
            # List failed migrations for debugging
            log "Checking migration status..."
            php artisan migrate:status
            
            error "Migration failed. Please check the error messages above and fix any database configuration issues."
        fi
    fi
    
    # Seed the database
    log "Seeding database..."
    php artisan db:seed --force
    
    success "Database setup completed"
}

# =====================================================================================
# 5. FRONTEND BUILD
# =====================================================================================

build_frontend() {
    # Check if we should attempt frontend build
    if [ "${NODE_AVAILABLE:-true}" = "false" ]; then
        warning "Skipping frontend build - Node.js not available"
        
        # Check if pre-built assets exist
        if [ -d "public/build" ] && [ -n "$(ls -A public/build 2>/dev/null)" ]; then
            log "Found existing frontend assets in public/build"
            success "Using pre-built frontend assets"
            return 0
        else
            warning "No pre-built assets found in public/build"
            echo -e "${YELLOW}OPTIONS:${NC}"
            echo -e "${CYAN}• Build assets locally: npm run build${NC}"
            echo -e "${CYAN}• Upload public/build folder to server${NC}"
            echo -e "${CYAN}• Contact hosting provider for Node.js support${NC}"
            
            read -p "Continue without frontend build? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                error "Frontend assets are required for the application to work properly"
            fi
            warning "Proceeding without frontend build - application may not work correctly"
            return 0
        fi
    fi
    
    log "Building frontend assets for production..."
    
    # Build production assets
    if command -v npm >/dev/null 2>&1; then
        npm run build
    else
        error "npm not available for frontend build"
    fi
    
    # Verify build files exist
    if [ ! -d "public/build" ] || [ -z "$(ls -A public/build 2>/dev/null)" ]; then
        error "Frontend build failed - build directory is empty or missing"
    fi
    
    success "Frontend assets built successfully"
}

# =====================================================================================
# 6. OPTIMIZATION
# =====================================================================================

optimize_application() {
    log "Optimizing application for production..."
    
    # Clear all caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Cache configuration
    php artisan config:cache
    
    # Cache routes
    php artisan route:cache
    
    # Cache views
    php artisan view:cache
    
    # Optimize autoloader
    composer dump-autoload --optimize
    
    success "Application optimization completed"
}

# =====================================================================================
# 7. SECURITY CONFIGURATION
# =====================================================================================

setup_security() {
    log "Configuring security settings..."
    
    # Set proper file permissions
    find . -type f -exec chmod 644 {} \;
    find . -type d -exec chmod 755 {} \;
    
    # Set specific permissions for sensitive directories
    chmod -R 775 storage bootstrap/cache
    chmod -R 755 public
    
    # Protect sensitive files
    chmod 600 .env
    
    # Create security headers configuration
    if [ -f "public/.htaccess" ]; then
        log "Adding security headers to .htaccess"
        cat >> public/.htaccess << 'EOF'

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>

# Hide sensitive files
<Files ~ "^\.env">
    Order allow,deny
    Deny from all
</Files>

<Files ~ "^\.git">
    Order allow,deny
    Deny from all
</Files>
EOF
    fi
    
    success "Security configuration completed"
}

# =====================================================================================
# 8. BACKUP SETUP
# =====================================================================================

setup_backup() {
    log "Setting up backup system..."
    
    # Create backup directory
    mkdir -p storage/app/backups
    
    # Create backup script
    cat > storage/app/backup-script.sh << 'EOF'
#!/bin/bash
# Parish Management System Backup Script

BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Backup database
if [ "$DB_CONNECTION" = "mysql" ]; then
    mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/database_$DATE.sql"
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    pg_dump -U "$DB_USERNAME" -h "$DB_HOST" "$DB_DATABASE" > "$BACKUP_DIR/database_$DATE.sql"
elif [ "$DB_CONNECTION" = "sqlite" ]; then
    cp "$PROJECT_DIR/database/database.sqlite" "$BACKUP_DIR/database_$DATE.sqlite"
fi

# Backup files
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" -C "$PROJECT_DIR" . --exclude=node_modules --exclude=vendor --exclude=storage/logs

# Keep only last 7 backups
find "$BACKUP_DIR" -name "database_*.sql" -o -name "database_*.sqlite" -o -name "files_*.tar.gz" | sort | head -n -7 | xargs rm -f

echo "Backup completed: $DATE"
EOF
    
    chmod +x storage/app/backup-script.sh
    
    success "Backup system configured"
}

# =====================================================================================
# 9. HEALTH CHECKS
# =====================================================================================

run_health_checks() {
    log "Running production health checks..."
    
    # Check if application can boot
    if ! php artisan --version >/dev/null 2>&1; then
        error "Application cannot boot properly"
    fi
    
    # Check database connection
    if ! php artisan migrate:status >/dev/null 2>&1; then
        error "Database connection test failed"
    fi
    
    # Check critical routes
    php artisan route:list | grep -q "login" || warning "Login route not found"
    php artisan route:list | grep -q "dashboard" || warning "Dashboard route not found"
    php artisan route:list | grep -q "members" || warning "Members route not found"
    
    # Check storage permissions
    if [ ! -w "storage/logs" ]; then
        error "Storage/logs directory is not writable"
    fi
    
    # Check if assets are built
    if [ ! -d "public/build" ] || [ -z "$(ls -A public/build)" ]; then
        error "Frontend assets are not built"
    fi
    
    # Run core functionality tests
    log "Running core functionality tests..."
    php artisan test --filter="admin can create new member|member registration validates required fields|marriage details required only for church marriages" --stop-on-failure
    
    success "All health checks passed"
}

# =====================================================================================
# 10. DEPLOYMENT VERIFICATION
# =====================================================================================

verify_deployment() {
    log "Verifying deployment..."
    
    # Create a test admin user if none exists
    php artisan tinker --execute="
    if (\App\Models\User::where('email', 'admin@parish.com')->doesntExist()) {
        \App\Models\User::create([
            'name' => 'Parish Administrator',
            'email' => 'admin@parish.com',
            'password' => bcrypt('admin123'),
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        echo 'Admin user created: admin@parish.com / admin123\n';
    } else {
        echo 'Admin user already exists\n';
    }
    "
    
    # Display system information
    info "=== DEPLOYMENT SUMMARY ==="
    info "PHP Version: $(php -r 'echo PHP_VERSION;')"
    info "Laravel Version: $(php artisan --version | cut -d' ' -f3)"
    info "Environment: $(grep APP_ENV .env | cut -d'=' -f2)"
    info "Database: $(grep DB_CONNECTION .env | cut -d'=' -f2)"
    info "Admin Login: admin@parish.com"
    info "Default Password: admin123"
    
    success "Deployment verification completed"
}

# =====================================================================================
# 11. POST-DEPLOYMENT INSTRUCTIONS
# =====================================================================================

show_post_deployment_instructions() {
    echo -e "${CYAN}"
    echo "════════════════════════════════════════════════════════════════════"
    echo "                     DEPLOYMENT COMPLETED!"
    echo "════════════════════════════════════════════════════════════════════"
    echo -e "${NC}"
    
    echo -e "${GREEN}✅ Parish Management System has been successfully deployed!${NC}"
    echo
    echo -e "${YELLOW}IMPORTANT POST-DEPLOYMENT STEPS:${NC}"
    echo "1. Point your web server document root to: $SCRIPT_DIR/public"
    echo "2. Set up SSL certificate for HTTPS"
    echo "3. Configure web server (Apache/Nginx) with proper rewrite rules"
    echo "4. Set up automated backups using the script: storage/app/backup-script.sh"
    echo "5. Configure cron jobs for Laravel scheduler:"
    echo "   * * * * * cd $SCRIPT_DIR && php artisan schedule:run >> /dev/null 2>&1"
    echo "6. Set up log rotation for storage/logs/*"
    echo "7. Configure firewall to allow only necessary ports (80, 443, 22)"
    echo
    echo -e "${GREEN}ACCESS INFORMATION:${NC}"
    echo "🌐 Application URL: https://yourdomain.com"
    echo "👤 Admin Email: admin@parish.com"
    echo "🔑 Admin Password: admin123"
    echo "📧 Change admin password after first login!"
    echo
    echo -e "${BLUE}MAINTENANCE COMMANDS:${NC}"
    echo "• Update application: git pull && composer install --no-dev && npm run build && php artisan migrate"
    echo "• Clear cache: php artisan cache:clear"
    echo "• View logs: tail -f storage/logs/laravel.log"
    echo "• Backup: ./storage/app/backup-script.sh"
    echo
    echo -e "${RED}SECURITY REMINDERS:${NC}"
    echo "⚠️  Change default admin password immediately"
    echo "⚠️  Keep system updated regularly"
    echo "⚠️  Monitor logs for suspicious activity"
    echo "⚠️  Regular database backups are essential"
    echo
    echo -e "${GREEN}For support and documentation: https://github.com/JosephNjoroge8/parish-management-system${NC}"
    echo
}

# =====================================================================================
# MAIN EXECUTION
# =====================================================================================

main() {
    log "=== STARTING PRODUCTION DEPLOYMENT ==="
    
    # Change to script directory
    cd "$SCRIPT_DIR"
    
    # Run deployment steps
    check_requirements
    setup_environment
    install_dependencies
    setup_database
    build_frontend
    optimize_application
    setup_security
    setup_backup
    run_health_checks
    verify_deployment
    
    log "=== DEPLOYMENT COMPLETED SUCCESSFULLY ==="
    
    # Show post-deployment instructions
    show_post_deployment_instructions
}

# Error handling
trap 'error "Deployment failed at line $LINENO. Check the log file: $LOG_FILE"' ERR

# Run main function
main "$@"

# End of script
log "Deployment script finished. Log file: $LOG_FILE"

exit 0