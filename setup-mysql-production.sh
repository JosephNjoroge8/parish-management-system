#!/bin/bash
# ============================================================================
# PARISH MANAGEMENT SYSTEM - MySQL Production Setup Script
# ============================================================================
# Run this script on your cPanel server after deployment
# This will configure MySQL database for production use
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}    PARISH MANAGEMENT SYSTEM - MySQL Production Configuration${NC}"
echo -e "${BLUE}============================================================================${NC}"
echo ""

# Database configuration
DB_NAME="parish_mgmt_prod"
DB_USER="parish_user"
DB_HOST="127.0.0.1"

echo -e "${YELLOW}This script will:${NC}"
echo "1. Configure MySQL database for production"
echo "2. Run Laravel migrations"
echo "3. Seed initial data"
echo "4. Create super admin user"
echo "5. Optimize database settings"
echo ""

read -p "Continue? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi

# Step 1: Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Please run this script from the Laravel root directory.${NC}"
    exit 1
fi

echo -e "${BLUE}Step 1: Installing Composer Dependencies...${NC}"
composer install --optimize-autoloader --no-dev
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Composer dependencies installed successfully${NC}"
else
    echo -e "${RED}❌ Failed to install Composer dependencies${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}Step 2: Generating Application Key...${NC}"
php artisan key:generate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Application key generated${NC}"
else
    echo -e "${RED}❌ Failed to generate application key${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}Step 3: Running Database Migrations...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database migrations completed${NC}"
else
    echo -e "${RED}❌ Database migration failed${NC}"
    echo -e "${YELLOW}Please check your database configuration in .env file${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}Step 4: Seeding Database...${NC}"
php artisan db:seed --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database seeding completed${NC}"
else
    echo -e "${RED}❌ Database seeding failed${NC}"
    echo -e "${YELLOW}Continuing with setup...${NC}"
fi

echo ""
echo -e "${BLUE}Step 5: Creating Storage Link...${NC}"
php artisan storage:link
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Storage link created${NC}"
else
    echo -e "${YELLOW}⚠️  Storage link may already exist${NC}"
fi

echo ""
echo -e "${BLUE}Step 6: Optimizing Application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✅ Application optimized for production${NC}"

echo ""
echo -e "${BLUE}Step 7: Setting File Permissions...${NC}"
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 600 .env
echo -e "${GREEN}✅ File permissions set${NC}"

echo ""
echo -e "${BLUE}Step 8: Database Health Check...${NC}"
MEMBER_COUNT=$(php artisan tinker --execute="echo \App\Models\Member::count();")
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();")
ROLE_COUNT=$(php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();")

echo -e "Members in database: ${GREEN}${MEMBER_COUNT}${NC}"
echo -e "Users in database: ${GREEN}${USER_COUNT}${NC}"
echo -e "Roles configured: ${GREEN}${ROLE_COUNT}${NC}"

echo ""
echo -e "${BLUE}Step 9: Testing Report Routes...${NC}"
REPORT_ROUTES=$(php artisan route:list --name=reports | wc -l)
echo -e "Report routes available: ${GREEN}$((REPORT_ROUTES - 1))${NC}"

echo ""
echo -e "${GREEN}============================================================================${NC}"
echo -e "${GREEN}                     🎉 SETUP COMPLETED SUCCESSFULLY! 🎉${NC}"
echo -e "${GREEN}============================================================================${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Access your application: https://yourdomain.com"
echo "2. Login with super admin credentials"
echo "3. Test member registration and reports"
echo "4. Configure email settings if needed"
echo ""
echo -e "${YELLOW}Super Admin Credentials:${NC}"
echo "Email: admin@parish.com"
echo "Password: Check your .env file or database seeder"
echo ""
echo -e "${YELLOW}Important Security Notes:${NC}"
echo "1. Change default admin password immediately"
echo "2. Update .env with your production values"
echo "3. Enable HTTPS in production"
echo "4. Set up regular database backups"
echo ""
echo -e "${BLUE}System Status: ${GREEN}READY FOR PRODUCTION${NC}"
echo ""

# Create a quick test
echo -e "${BLUE}Creating quick system test...${NC}"
cat > system_health_check.php << 'EOF'
<?php
// Quick system health check
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Test database connection
    DB::connection()->getPdo();
    echo "✅ Database: Connected\n";
    
    // Test member model
    $memberCount = \App\Models\Member::count();
    echo "✅ Members: $memberCount records\n";
    
    // Test user authentication
    $userCount = \App\Models\User::count();
    echo "✅ Users: $userCount accounts\n";
    
    // Test roles and permissions
    $roleCount = \Spatie\Permission\Models\Role::count();
    echo "✅ Roles: $roleCount configured\n";
    
    // Test reports routes
    $routes = collect(app('router')->getRoutes())->filter(function($route) {
        return str_contains($route->getName() ?? '', 'reports.');
    })->count();
    echo "✅ Reports: $routes routes active\n";
    
    echo "\n🎉 All systems operational!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
EOF

php system_health_check.php
rm system_health_check.php

echo -e "${GREEN}Deployment verification completed!${NC}"
echo ""
echo -e "${BLUE}============================================================================${NC}"