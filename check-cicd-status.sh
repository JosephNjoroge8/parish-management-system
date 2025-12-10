#!/bin/bash

##############################################################################
# CI/CD STATUS CHECKER
##############################################################################
# Checks the status of your GitHub Actions workflow
##############################################################################

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}CI/CD Pipeline Status${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Repository info
REPO="JosephNjoroge8/parish-management-system"
BRANCH="Main"
GITHUB_URL="https://github.com/${REPO}"

echo -e "${GREEN}Repository:${NC} $REPO"
echo -e "${GREEN}Branch:${NC} $BRANCH\n"

# Get latest commit
LATEST_COMMIT=$(git log -1 --oneline)
echo -e "${GREEN}Latest Commit:${NC}"
echo -e "  $LATEST_COMMIT\n"

# Check if push was successful
LAST_PUSH=$(git log --pretty=format:'%h - %s (%cr)' -1)
echo -e "${GREEN}Last Push:${NC}"
echo -e "  $LAST_PUSH\n"

# Links to check
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}Check CI/CD Status:${NC}"
echo -e "${BLUE}========================================${NC}\n"

echo -e "1. ${GREEN}GitHub Actions:${NC}"
echo -e "   ${GITHUB_URL}/actions\n"

echo -e "2. ${GREEN}Latest Workflow Run:${NC}"
echo -e "   ${GITHUB_URL}/actions/workflows/laravel.yml\n"

echo -e "3. ${GREEN}Commits:${NC}"
echo -e "   ${GITHUB_URL}/commits/${BRANCH}\n"

# Workflow file check
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}Workflow Configuration:${NC}"
echo -e "${BLUE}========================================${NC}\n"

if [ -f ".github/workflows/laravel.yml" ]; then
    echo -e "${GREEN}✓${NC} Workflow file exists: .github/workflows/laravel.yml"
    
    # Check workflow jobs
    JOBS=$(grep -E "^  [a-z_-]+:" .github/workflows/laravel.yml | sed 's/:$//' | sed 's/^  //' || true)
    echo -e "\n${GREEN}Configured Jobs:${NC}"
    echo "$JOBS" | while read job; do
        echo -e "  • $job"
    done
else
    echo -e "${YELLOW}⚠${NC} No workflow file found"
fi

echo -e "\n${BLUE}========================================${NC}"
echo -e "${GREEN}Your CI/CD pipeline should be running!${NC}"
echo -e "${BLUE}========================================${NC}\n"

echo -e "Visit the links above to see:"
echo -e "  • Test results"
echo -e "  • Build status"  
echo -e "  • Deployment logs\n"

echo -e "${YELLOW}Tip:${NC} Install GitHub CLI for terminal status:"
echo -e "  ${GREEN}sudo apt install gh${NC}"
echo -e "  ${GREEN}gh auth login${NC}"
echo -e "  ${GREEN}gh run list${NC}\n"
