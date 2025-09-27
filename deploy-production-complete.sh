#!/bin/bash

# =============================================================================
# PARISH MANAGEMENT SYSTEM - PRODUCTION DEPLOYMENT SCRIPT
# =============================================================================
# This script prepares and deploys the Parish Management System to production
# Run this script on your production server after uploading the code
# =============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/parish-system"
BACKUP_DIR="/var/backups/parish-system"
WEB_USER="www-data"

echo -e "${BLUE}🚀 Parish Management System - Production Deployment${NC}"
echo "================================================================="

# Function to print status
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root or with sudo"
    exit 1
fi

print_status "Starting deployment process..."

# =============================================================================
# 1. SYSTEM PREPARATION
# =============================================================================
echo -e "\n${BLUE}📦 Step 1: System Preparation${NC}"

# Update system packages
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install required packages
print_status "Installing required packages..."
apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-sqlite3 php8.2-curl \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-xml php8.2-intl php8.2-bcmath \
    nginx mysql-server redis-server nodejs npm composer git unzip

# =============================================================================
# 2. PROJECT SETUP
# =============================================================================
echo -e "\n${BLUE}📁 Step 2: Project Setup${NC}"

# Create project directory if it doesn't exist
if [ ! -d "$PROJECT_DIR" ]; then
    mkdir -p "$PROJECT_DIR"
    print_status "Created project directory: $PROJECT_DIR"
fi

# Navigate to project directory
cd "$PROJECT_DIR"

# Create backup directory
mkdir -p "$BACKUP_DIR"
print_status "Backup directory created: $BACKUP_DIR"

# =============================================================================
# 3. APPLICATION DEPLOYMENT
# =============================================================================
echo -e "\n${BLUE}🔧 Step 3: Application Deployment${NC}"

# Install PHP dependencies (production optimized)
print_status "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev --no-scripts

# Install and build frontend assets
print_status "Installing and building frontend assets..."
npm ci --production=false  # Install dev dependencies for building
npm run build
npm prune --production  # Remove dev dependencies after build

# =============================================================================
# 4. ENVIRONMENT CONFIGURATION
# =============================================================================
echo -e "\n${BLUE}⚙️  Step 4: Environment Configuration${NC}"

# Copy production environment file
if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        cp .env.production .env
        print_status "Production environment file configured"
    else
        cp .env.example .env
        print_warning "Copied example environment file - PLEASE CONFIGURE MANUALLY"
    fi
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --force
    print_status "Application key generated"
fi

# =============================================================================
# 5. DATABASE SETUP
# =============================================================================
echo -e "\n${BLUE}🗄️  Step 5: Database Setup${NC}"

# Create database backup if it exists
if [ -f "database/database.sqlite" ]; then
    cp database/database.sqlite "$BACKUP_DIR/database_backup_$(date +%Y%m%d_%H%M%S).sqlite"
    print_status "Database backup created"
fi

# Run migrations
print_status "Running database migrations..."
php artisan migrate --force

# Seed production data if needed
if php artisan db:seed --class=ProductionSeeder --dry-run > /dev/null 2>&1; then
    php artisan db:seed --class=ProductionSeeder --force
    print_status "Production data seeded"
fi

# =============================================================================
# 6. OPTIMIZATION
# =============================================================================
echo -e "\n${BLUE}⚡ Step 6: Performance Optimization${NC}"

# Clear and cache configurations
print_status "Optimizing application..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Database optimizations
print_status "Optimizing database..."
if [ -f "database/database.sqlite" ]; then
    sqlite3 database/database.sqlite "VACUUM;"
    sqlite3 database/database.sqlite "PRAGMA optimize;"
fi

# =============================================================================
# 7. SECURITY & PERMISSIONS
# =============================================================================
echo -e "\n${BLUE}🔒 Step 7: Security & Permissions${NC}"

# Set proper ownership
chown -R $WEB_USER:$WEB_USER "$PROJECT_DIR"
print_status "Set proper file ownership"

# Set secure permissions
find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
find "$PROJECT_DIR" -type d -exec chmod 755 {} \;

# Set special permissions for writable directories
chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
chmod 600 "$PROJECT_DIR/.env"

# Secure database file if SQLite
if [ -f "$PROJECT_DIR/database/database.sqlite" ]; then
    chmod 600 "$PROJECT_DIR/database/database.sqlite"
    chown $WEB_USER:$WEB_USER "$PROJECT_DIR/database/database.sqlite"
fi

print_status "Secure permissions configured"

# =============================================================================
# 8. WEB SERVER CONFIGURATION
# =============================================================================
echo -e "\n${BLUE}🌐 Step 8: Web Server Configuration${NC}"

# Create Nginx configuration
cat > /etc/nginx/sites-available/parish-system << 'EOF'
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/parish-system/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Static asset caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, no-transform, immutable";
    }

    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }

    location ~* (composer\.json|composer\.lock|package\.json|package-lock\.json|\.gitignore)$ {
        deny all;
    }
}
EOF

# Enable the site
if [ ! -L /etc/nginx/sites-enabled/parish-system ]; then
    ln -s /etc/nginx/sites-available/parish-system /etc/nginx/sites-enabled/
    print_status "Nginx configuration enabled"
fi

# Remove default site if it exists
if [ -L /etc/nginx/sites-enabled/default ]; then
    rm /etc/nginx/sites-enabled/default
    print_status "Default Nginx site removed"
fi

# Test nginx configuration
if nginx -t; then
    systemctl reload nginx
    print_status "Nginx configuration reloaded"
else
    print_error "Nginx configuration test failed"
fi

# =============================================================================
# 9. PHP-FPM OPTIMIZATION
# =============================================================================
echo -e "\n${BLUE}🐘 Step 9: PHP-FPM Optimization${NC}"

# Create optimized PHP-FPM pool configuration
cat > /etc/php/8.2/fpm/pool.d/parish-system.conf << 'EOF'
[parish-system]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm-parish.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 1000

php_value[memory_limit] = 512M
php_value[max_execution_time] = 300
php_value[upload_max_filesize] = 50M
php_value[post_max_size] = 50M
EOF

# Restart PHP-FPM
systemctl restart php8.2-fpm
print_status "PHP-FPM optimized and restarted"

# =============================================================================
# 10. MONITORING & LOGGING
# =============================================================================
echo -e "\n${BLUE}📊 Step 10: Monitoring & Logging${NC}"

# Create log rotation configuration
cat > /etc/logrotate.d/parish-system << 'EOF'
/var/www/parish-system/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
}
EOF

print_status "Log rotation configured"

# Create monitoring script
cat > /usr/local/bin/parish-system-monitor << 'EOF'
#!/bin/bash
# Parish System Health Monitor

PROJECT_DIR="/var/www/parish-system"

# Check if application is responding
if curl -f -s http://localhost/health > /dev/null; then
    echo "$(date): Parish System is healthy"
else
    echo "$(date): Parish System is not responding" >&2
    systemctl restart nginx php8.2-fpm
fi

# Check disk space
DISK_USAGE=$(df "$PROJECT_DIR" | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    echo "$(date): Disk usage is high: ${DISK_USAGE}%" >&2
fi

# Check log file sizes
find "$PROJECT_DIR/storage/logs" -name "*.log" -size +100M -exec echo "$(date): Large log file: {}" \;
EOF

chmod +x /usr/local/bin/parish-system-monitor
print_status "Health monitoring script created"

# =============================================================================
# 11. SSL CERTIFICATE (Let's Encrypt)
# =============================================================================
echo -e "\n${BLUE}🔐 Step 11: SSL Certificate Setup${NC}"

print_warning "SSL Certificate setup requires manual configuration"
echo "To enable HTTPS with Let's Encrypt, run:"
echo "  1. Install certbot: apt install certbot python3-certbot-nginx"
echo "  2. Get certificate: certbot --nginx -d your-domain.com"
echo "  3. Test renewal: certbot renew --dry-run"

# =============================================================================
# 12. FINAL STEPS
# =============================================================================
echo -e "\n${BLUE}✅ Step 12: Final Configuration${NC}"

# Create cron jobs for maintenance
(crontab -l 2>/dev/null; echo "0 2 * * * cd $PROJECT_DIR && php artisan schedule:run") | crontab -
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/parish-system-monitor") | crontab -

print_status "Maintenance cron jobs configured"

# Start and enable services
systemctl enable nginx php8.2-fpm mysql redis-server
systemctl start nginx php8.2-fpm mysql redis-server

print_status "All services started and enabled"

# =============================================================================
# DEPLOYMENT COMPLETE
# =============================================================================
echo -e "\n${GREEN}🎉 DEPLOYMENT COMPLETE!${NC}"
echo "================================================================="
print_status "Parish Management System has been deployed successfully!"

echo -e "\n${BLUE}📋 Next Steps:${NC}"
echo "1. Update DNS to point your domain to this server"
echo "2. Configure SSL certificate with Let's Encrypt"
echo "3. Update .env file with your production settings:"
echo "   - Database credentials"
echo "   - Mail configuration"
echo "   - App URL and domain"
echo "4. Test the application at your domain"
echo "5. Set up regular backups"

echo -e "\n${BLUE}📁 Important Paths:${NC}"
echo "- Application: $PROJECT_DIR"
echo "- Logs: $PROJECT_DIR/storage/logs"
echo "- Backups: $BACKUP_DIR"
echo "- Nginx Config: /etc/nginx/sites-available/parish-system"

echo -e "\n${BLUE}🛠️  Useful Commands:${NC}"
echo "- View logs: tail -f $PROJECT_DIR/storage/logs/laravel.log"
echo "- Restart services: systemctl restart nginx php8.2-fpm"
echo "- Clear cache: cd $PROJECT_DIR && php artisan optimize:clear"
echo "- Run migrations: cd $PROJECT_DIR && php artisan migrate"

print_status "Deployment script completed successfully!"