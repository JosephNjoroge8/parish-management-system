#!/bin/bash

##############################################################################
# QUICK PRODUCTION CHECK SCRIPT
##############################################################################
# Run this before deploying to ensure everything is ready
##############################################################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}Pre-Deployment Check${NC}"
echo -e "${BLUE}================================${NC}\n"

# Check 1: Node modules
echo -e "${BLUE}[1/7]${NC} Checking Node modules..."
if [ -d "node_modules" ]; then
    echo -e "${GREEN}✓${NC} Node modules installed\n"
else
    echo -e "${RED}✗${NC} Node modules not found"
    echo -e "${YELLOW}Run: npm install${NC}\n"
    exit 1
fi

# Check 2: Build directory
echo -e "${BLUE}[2/7]${NC} Checking build directory..."
if [ -d "public/build" ] && [ -f "public/build/manifest.json" ]; then
    echo -e "${GREEN}✓${NC} Build directory exists with manifest\n"
else
    echo -e "${RED}✗${NC} Build directory or manifest missing"
    echo -e "${YELLOW}Run: npm run build${NC}\n"
    exit 1
fi

# Check 3: Manifest validity
echo -e "${BLUE}[3/7]${NC} Validating manifest.json..."
if command -v jq &> /dev/null; then
    if jq empty public/build/manifest.json 2>/dev/null; then
        ENTRIES=$(jq 'length' public/build/manifest.json)
        echo -e "${GREEN}✓${NC} Manifest is valid JSON with $ENTRIES entries\n"
    else
        echo -e "${RED}✗${NC} Manifest is invalid JSON"
        echo -e "${YELLOW}Run: npm run build${NC}\n"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠${NC} jq not installed, skipping JSON validation\n"
fi

# Check 4: Critical assets
echo -e "${BLUE}[4/7]${NC} Checking critical assets..."
ASSETS_FOUND=0
if [ -d "public/build/assets" ]; then
    JS_COUNT=$(find public/build/assets -name "*.js" -type f | wc -l)
    CSS_COUNT=$(find public/build/assets -name "*.css" -type f | wc -l)
    
    if [ "$JS_COUNT" -gt 0 ] && [ "$CSS_COUNT" -gt 0 ]; then
        echo -e "${GREEN}✓${NC} Found $JS_COUNT JS files and $CSS_COUNT CSS files\n"
        ASSETS_FOUND=1
    fi
fi

if [ "$ASSETS_FOUND" -eq 0 ]; then
    echo -e "${RED}✗${NC} No assets found in public/build/assets"
    echo -e "${YELLOW}Run: npm run build${NC}\n"
    exit 1
fi

# Check 5: .htaccess
echo -e "${BLUE}[5/7]${NC} Checking .htaccess..."
if [ -f "public/.htaccess" ]; then
    echo -e "${GREEN}✓${NC} .htaccess exists\n"
else
    echo -e "${RED}✗${NC} .htaccess missing"
    echo -e "${YELLOW}This is required for production!${NC}\n"
    exit 1
fi

# Check 6: .env.example
echo -e "${BLUE}[6/7]${NC} Checking .env.example..."
if [ -f ".env.example" ]; then
    if grep -q "ASSET_URL" .env.example; then
        echo -e "${GREEN}✓${NC} .env.example has ASSET_URL configured\n"
    else
        echo -e "${YELLOW}⚠${NC} .env.example missing ASSET_URL"
        echo -e "${YELLOW}Add: ASSET_URL=https://your-domain.com${NC}\n"
    fi
else
    echo -e "${YELLOW}⚠${NC} .env.example not found\n"
fi

# Check 7: Composer dependencies
echo -e "${BLUE}[7/7]${NC} Checking composer.lock..."
if [ -f "composer.lock" ]; then
    echo -e "${GREEN}✓${NC} Composer dependencies locked\n"
else
    echo -e "${YELLOW}⚠${NC} composer.lock not found"
    echo -e "${YELLOW}Run: composer install${NC}\n"
fi

# Summary
echo -e "${BLUE}================================${NC}"
echo -e "${GREEN}✓ All checks passed!${NC}"
echo -e "${BLUE}================================${NC}\n"

echo -e "Next steps for deployment:\n"
echo -e "1. Commit and push changes:"
echo -e "   ${YELLOW}git add .${NC}"
echo -e "   ${YELLOW}git commit -m 'Production build ready'${NC}"
echo -e "   ${YELLOW}git push origin main${NC}\n"

echo -e "2. On production server:"
echo -e "   ${YELLOW}cd /home2/shemidig/parish_system${NC}"
echo -e "   ${YELLOW}git pull origin main${NC}"
echo -e "   ${YELLOW}composer install --no-dev --optimize-autoloader${NC}"
echo -e "   ${YELLOW}php artisan migrate --force${NC}"
echo -e "   ${YELLOW}php artisan config:cache${NC}"
echo -e "   ${YELLOW}php artisan route:cache${NC}"
echo -e "   ${YELLOW}php artisan view:cache${NC}"
echo -e "   ${YELLOW}chmod -R 755 public/build${NC}\n"

echo -e "3. Verify deployment:"
echo -e "   ${YELLOW}Visit: https://parish.quovadisyouthhub.org/diagnostic.php${NC}\n"

echo -e "${GREEN}Ready for deployment! 🚀${NC}\n"
