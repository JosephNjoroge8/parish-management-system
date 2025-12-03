#!/bin/bash

# ============================================================================
# PRODUCTION SETUP VERIFICATION SCRIPT
# ============================================================================
# This script verifies your production configuration before deployment
# Run this BEFORE pushing to production
#
# Usage: bash verify-production-setup.sh
# ============================================================================

echo "╔════════════════════════════════════════════════════════════════════╗"
echo "║                                                                    ║"
echo "║     PRODUCTION SETUP VERIFICATION                                 ║"
echo "║     Parish Management System                                      ║"
echo "║                                                                    ║"
echo "╚════════════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Expected values
EXPECTED_PATH="/home2/shemidig/parish_system"
EXPECTED_DOMAIN="parish.quovadisyouthhub.org"
EXPECTED_DB="shemidig_parish_system"
EXPECTED_DB_USER="shemidig_NjoroParish"

print_check() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
    fi
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

echo "Verifying CI/CD Configuration..."
echo ""

# Check if files exist
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. Checking Required Files"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

FILES=(
    "public/webhook.php"
    "public/deploy.php"
    ".github/workflows/laravel.yml"
    "PRODUCTION_CONFIG.md"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        print_check 0 "$file exists"
    else
        print_check 1 "$file MISSING"
        exit 1
    fi
done

echo ""

# Check webhook.php configuration
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2. Verifying webhook.php Configuration"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if grep -q "$EXPECTED_PATH" public/webhook.php; then
    print_check 0 "Deployment path configured correctly"
else
    print_check 1 "Deployment path NOT configured"
    print_warning "Expected: $EXPECTED_PATH"
fi

if grep -q "your-super-secret-webhook-key-change-this" public/webhook.php; then
    print_check 1 "Webhook secret NOT changed (still default)"
    print_warning "You MUST change the webhook secret!"
else
    print_check 0 "Webhook secret has been changed"
fi

if grep -q "$EXPECTED_DOMAIN" public/webhook.php; then
    print_check 0 "Domain configured"
else
    print_warning "Domain might not be configured"
fi

echo ""

# Check deploy.php configuration
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3. Verifying deploy.php Configuration"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if grep -q "$EXPECTED_PATH" public/deploy.php; then
    print_check 0 "Deployment path configured correctly"
else
    print_check 1 "Deployment path NOT configured"
fi

if grep -q "your-super-secret-webhook-key-change-this" public/deploy.php; then
    print_check 1 "Deploy secret NOT changed (still default)"
    print_warning "You MUST change the deploy secret!"
else
    print_check 0 "Deploy secret has been changed"
fi

echo ""

# Check if secrets match
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4. Verifying Secret Synchronization"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

WEBHOOK_SECRET=$(grep "define('WEBHOOK_SECRET'" public/webhook.php | cut -d"'" -f4)
DEPLOY_SECRET=$(grep "define('DEPLOY_SECRET'" public/deploy.php | cut -d"'" -f4)

if [ "$WEBHOOK_SECRET" == "$DEPLOY_SECRET" ]; then
    print_check 0 "Secrets match between webhook.php and deploy.php"
else
    print_check 1 "Secrets DO NOT match!"
    print_warning "webhook.php and deploy.php MUST have the same secret"
fi

echo ""

# Check GitHub workflow
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5. Verifying GitHub Actions Workflow"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if grep -q "branches: \[ Main \]" .github/workflows/laravel.yml; then
    print_check 0 "Workflow configured for Main branch"
else
    print_warning "Workflow might not be configured for Main branch"
fi

echo ""

# Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "VERIFICATION SUMMARY"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if grep -q "your-super-secret-webhook-key-change-this" public/webhook.php; then
    echo -e "${RED}❌ NOT READY FOR PRODUCTION${NC}"
    echo ""
    echo "Required actions:"
    echo "1. Generate a webhook secret: openssl rand -hex 32"
    echo "2. Update public/webhook.php (line 25)"
    echo "3. Update public/deploy.php (line 34)"
    echo "4. Or run: bash setup-webhook.sh"
    echo ""
elif [ "$WEBHOOK_SECRET" != "$DEPLOY_SECRET" ]; then
    echo -e "${RED}❌ SECRETS DO NOT MATCH${NC}"
    echo ""
    echo "The secrets in webhook.php and deploy.php must be identical!"
    echo "Run: bash setup-webhook.sh to fix"
    echo ""
else
    echo -e "${GREEN}✅ READY FOR DEPLOYMENT${NC}"
    echo ""
    echo "Configuration verified successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Commit changes: git add . && git commit -m 'Configure CI/CD'"
    echo "2. Push to GitHub: git push origin Main"
    echo "3. Upload webhook.php and deploy.php to cPanel"
    echo "4. Create GitHub webhook"
    echo "5. Test deployment"
    echo ""
    echo "See PRODUCTION_CONFIG.md for detailed instructions"
fi

echo ""
