#!/bin/bash

# ============================================================================
# CI/CD PIPELINE - COMPLETE INSTALLATION SCRIPT
# ============================================================================
# This script runs all necessary checks and helps you set up the CI/CD pipeline
# 
# Usage: bash install-cicd.sh
# ============================================================================

echo "╔════════════════════════════════════════════════════════════════════╗"
echo "║                                                                    ║"
echo "║     PARISH MANAGEMENT SYSTEM - CI/CD PIPELINE INSTALLER           ║"
echo "║                                                                    ║"
echo "╚════════════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Please run from Laravel root directory."
    exit 1
fi

print_success "Found Laravel project"
echo ""

# Step 1: Check local requirements
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 1: Checking Local Requirements"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Check Git
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version | awk '{print $3}')
    print_success "Git installed: v$GIT_VERSION"
else
    print_error "Git not found"
    exit 1
fi

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}')
    print_success "PHP installed: v$PHP_VERSION"
    
    if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
        print_success "PHP version >= 8.2 ✓"
    else
        print_warning "PHP version should be >= 8.2"
    fi
else
    print_error "PHP not found"
    exit 1
fi

# Check Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | awk '{print $3}')
    print_success "Composer installed: v$COMPOSER_VERSION"
else
    print_error "Composer not found"
    exit 1
fi

# Check NPM
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    print_success "NPM installed: v$NPM_VERSION"
else
    print_error "NPM not found"
    exit 1
fi

# Check Node
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    print_success "Node.js installed: $NODE_VERSION"
else
    print_error "Node.js not found"
    exit 1
fi

echo ""

# Step 2: Check required files
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 2: Verifying CI/CD Files"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

FILES=(
    "public/webhook.php"
    "public/deploy.php"
    ".github/workflows/laravel.yml"
    ".cpanel.yml"
    "CICD_SETUP_GUIDE.md"
    "CICD_QUICK_START.md"
    "CICD_TESTING_GUIDE.md"
    "README_CICD.md"
    "setup-webhook.sh"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        print_success "$file"
    else
        print_error "$file (missing)"
    fi
done

echo ""

# Step 3: Run tests locally
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 3: Running Local Tests (Optional)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

read -p "Run tests now? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_info "Installing dependencies..."
    composer install --quiet
    
    if [ ! -f ".env" ]; then
        print_info "Creating .env file..."
        cp .env.example .env
        php artisan key:generate --quiet
    fi
    
    print_info "Running tests..."
    if php artisan test --quiet; then
        print_success "All tests passed!"
    else
        print_warning "Some tests failed. Review and fix before deploying."
    fi
fi

echo ""

# Step 4: Configuration
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 4: Configuration"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

read -p "Configure webhook files now? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    bash setup-webhook.sh
else
    print_info "You can run 'bash setup-webhook.sh' later to configure"
fi

echo ""

# Step 5: Next steps
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "INSTALLATION COMPLETE!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
print_success "CI/CD pipeline files are ready!"
echo ""
echo "📚 DOCUMENTATION:"
echo "   • README_CICD.md          - Overview and introduction"
echo "   • CICD_QUICK_START.md     - Quick setup guide (30 min)"
echo "   • CICD_SETUP_GUIDE.md     - Complete guide (detailed)"
echo "   • CICD_TESTING_GUIDE.md   - Testing procedures"
echo ""
echo "🚀 NEXT STEPS:"
echo "   1. Read README_CICD.md for overview"
echo "   2. Follow CICD_QUICK_START.md for setup"
echo "   3. Configure cPanel server"
echo "   4. Create GitHub webhook"
echo "   5. Test deployment"
echo ""
echo "⏱️  ESTIMATED TIME:"
echo "   • Configuration: 5 minutes"
echo "   • cPanel setup: 15 minutes"
echo "   • GitHub setup: 5 minutes"
echo "   • Testing: 10 minutes"
echo "   • Total: ~35 minutes"
echo ""
echo "📖 START HERE: cat README_CICD.md"
echo ""
print_success "Good luck! 🎉"
echo ""
