#!/bin/bash

# =====================================================================================
# Parish Management System - Create Super Admin Script
# =====================================================================================
# 
# This script creates a super administrator user for the Parish Management System
# 
# Usage:
#   chmod +x create-admin.sh
#   ./create-admin.sh
#
# =====================================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}"
echo "════════════════════════════════════════════════════════════════════"
echo "                  PARISH MANAGEMENT SYSTEM"
echo "                   Create Super Admin User"
echo "════════════════════════════════════════════════════════════════════"
echo -e "${NC}"

# Get admin details
echo -e "${BLUE}Enter Super Admin Details:${NC}"
read -p "Full Name: " ADMIN_NAME
read -p "Email Address: " ADMIN_EMAIL

# Password with confirmation
while true; do
    read -s -p "Password: " ADMIN_PASSWORD
    echo
    read -s -p "Confirm Password: " ADMIN_PASSWORD_CONFIRM
    echo
    
    if [ "$ADMIN_PASSWORD" = "$ADMIN_PASSWORD_CONFIRM" ]; then
        break
    else
        echo -e "${RED}Passwords do not match. Please try again.${NC}"
    fi
done

echo -e "${YELLOW}Creating super admin user...${NC}"

# Create the admin user using Laravel Tinker
php artisan tinker --execute="
try {
    // Check if user already exists
    if (\App\Models\User::where('email', '$ADMIN_EMAIL')->exists()) {
        echo '${RED}Error: User with email $ADMIN_EMAIL already exists!${NC}\n';
        exit(1);
    }
    
    // Create new super admin user
    \$user = \App\Models\User::create([
        'name' => '$ADMIN_NAME',
        'email' => '$ADMIN_EMAIL',
        'password' => bcrypt('$ADMIN_PASSWORD'),
        'is_admin' => true,
        'is_active' => true,
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo '${GREEN}✅ Super Admin user created successfully!${NC}\n';
    echo '${CYAN}Login Details:${NC}\n';
    echo '   Email: $ADMIN_EMAIL\n';
    echo '   Password: [Hidden for security]\n';
    echo '   Admin Status: Yes\n';
    echo '   Account Status: Active\n';
    echo '\n${YELLOW}You can now login to the Parish Management System${NC}\n';
    
} catch (Exception \$e) {
    echo '${RED}Error creating admin user: ' . \$e->getMessage() . '${NC}\n';
    exit(1);
}
"

echo -e "${GREEN}Super admin creation completed!${NC}"
echo -e "${BLUE}Access your Parish Management System at: https://yourdomain.com${NC}"