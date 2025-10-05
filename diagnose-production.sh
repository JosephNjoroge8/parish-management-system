#!/bin/bash

# ============================================================================
# Parish Management System - Production Diagnostic Script
# ============================================================================
# This script helps diagnose common production issues
# Run this when experiencing database or connection problems
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

print_status "🔍 Starting Parish Management System Diagnostics..."

echo ""
print_status "=== ENVIRONMENT CHECK ==="
echo "PHP Version: $(php -v | head -n 1)"
echo "Laravel Version: $(php artisan --version 2>/dev/null || echo 'Laravel not accessible')"
echo "Current User: $(whoami)"
echo "Current Directory: $(pwd)"
echo "Server Time: $(date)"

echo ""
print_status "=== DATABASE CONFIGURATION ==="
if [ -f .env ]; then
    print_success ".env file exists"
    echo "Database Configuration:"
    grep -E '^DB_' .env | while read line; do
        if [[ $line == *"PASSWORD"* ]]; then
            echo "  ${line%=*}=***HIDDEN***"
        else
            echo "  $line"
        fi
    done
else
    print_error ".env file not found!"
fi

echo ""
print_status "=== DATABASE CONNECTION TEST ==="
php artisan tinker --execute="
try {
    echo 'Testing database connection...' . PHP_EOL;
    \$connection = \DB::connection();
    \$pdo = \$connection->getPdo();
    echo '✅ Database connection: SUCCESS' . PHP_EOL;
    echo 'Driver: ' . \$connection->getDriverName() . PHP_EOL;
    echo 'Database: ' . \$connection->getDatabaseName() . PHP_EOL;
    
    // Test a simple query
    \$result = \DB::select('SELECT 1 as test');
    echo '✅ Query test: SUCCESS' . PHP_EOL;
    
} catch (\Exception \$e) {
    echo '❌ Database connection: FAILED' . PHP_EOL;
    echo 'Error: ' . \$e->getMessage() . PHP_EOL;
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
}
"

echo ""
print_status "=== MYSQL SERVICE STATUS ==="
if command -v systemctl >/dev/null; then
    if systemctl is-active --quiet mysql; then
        print_success "MySQL service is running"
        echo "MySQL status:"
        systemctl status mysql --no-pager -l
    elif systemctl is-active --quiet mariadb; then
        print_success "MariaDB service is running"
        echo "MariaDB status:"
        systemctl status mariadb --no-pager -l
    else
        print_error "MySQL/MariaDB service is not running"
        echo "Try starting it with:"
        echo "  sudo systemctl start mysql"
        echo "  # or"
        echo "  sudo systemctl start mariadb"
    fi
else
    print_warning "systemctl not available - checking process list"
    if pgrep mysql >/dev/null; then
        print_success "MySQL process found"
    else
        print_error "MySQL process not found"
    fi
fi

echo ""
print_status "=== MYSQL CONNECTION TEST ==="
# Get database credentials from .env
if [ -f .env ]; then
    DB_HOST=$(grep '^DB_HOST=' .env | cut -d'=' -f2)
    DB_PORT=$(grep '^DB_PORT=' .env | cut -d'=' -f2)
    DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d'=' -f2)
    DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d'=' -f2)
    
    echo "Testing MySQL connection..."
    echo "Host: $DB_HOST"
    echo "Port: $DB_PORT"
    echo "Database: $DB_DATABASE"
    echo "Username: $DB_USERNAME"
    
    if command -v mysql >/dev/null; then
        # Test connection without password prompt
        if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -e "SELECT 1;" "$DB_DATABASE" 2>/dev/null; then
            print_success "Direct MySQL connection: SUCCESS"
        else
            print_error "Direct MySQL connection: FAILED"
            echo "Try manual connection:"
            echo "  mysql -h$DB_HOST -P$DB_PORT -u$DB_USERNAME -p $DB_DATABASE"
        fi
    else
        print_warning "mysql command not found"
    fi
fi

echo ""
print_status "=== FILE PERMISSIONS ==="
echo "Application directory permissions:"
ls -la . | head -5
echo ""
echo "Storage directory permissions:"
ls -la storage/ | head -5
echo ""
echo "Bootstrap cache permissions:"
ls -la bootstrap/cache/ | head -5

echo ""
print_status "=== MIGRATION STATUS ==="
php artisan migrate:status 2>/dev/null || print_error "Cannot check migration status"

echo ""
print_status "=== ERROR LOGS ==="
echo "Recent Laravel errors (last 10 lines):"
if [ -f storage/logs/laravel.log ]; then
    tail -10 storage/logs/laravel.log
else
    print_warning "No Laravel log file found"
fi

echo ""
print_status "=== RECENT WEB SERVER ERRORS ==="
echo "Nginx errors (if available):"
if [ -f /var/log/nginx/error.log ]; then
    sudo tail -5 /var/log/nginx/error.log 2>/dev/null || echo "Cannot read nginx error log"
else
    echo "Nginx error log not found"
fi

echo "Apache errors (if available):"
if [ -f /var/log/apache2/error.log ]; then
    sudo tail -5 /var/log/apache2/error.log 2>/dev/null || echo "Cannot read apache error log"
else
    echo "Apache error log not found"
fi

echo ""
print_status "=== DISK SPACE ==="
df -h | grep -E '^/dev|Filesystem'

echo ""
print_status "=== PROCESS INFORMATION ==="
echo "PHP processes:"
ps aux | grep php | grep -v grep | head -3

echo ""
echo "Web server processes:"
ps aux | grep -E 'nginx|apache|httpd' | grep -v grep | head -3

echo ""
print_status "=== NETWORK CONNECTIVITY ==="
echo "Listening ports:"
netstat -tulnp 2>/dev/null | grep -E ':80|:443|:3306|:8000' || ss -tulnp | grep -E ':80|:443|:3306|:8000'

echo ""
print_success "🎯 Diagnostics complete!"
print_status "If you found issues, check the troubleshooting guide above."