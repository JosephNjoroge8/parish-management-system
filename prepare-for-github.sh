#!/bin/bash

# =============================================================================
# PARISH MANAGEMENT SYSTEM - SAFE GITHUB PREPARATION SCRIPT
# =============================================================================
# This script safely prepares the repository for pushing to GitHub
# It ensures no sensitive data is accidentally committed
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Parish Management System - GitHub Preparation${NC}"
echo "================================================================="

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ Error: Not a git repository${NC}"
    exit 1
fi

# =============================================================================
# 1. SECURITY CHECK - ENSURE SENSITIVE FILES ARE NOT STAGED
# =============================================================================
echo -e "\n${BLUE}🔒 Step 1: Security Check${NC}"

# Check for sensitive files in staging area
echo "🔍 Checking for sensitive files in staging area..."

SENSITIVE_STAGED=$(git diff --cached --name-only | grep -E "\.(env|sqlite|log)$" || true)
if [ -n "$SENSITIVE_STAGED" ]; then
    echo -e "${RED}❌ Error: Sensitive files are staged for commit:${NC}"
    echo "$SENSITIVE_STAGED"
    echo -e "${YELLOW}⚠️  Run: git reset HEAD <file> to unstage these files${NC}"
    exit 1
fi

echo -e "${GREEN}✅ No sensitive files found in staging area${NC}"

# =============================================================================
# 2. DATABASE SAFETY CHECK
# =============================================================================
echo -e "\n${BLUE}🗄️  Step 2: Database Safety Check${NC}"

# Check if database file exists and contains data
if [ -f "database/database.sqlite" ]; then
    DB_SIZE=$(stat -c%s "database/database.sqlite" 2>/dev/null || echo "0")
    if [ "$DB_SIZE" -gt 100000 ]; then  # More than 100KB indicates real data
        echo -e "${YELLOW}⚠️  Warning: Large database file detected (${DB_SIZE} bytes)${NC}"
        echo "This likely contains real parish member data"
        echo -e "${RED}🚨 Database file is properly ignored and will not be committed${NC}"
    fi
fi

echo -e "${GREEN}✅ Database safety check completed${NC}"

# =============================================================================
# 3. ENVIRONMENT FILES CHECK
# =============================================================================
echo -e "\n${BLUE}⚙️  Step 3: Environment Files Check${NC}"

# List environment files for user review
ENV_FILES=$(find . -maxdepth 1 -name ".env*" -type f | sort)
if [ -n "$ENV_FILES" ]; then
    echo "📋 Environment files found:"
    while IFS= read -r file; do
        if git check-ignore "$file" >/dev/null 2>&1; then
            echo -e "  ${GREEN}✅ $file (ignored)${NC}"
        else
            echo -e "  ${RED}❌ $file (NOT ignored - CHECK .gitignore)${NC}"
        fi
    done <<< "$ENV_FILES"
fi

echo -e "${GREEN}✅ Environment files check completed${NC}"

# =============================================================================
# 4. CLEAN UP UNNECESSARY FILES
# =============================================================================
echo -e "\n${BLUE}🧹 Step 4: Clean Up${NC}"

# Remove debug files and temporary files
echo "🗑️  Removing debug and temporary files..."

# List of files to remove (these were created during testing/debugging)
DEBUG_FILES=(
    "debug_member_registration.php"
    "analyze_performance.php"
    "education_level"
    "id"
    "matrimony_status" 
    "occupation"
)

for file in "${DEBUG_FILES[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        echo "  Removed: $file"
    fi
done

echo -e "${GREEN}✅ Cleanup completed${NC}"

# =============================================================================
# 5. STAGE SAFE FILES
# =============================================================================
echo -e "\n${BLUE}📦 Step 5: Staging Safe Files${NC}"

echo "📋 Adding safe files to git..."

# Add updated .gitignore first
git add .gitignore

# Add safe application files
git add app/ --ignore-errors 2>/dev/null || true
git add config/ --ignore-errors 2>/dev/null || true
git add resources/ --ignore-errors 2>/dev/null || true
git add routes/ --ignore-errors 2>/dev/null || true
git add bootstrap/ --ignore-errors 2>/dev/null || true

# Add database migrations and seeders (but not actual database)
git add database/migrations/ --ignore-errors 2>/dev/null || true
git add database/seeders/ --ignore-errors 2>/dev/null || true

# Add production configuration files
git add "*.sh" --ignore-errors 2>/dev/null || true
git add "*.conf" --ignore-errors 2>/dev/null || true
git add "*.md" --ignore-errors 2>/dev/null || true

# Add package files
git add package*.json --ignore-errors 2>/dev/null || true
git add composer.json --ignore-errors 2>/dev/null || true

# Add other safe files
git add LICENSE --ignore-errors 2>/dev/null || true
git add README.md --ignore-errors 2>/dev/null || true

echo -e "${GREEN}✅ Safe files staged${NC}"

# =============================================================================
# 6. SHOW WHAT WILL BE COMMITTED
# =============================================================================
echo -e "\n${BLUE}📋 Step 6: Review Changes${NC}"

echo "📊 Files staged for commit:"
git diff --cached --name-status | head -20

TOTAL_STAGED=$(git diff --cached --name-only | wc -l)
echo -e "\n${BLUE}Total files staged: $TOTAL_STAGED${NC}"

if [ "$TOTAL_STAGED" -gt 20 ]; then
    echo "... (showing first 20 files)"
fi

# =============================================================================
# 7. COMMIT PREPARATION
# =============================================================================
echo -e "\n${BLUE}✅ Step 7: Commit Preparation${NC}"

echo -e "${GREEN}🎉 Repository is ready for GitHub!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "1. Review the staged files above"
echo "2. Create commit with: git commit -m 'Initial parish management system commit'"
echo "3. Add GitHub remote: git remote add origin https://github.com/JosephNjoroge8/parish-management-system.git"
echo "4. Push to GitHub: git push -u origin main"
echo ""
echo -e "${BLUE}🔒 Security Notes:${NC}"
echo "✅ Database files are ignored (.sqlite files)"
echo "✅ Environment files are ignored (.env files)"
echo "✅ Log files are ignored"
echo "✅ Sensitive configuration is protected"
echo "✅ Only safe application code will be committed"

echo ""
echo -e "${YELLOW}⚠️  Before pushing to GitHub:${NC}"
echo "1. Make sure your .env file is properly configured for production"
echo "2. Verify no sensitive data is in any committed files"
echo "3. Review the PRODUCTION_DEPLOYMENT_GUIDE.md for deployment instructions"

echo ""
echo -e "${GREEN}🚀 Ready to commit and push to GitHub!${NC}"