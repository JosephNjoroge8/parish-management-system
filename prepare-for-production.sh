#!/bin/bash

# Parish Management System - Production Deployment Preparation Script
# This script prepares the codebase for production deployment, ensuring MySQL compatibility
# and removing test files and temporary documentation.

echo "======================================================"
echo "Parish Management System - Production Preparation Tool"
echo "======================================================"
echo 

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if script is run from project root
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: This script must be run from the project root directory.${NC}"
    exit 1
fi

echo -e "${BLUE}Step 1: Cleaning up test files and temporary documentation${NC}"

# Files to keep
README="README.md"
LICENSE="LICENSE"

# Remove test files
echo "Removing test files..."
rm -f test*.php
rm -f fix-*.php
echo -e "${GREEN}✓ Test files removed${NC}"

# Remove temporary MD files except README.md and LICENSE
echo "Removing temporary documentation files..."
for file in *.md; do
    if [[ "$file" != "$README" && "$file" != "$LICENSE" ]]; then
        echo "  - Removing $file"
        rm -f "$file"
    fi
done
echo -e "${GREEN}✓ Temporary documentation removed${NC}"

echo -e "${BLUE}Step 2: Setting up database configuration for production${NC}"

# Copy production database config
if [ -f "config/database.php.production" ]; then
    echo "Setting up production database configuration..."
    cp config/database.php.production config/database.php
    echo -e "${GREEN}✓ Production database configuration set up${NC}"
else
    echo -e "${YELLOW}Warning: Production database configuration not found. Skipping.${NC}"
fi

echo -e "${BLUE}Step 3: Optimizing the application for production${NC}"

# Clear cache files
echo "Clearing cache files..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Cache files cleared${NC}"

# Generate optimized files
echo "Generating optimized files..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Optimized files generated${NC}"

echo -e "${BLUE}Step 4: Building frontend assets for production${NC}"
if [ -f "package.json" ]; then
    echo "Building frontend assets..."
    npm run build
    echo -e "${GREEN}✓ Frontend assets built${NC}"
else
    echo -e "${YELLOW}Warning: package.json not found. Skipping frontend build.${NC}"
fi

echo -e "${BLUE}Step 5: Final checks${NC}"

# Check for remaining test files
remaining_test_files=$(find . -name "test*.php" -not -path "./vendor/*" -not -path "./tests/*")
if [ -n "$remaining_test_files" ]; then
    echo -e "${YELLOW}Warning: Some test files still remain:${NC}"
    echo "$remaining_test_files"
else
    echo -e "${GREEN}✓ No test files found outside of test directories${NC}"
fi

# Check for database compatibility issues
compatibility_issues=$(grep -r -l "MONTH(" --include="*.php" --exclude-dir="vendor" --exclude-dir="tests" --exclude-dir="storage" .)
if [ -n "$compatibility_issues" ]; then
    echo -e "${YELLOW}Warning: Potential database compatibility issues found:${NC}"
    echo "$compatibility_issues"
    echo -e "${YELLOW}Consider using the DatabaseCompatibilityService for these files${NC}"
else
    echo -e "${GREEN}✓ No obvious database compatibility issues found${NC}"
fi

echo
echo -e "${GREEN}=====================================================${NC}"
echo -e "${GREEN}Application is ready for production deployment!${NC}"
echo -e "${GREEN}=====================================================${NC}"
echo
echo -e "${BLUE}Next steps:${NC}"
echo "1. Update your production .env file with MySQL database credentials"
echo "2. Run database migrations on production server: php artisan migrate"
echo "3. Seed initial data if needed: php artisan db:seed"
echo "4. Set proper file permissions on production server"
echo "5. Configure your web server (Apache/Nginx)"
echo
echo -e "${YELLOW}Note: Make sure to test thoroughly after deployment!${NC}"

exit 0