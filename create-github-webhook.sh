#!/bin/bash

# ============================================================================
# GITHUB WEBHOOK CREATOR
# ============================================================================
# This script creates the GitHub webhook automatically using GitHub CLI
# 
# Prerequisites: gh CLI installed and authenticated
# Install: https://cli.github.com/
# Auth: gh auth login
# ============================================================================

WEBHOOK_SECRET="7c405eeeaca081e63d0fd443c7f564b1719bf0594169601aa2a706cbea276a8a"
WEBHOOK_URL="https://parish.quovadisyouthhub.org/webhook.php"
REPO="JosephNjoroge8/parish-management-system"

echo "╔════════════════════════════════════════════════════════════════════╗"
echo "║                                                                    ║"
echo "║     GITHUB WEBHOOK CREATOR                                        ║"
echo "║                                                                    ║"
echo "╚════════════════════════════════════════════════════════════════════╝"
echo ""

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "❌ GitHub CLI (gh) is not installed"
    echo ""
    echo "Please install it first:"
    echo "  Ubuntu/Debian: sudo apt install gh"
    echo "  macOS: brew install gh"
    echo "  Or visit: https://cli.github.com/"
    echo ""
    echo "After installation, authenticate with:"
    echo "  gh auth login"
    echo ""
    echo "Then run this script again."
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "❌ Not authenticated with GitHub"
    echo ""
    echo "Please authenticate first:"
    echo "  gh auth login"
    echo ""
    exit 1
fi

echo "✅ GitHub CLI authenticated"
echo ""
echo "Creating webhook for: $REPO"
echo "Webhook URL: $WEBHOOK_URL"
echo ""

# Create webhook using GitHub API
gh api \
  --method POST \
  -H "Accept: application/vnd.github+json" \
  -H "X-GitHub-Api-Version: 2022-11-28" \
  /repos/$REPO/hooks \
  -f name='web' \
  -f active=true \
  -F config[url]="$WEBHOOK_URL" \
  -F config[content_type]='json' \
  -F config[secret]="$WEBHOOK_SECRET" \
  -F config[insecure_ssl]='0' \
  -f events[]='push' \
  && echo "" && echo "✅ Webhook created successfully!" \
  || echo "" && echo "❌ Failed to create webhook (it may already exist)"

echo ""
echo "Next steps:"
echo "1. Verify webhook: https://github.com/$REPO/settings/hooks"
echo "2. Test deployment: git push origin Main"
echo "3. Monitor logs on server"
echo ""
